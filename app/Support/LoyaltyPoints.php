<?php

namespace App\Support;

use App\Enums\LoyaltyPointTransactionType;
use App\Enums\PointsRedeemStatus;
use App\Models\LoyaltyPointTransaction;
use App\Models\LoyaltyWallet;
use App\Models\Order;
use App\Models\PromoCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use InvalidArgumentException;

class LoyaltyPoints
{
    private const SESSION_KEY = 'checkout_use_points';

    public function __construct(private ShopSettings $settings) {}

    public function enabled(): bool
    {
        $points = $this->settings->points();

        return $points[ShopSettings::POINTS_ENABLED]
            && $points[ShopSettings::POINTS_PER_PAID_ORDER] >= 0
            && $points[ShopSettings::POINT_VALUE_GHS] > 0;
    }

    public function pointsPerPaidOrder(): int
    {
        return max(0, $this->settings->points()[ShopSettings::POINTS_PER_PAID_ORDER]);
    }

    public function pointValueGhs(): float
    {
        return max(0, $this->settings->points()[ShopSettings::POINT_VALUE_GHS]);
    }

    public function isRedeeming(): bool
    {
        return (bool) Session::get(self::SESSION_KEY, false);
    }

    public function enableRedeem(): void
    {
        Session::put(self::SESSION_KEY, true);
    }

