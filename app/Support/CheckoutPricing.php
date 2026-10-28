<?php

namespace App\Support;

class CheckoutPricing
{
    public function __construct(
        private PromoCodes $promos,
        private SecondPurchaseDiscount $secondPurchase,
        private LoyaltyPoints $loyaltyPoints,
    ) {}

    /**
     * Loyalty % applies first on merchandise subtotal, then promo on the remainder, then shipping.
     * Redeeming points skips loyalty % and promo, then discounts subtotal + shipping.
     *
     * @return array{
     *     loyalty_discount_amount: string,
     *     loyalty_label: string|null,
     *     loyalty_eligible: bool,
     *     promo_code: string|null,
     *     promo_label: string|null,
     *     promo_discount_amount: string,
     *     points_enabled: bool,
     *     points_redeeming: bool,
     *     points_balance: int,
     *     points_balance_value: string,
     *     points_redeemed: int,
     *     points_discount_amount: string,
     *     points_per_paid_order: int,
     *     point_value_ghs: float,
     *     discount_amount: string,
     *     total: string
     * }
     */
    public function quote(string $subtotal, string $shippingAmount = '0.00', ?string $phone = null): array
    {
        $pointsPreview = $this->loyaltyPoints->quoteRedeem(
            bcadd($subtotal, $shippingAmount, 2),
            $phone,
        );

        if ($pointsPreview['redeeming']) {
            $total = bcsub(
                bcadd($subtotal, $shippingAmount, 2),
                $pointsPreview['discount_amount'],
                2,
            );

            return [
                'loyalty_discount_amount' => '0.00',
                'loyalty_label' => null,
                'loyalty_eligible' => false,
                'promo_code' => null,
                'promo_label' => null,
                'promo_discount_amount' => '0.00',
                'points_enabled' => $pointsPreview['enabled'],
                'points_redeeming' => true,
                'points_balance' => $pointsPreview['balance'],
                'points_balance_value' => $pointsPreview['balance_value'],
                'points_redeemed' => $pointsPreview['points_to_redeem'],
                'points_discount_amount' => $pointsPreview['discount_amount'],
                'points_per_paid_order' => $pointsPreview['points_per_paid_order'],
                'point_value_ghs' => $pointsPreview['point_value_ghs'],
                'discount_amount' => $pointsPreview['discount_amount'],
                'total' => $total,
            ];
        }

        $loyalty = $this->secondPurchase->quote($subtotal, $phone);
        $afterLoyalty = bcsub($subtotal, $loyalty['discount_amount'], 2);
        $promo = $this->promos->quote($afterLoyalty, $shippingAmount, $phone);
        $discountAmount = bcadd($loyalty['discount_amount'], $promo['discount_amount'], 2);

        return [
            'loyalty_discount_amount' => $loyalty['discount_amount'],
            'loyalty_label' => $loyalty['label'],
            'loyalty_eligible' => $loyalty['eligible'],
            'promo_code' => $promo['code'],
            'promo_label' => $promo['label'],
            'promo_discount_amount' => $promo['discount_amount'],
            'points_enabled' => $pointsPreview['enabled'],
            'points_redeeming' => false,
            'points_balance' => $pointsPreview['balance'],
            'points_balance_value' => $pointsPreview['balance_value'],
            'points_redeemed' => 0,
            'points_discount_amount' => '0.00',
            'points_per_paid_order' => $pointsPreview['points_per_paid_order'],
            'point_value_ghs' => $pointsPreview['point_value_ghs'],
            'discount_amount' => $discountAmount,
            'total' => $promo['total'],
        ];
    }
}
