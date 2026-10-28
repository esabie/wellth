<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Models\Order;

class SecondPurchaseDiscount
{
    private const SESSION_KEY = 'checkout_loyalty_phone';

    public function __construct(private ShopSettings $settings) {}

    public function percent(): float
    {
        return max(0, $this->settings->loyalty()[ShopSettings::SECOND_PURCHASE_PERCENT]);
    }

    public function enabled(): bool
    {
        $loyalty = $this->settings->loyalty();

        return $loyalty[ShopSettings::SECOND_PURCHASE_ENABLED] && $this->percent() > 0;
    }

    public function label(): string
    {
        $label = $this->settings->loyalty()[ShopSettings::SECOND_PURCHASE_LABEL];

        if ($label !== '') {
            return $label;
        }

        return 'Second purchase '.$this->percent().'% off';
    }

    public function rememberPreviewPhone(string $phone): void
    {
        $normalized = PhoneNumber::normalize($phone);

        if ($normalized === '') {
            session()->forget(self::SESSION_KEY);

            return;
        }

        session([self::SESSION_KEY => $normalized]);
    }

    public function previewPhone(): ?string
    {
        $phone = session(self::SESSION_KEY);

        return is_string($phone) && $phone !== '' ? $phone : null;
    }

    public function clearPreviewPhone(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function paidOrderCount(string $phone): int
    {
        $normalized = PhoneNumber::normalize($phone);

        if ($normalized === '') {
            return 0;
        }

        return Order::query()
            ->where('customer_phone_normalized', $normalized)
            ->whereIn('status', [
                OrderStatus::Paid,
                OrderStatus::Preparing,
                OrderStatus::Shipped,
            ])
            ->count();
    }

    /**
     * Exactly the second paid order: eligible when the customer already has one paid order.
     */
    public function isEligible(string $phone): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        return $this->paidOrderCount($phone) === 1;
    }

    /**
     * @return array{eligible: bool, discount_amount: string, label: string|null, paid_order_count: int}
     */
    public function quote(string $subtotal, ?string $phone): array
    {
        $paidCount = $phone !== null && $phone !== '' ? $this->paidOrderCount($phone) : 0;
        $eligible = $phone !== null && $phone !== '' && $this->isEligible($phone);

        if (! $eligible) {
            return [
                'eligible' => false,
                'discount_amount' => '0.00',
                'label' => null,
                'paid_order_count' => $paidCount,
            ];
        }

        $discount = number_format(
            min((float) $subtotal, ((float) $subtotal) * ($this->percent() / 100)),
            2,
            '.',
            '',
        );

        return [
            'eligible' => true,
            'discount_amount' => $discount,
            'label' => $this->label(),
            'paid_order_count' => $paidCount,
        ];
    }
}
