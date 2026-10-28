<?php

namespace Database\Factories;

use App\Models\LoyaltyWallet;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoyaltyWallet>
 */
class LoyaltyWalletFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $phone = '0'.fake()->numerify('##########');

        return [
            'phone_normalized' => PhoneNumber::normalize($phone),
            'balance' => fake()->numberBetween(0, 200),
        ];
    }

    public function withBalance(int $balance): static
    {
        return $this->state(fn (array $attributes): array => [
            'balance' => max(0, $balance),
        ]);
    }
}
