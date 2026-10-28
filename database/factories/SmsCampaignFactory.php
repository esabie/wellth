<?php

namespace Database\Factories;

use App\Enums\SmsCampaignAudience;
use App\Enums\SmsCampaignStatus;
use App\Models\SmsCampaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SmsCampaign>
 */
class SmsCampaignFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $message = fake()->sentence(12);
        $phones = ['0244111222', '0200000111'];

        return [
            'user_id' => User::factory(),
            'audience' => SmsCampaignAudience::AllCustomers,
            'message' => $message,
            'sender_id' => 'Wellth',
            'status' => SmsCampaignStatus::Completed,
            'recipients_count' => count($phones),
            'sent_count' => count($phones),
            'failed_count' => 0,
            'recipient_phones' => $phones,
            'meta' => [
                'batches' => [
                    [
                        'recipients' => count($phones),
                        'status' => 'success',
                        'campaign_id' => fake()->uuid(),
                    ],
                ],
            ],
            'error_message' => null,
            'sent_at' => now(),
        ];
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SmsCampaignStatus::Failed,
            'sent_count' => 0,
            'failed_count' => $attributes['recipients_count'] ?? 1,
            'error_message' => 'Unable to send SMS via mNotify.',
        ]);
    }

    public function customPhones(?array $phones = null): static
    {
        $phones ??= ['0241234567', '0209876543'];

        return $this->state(fn (array $attributes): array => [
            'audience' => SmsCampaignAudience::CustomPhones,
            'recipients_count' => count($phones),
            'sent_count' => count($phones),
            'failed_count' => 0,
            'recipient_phones' => $phones,
        ]);
    }
}