    public function clearRedeem(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    public function balanceForPhone(?string $phone): int
    {
        $normalized = PhoneNumber::normalize((string) $phone);

        if ($normalized === '') {
            return 0;
        }

        return (int) (LoyaltyWallet::query()
            ->where('phone_normalized', $normalized)
            ->value('balance') ?? 0);
    }

    public function ghsValue(int $points): string
    {
        return number_format(max(0, $points) * $this->pointValueGhs(), 2, '.', '');
    }

    /**
     * @return array{
     *     enabled: bool,
     *     redeeming: bool,
     *     balance: int,
     *     balance_value: string,
     *     points_to_redeem: int,
     *     discount_amount: string,
     *     point_value_ghs: float,
     *     points_per_paid_order: int
     * }
     */
    public function quoteRedeem(string $payableBeforePoints, ?string $phone): array
    {
        $balance = $this->balanceForPhone($phone);
        $pointValue = $this->pointValueGhs();
        $enabled = $this->enabled();
        $redeeming = $enabled && $this->isRedeeming() && $phone !== null && $phone !== '';

        $maxByValue = $pointValue > 0
            ? (int) floor(((float) $payableBeforePoints) / $pointValue)
            : 0;

        $pointsToRedeem = $redeeming ? min($balance, max(0, $maxByValue)) : 0;
        $discount = $this->ghsValue($pointsToRedeem);

        if ($pointsToRedeem > 0 && bccomp($discount, $payableBeforePoints, 2) === 1) {
            $discount = number_format((float) $payableBeforePoints, 2, '.', '');
        }

        return [
            'enabled' => $enabled,
            'redeeming' => $redeeming && $pointsToRedeem > 0,
            'balance' => $balance,
            'balance_value' => $this->ghsValue($balance),
            'points_to_redeem' => $pointsToRedeem,
            'discount_amount' => $pointsToRedeem > 0 ? $discount : '0.00',
            'point_value_ghs' => $pointValue,
            'points_per_paid_order' => $this->pointsPerPaidOrder(),
        ];
    }

    public function findOrCreateWallet(string $phone): LoyaltyWallet
    {
        $normalized = PhoneNumber::normalize($phone);

        if ($normalized === '') {
            throw new InvalidArgumentException('A valid phone number is required for loyalty points.');
        }

        return LoyaltyWallet::query()->firstOrCreate(
            ['phone_normalized' => $normalized],
            ['balance' => 0],
        );
    }

    public function reserveForOrder(Order $order): void
    {
        if ($order->points_redeemed <= 0) {
            return;
        }

        if ($order->points_redeem_status !== PointsRedeemStatus::None) {
            return;
        }

        DB::transaction(function () use ($order): void {
            $wallet = $this->lockedWalletForPhone((string) $order->customer_phone);

            if ($wallet->balance < $order->points_redeemed) {
                throw new InvalidArgumentException('Not enough loyalty points to complete this order.');
            }

            $alreadyReserved = LoyaltyPointTransaction::query()
                ->where('order_id', $order->id)
                ->where('type', LoyaltyPointTransactionType::RedeemReserve)
                ->exists();

            if ($alreadyReserved) {
                return;
            }

            $wallet->balance -= $order->points_redeemed;
            $wallet->save();

            $this->recordTransaction(
                $wallet,
                $order,
                LoyaltyPointTransactionType::RedeemReserve,
                -1 * $order->points_redeemed,
                'Points reserved at checkout',
            );

            $order->update([
                'points_redeem_status' => PointsRedeemStatus::Reserved,
            ]);

            Log::info('loyalty.points_reserved', [
                'order' => $order->uuid,
                'points' => $order->points_redeemed,
                'balance' => $wallet->balance,
                'phone' => $wallet->phone_normalized,
            ]);
        });
    }

    public function settleRedeem(Order $order): void
    {
        if ($order->points_redeem_status !== PointsRedeemStatus::Reserved) {
            return;
        }

        DB::transaction(function () use ($order): void {
            $wallet = $this->lockedWalletForPhone((string) $order->customer_phone);

            $alreadySettled = LoyaltyPointTransaction::query()
                ->where('order_id', $order->id)
                ->where('type', LoyaltyPointTransactionType::RedeemSettle)
                ->exists();

            if ($alreadySettled) {
                return;
            }

            $this->recordTransaction(
                $wallet,
                $order,
                LoyaltyPointTransactionType::RedeemSettle,
                0,
                'Points redemption settled',
            );

            $order->update([
                'points_redeem_status' => PointsRedeemStatus::Settled,
            ]);

            Log::info('loyalty.points_settled', [
                'order' => $order->uuid,
                'points' => $order->points_redeemed,
                'phone' => $wallet->phone_normalized,
            ]);
        });
    }

    public function releaseRedeem(Order $order): void
    {
        if ($order->points_redeem_status !== PointsRedeemStatus::Reserved) {
            return;
        }

        if ($order->points_redeemed <= 0) {
            $order->update(['points_redeem_status' => PointsRedeemStatus::Released]);

            return;
        }

        DB::transaction(function () use ($order): void {
            $wallet = $this->lockedWalletForPhone((string) $order->customer_phone);

            $alreadyReleased = LoyaltyPointTransaction::query()
                ->where('order_id', $order->id)
                ->where('type', LoyaltyPointTransactionType::RedeemRelease)
                ->exists();

            if ($alreadyReleased) {
                return;
            }

            $wallet->balance += $order->points_redeemed;
            $wallet->save();

            $this->recordTransaction(
                $wallet,
                $order,
                LoyaltyPointTransactionType::RedeemRelease,
                $order->points_redeemed,
                'Points released after cancelled or failed order',
            );

            $order->update([
                'points_redeem_status' => PointsRedeemStatus::Released,
            ]);

            Log::info('loyalty.points_released', [
                'order' => $order->uuid,
                'points' => $order->points_redeemed,
                'balance' => $wallet->balance,
                'phone' => $wallet->phone_normalized,
            ]);
        });
    }

    public function earnForPaidOrder(Order $order): void
    {
        if (! $this->enabled()) {
            Log::info('loyalty.earn_skipped', [
                'order' => $order->uuid,
                'reason' => 'points_disabled',
            ]);

            return;
        }

        $points = $this->pointsPerPaidOrder();

        if ($points <= 0 || blank($order->customer_phone)) {
            Log::info('loyalty.earn_skipped', [
                'order' => $order->uuid,
                'reason' => $points <= 0 ? 'zero_points_rate' : 'missing_phone',
            ]);

            return;
        }

        if ($order->points_earned !== null) {
            return;
        }

        DB::transaction(function () use ($order, $points): void {
            $wallet = $this->lockedWalletForPhone((string) $order->customer_phone);

            $alreadyEarned = LoyaltyPointTransaction::query()
                ->where('order_id', $order->id)
                ->where('type', LoyaltyPointTransactionType::Earn)
                ->exists();

            if ($alreadyEarned) {
                if ($order->points_earned === null) {
                    $order->update(['points_earned' => $points]);
                }

                return;
            }

            $wallet->balance += $points;
            $wallet->save();

            $this->recordTransaction(
                $wallet,
                $order,
                LoyaltyPointTransactionType::Earn,
                $points,
                'Earned on paid order',
            );

            $order->update(['points_earned' => $points]);

            Log::info('loyalty.points_earned', [
                'order' => $order->uuid,
                'points' => $points,
                'balance' => $wallet->balance,
                'phone' => $wallet->phone_normalized,
            ]);
        });
    }

    /**
     * Credit the promo referrer for a paid order. Idempotent per order.
     *
     * @return array{points: int, balance: int, phone: string}|null
     */
    public function earnReferralForOrder(Order $order, PromoCode $promo): ?array
    {
        if (! $promo->hasReferrer()) {
            return null;
        }

        if ($promo->isOwnedByPhone($order->customer_phone) || $promo->isOwnedByPhone($order->customer_phone_normalized)) {
            return null;
        }

        $points = $this->settings->referralPointsPerOrder();

        if ($points <= 0) {
            Log::info('loyalty.referral_earn_skipped', [
                'order' => $order->uuid,
                'promo_code' => $promo->code,
                'reason' => 'zero_referral_points_rate',
            ]);

            return null;
        }

        return DB::transaction(function () use ($order, $promo, $points): ?array {
            $alreadyEarned = LoyaltyPointTransaction::query()
                ->where('order_id', $order->id)
                ->where('type', LoyaltyPointTransactionType::ReferralEarn)
                ->exists();

            if ($alreadyEarned) {
                return null;
            }

            $wallet = $this->lockedWalletForPhone((string) $promo->referrer_phone);
            $wallet->balance += $points;
            $wallet->save();

            $this->recordTransaction(
                $wallet,
                $order,
                LoyaltyPointTransactionType::ReferralEarn,
                $points,
                'Referral reward for promo '.$promo->code,
            );

            Log::info('loyalty.referral_points_earned', [
                'order' => $order->uuid,
                'promo_code' => $promo->code,
                'points' => $points,
                'balance' => $wallet->balance,
                'phone' => $wallet->phone_normalized,
            ]);

            return [
                'points' => $points,
                'balance' => $wallet->balance,
                'phone' => (string) $promo->referrer_phone,
            ];
        });
    }

    public function adjustBalance(LoyaltyWallet $wallet, int $delta, ?string $note = null): LoyaltyWallet
    {
        return DB::transaction(function () use ($wallet, $delta, $note): LoyaltyWallet {
            $locked = LoyaltyWallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();

            $next = $locked->balance + $delta;

            if ($next < 0) {
                throw new InvalidArgumentException('Adjustment would make the wallet balance negative.');
            }

            $locked->balance = $next;
            $locked->save();

            $this->recordTransaction(
                $locked,
                null,
                LoyaltyPointTransactionType::Adjust,
                $delta,
                $note ?: 'Admin adjustment',
            );

            Log::info('loyalty.balance_adjusted', [
                'wallet_id' => $locked->id,
                'phone' => $locked->phone_normalized,
                'delta' => $delta,
                'balance' => $locked->balance,
                'note' => $note ?: 'Admin adjustment',
            ]);

            return $locked->refresh();
        });
    }

    private function lockedWalletForPhone(string $phone): LoyaltyWallet
    {
        $wallet = $this->findOrCreateWallet($phone);

        return LoyaltyWallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();
    }

    private function recordTransaction(
        LoyaltyWallet $wallet,
        ?Order $order,
        LoyaltyPointTransactionType $type,
        int $points,
        ?string $note = null,
    ): LoyaltyPointTransaction {
        return LoyaltyPointTransaction::query()->create([
            'loyalty_wallet_id' => $wallet->id,
            'order_id' => $order?->id,
            'type' => $type,
            'points' => $points,
            'balance_after' => $wallet->balance,
            'note' => $note,
        ]);
    }
}
