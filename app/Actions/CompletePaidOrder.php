<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Notifications\Channels\MnotifyChannel;
use App\Notifications\OrderPaidCustomerNotification;
use App\Notifications\OrderPaidCustomerSmsNotification;
use App\Notifications\ReferralRewardSmsNotification;
use App\Support\Cart;
use App\Support\LoyaltyPoints;
use App\Support\PhoneNumber;
use App\Support\PromoCodes;
use App\Support\ShopAdminNotifier;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class CompletePaidOrder
{
    public function handle(Order $order, ?string $paidAt = null): Order
    {
        if ($order->isPaid()) {
            Log::info('checkout.payment_confirm_skipped', [
                'order' => $order->uuid,
                'status' => $order->status->value,
                'reason' => 'already_paid',
            ]);

            return $order;
        }

        $previousStatus = $order->status->value;
        $referralReward = null;

        $order = DB::transaction(function () use ($order, $paidAt, &$referralReward): Order {
            $order->update([
                'status' => OrderStatus::Paid,
                'paid_at' => $paidAt ? Carbon::parse($paidAt) : now(),
            ]);

            $order = $order->refresh();

            app(LoyaltyPoints::class)->settleRedeem($order);
            app(LoyaltyPoints::class)->earnForPaidOrder($order->refresh());

            app(Cart::class)->clear();

            $promos = app(PromoCodes::class);
            $promos->recordRedemption($order->promo_code);

            if (filled($order->promo_code)) {
                $promo = $promos->find((string) $order->promo_code);

                if ($promo !== null) {
                    $referralReward = app(LoyaltyPoints::class)->earnReferralForOrder($order, $promo);
                }
            }

            return $order->refresh()->load('items');
        });

        Log::info('checkout.payment_confirmed', [
            'order' => $order->uuid,
            'previous_status' => $previousStatus,
            'status' => $order->status->value,
            'total' => $order->total,
            'promo_code' => $order->promo_code,
            'points_earned' => $order->points_earned,
            'points_redeemed' => $order->points_redeemed,
            'referral_points' => $referralReward['points'] ?? null,
            'paid_at' => $order->paid_at?->toIso8601String(),
        ]);

        $this->sendPaidNotifications($order);
        $this->sendReferralRewardSms($order, $referralReward);

        return $order;
    }

    private function sendPaidNotifications(Order $order): void
    {
        Notification::route('mail', $order->customer_email)
            ->notify(new OrderPaidCustomerNotification($order));

        Log::info('checkout.customer_paid_email_queued', [
            'order' => $order->uuid,
            'email' => $order->customer_email,
        ]);

        $this->sendCustomerSmsConfirmation($order);

        app(ShopAdminNotifier::class)->orderPaid($order);
    }

    private function sendCustomerSmsConfirmation(Order $order): void
    {
        if (! filled(config('services.mnotify.api_key'))) {
            Log::warning('checkout.customer_paid_sms_skipped', [
                'order' => $order->uuid,
                'reason' => 'mnotify_not_configured',
            ]);

            return;
        }

        if (blank($order->customer_phone)) {
            Log::warning('checkout.customer_paid_sms_skipped', [
                'order' => $order->uuid,
                'reason' => 'missing_customer_phone',
            ]);

            return;
        }

        Notification::route(MnotifyChannel::class, PhoneNumber::forSms($order->customer_phone))
            ->notify(new OrderPaidCustomerSmsNotification($order));

        Log::info('checkout.customer_paid_sms_queued', [
            'order' => $order->uuid,
            'phone' => PhoneNumber::normalize((string) $order->customer_phone),
        ]);
    }

    /**
     * @param  array{points: int, balance: int, phone: string}|null  $referralReward
     */
    private function sendReferralRewardSms(Order $order, ?array $referralReward): void
    {
        if ($referralReward === null) {
            return;
        }

        if (! filled(config('services.mnotify.api_key')) || blank($referralReward['phone'])) {
            Log::warning('checkout.referral_sms_skipped', [
                'order' => $order->uuid,
                'promo_code' => $order->promo_code,
                'reason' => ! filled(config('services.mnotify.api_key'))
                    ? 'mnotify_not_configured'
                    : 'missing_referrer_phone',
            ]);

            return;
        }

        Notification::route(MnotifyChannel::class, PhoneNumber::forSms($referralReward['phone']))
            ->notify(new ReferralRewardSmsNotification(
                $order,
                (string) $order->promo_code,
                $referralReward['points'],
                $referralReward['balance'],
            ));

        Log::info('checkout.referral_sms_queued', [
            'order' => $order->uuid,
            'promo_code' => $order->promo_code,
            'points' => $referralReward['points'],
            'balance' => $referralReward['balance'],
            'phone' => PhoneNumber::normalize($referralReward['phone']),
        ]);
    }
}
