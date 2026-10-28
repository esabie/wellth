<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PointsRedeemStatus;
use App\Models\Order;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 20, 120);
        $shipping = 0.00;
        $number = 'WL'.Str::upper(Str::random(8));
        $phone = '0'.fake()->numerify('##########');

        return [
            'uuid' => $number,
            'status' => OrderStatus::Pending,
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'customer_phone' => $phone,
            'customer_phone_normalized' => PhoneNumber::normalize($phone),
            'shipping_line1' => fake()->streetAddress(),
            'shipping_line2' => null,
            'shipping_city' => 'Accra',
            'shipping_region' => 'Greater Accra',
            'shipping_notes' => null,
            'subtotal' => $subtotal,
            'shipping_amount' => $shipping,
            'discount_amount' => 0,
            'loyalty_discount_amount' => 0,
            'promo_code' => null,
            'points_redeemed' => 0,
            'points_discount_amount' => 0,
            'points_earned' => null,
            'points_redeem_status' => PointsRedeemStatus::None,
            'total' => round($subtotal + $shipping, 2),
            'currency' => 'GHS',
            'paystack_reference' => $number,
            'paystack_access_code' => null,
            'paid_at' => null,
            'prepared_at' => null,
            'shipped_at' => null,
            'stock_decremented_at' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => OrderStatus::Paid,
            'paid_at' => now(),
        ]);
    }

    public function preparing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => OrderStatus::Preparing,
            'paid_at' => now()->subHour(),
            'prepared_at' => now(),
            'stock_decremented_at' => now(),
        ]);
    }

    public function shipped(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => OrderStatus::Shipped,
            'paid_at' => now()->subHours(2),
            'prepared_at' => now()->subHour(),
            'shipped_at' => now(),
            'stock_decremented_at' => now()->subHour(),
        ]);
    }
}
