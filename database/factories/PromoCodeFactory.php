<?php

namespace Database\Factories;

use App\Enums\PromoCodeType;
use App\Models\PromoCode;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PromoCode>
 */
class PromoCodeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('SAVE##')),
            'type' => PromoCodeType::Percent,
            'value' => 10,
            'label' => '10% off',
            'min_order_amount' => null,
            'max_uses' => null,
            'used_count' => 0,
            'is_active' => true,
            'referrer_phone' => null,
            'referrer_phone_normalized' => null,
            'referrer_name' => null,
        ];
    }

    public function fixed(float $amount = 20): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => PromoCodeType::Fixed,
            'value' => $amount,
            'label' => number_format($amount, 2).' off',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function forReferrer(string $phone, ?string $name = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'referrer_phone' => $phone,
            'referrer_phone_normalized' => PhoneNumber::normalize($phone),
            'referrer_name' => $name,
        ]);
    }
}
