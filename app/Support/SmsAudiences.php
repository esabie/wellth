<?php

namespace App\Support;

use App\Enums\SmsCampaignAudience;
use App\Models\Order;

class SmsAudiences
{
    /**
     * Unique SMS-ready phone numbers for the selected audience.
     *
     * @param  list<string>|null  $customPhones
     * @return list<string>
     */
    public function phones(SmsCampaignAudience $audience, ?array $customPhones = null): array
    {
        if ($audience === SmsCampaignAudience::CustomPhones) {
            return array_values(array_unique(array_filter(
                $customPhones ?? [],
                fn (string $phone): bool => $this->isValidGhanaSmsPhone($phone),
            )));
        }

        return Order::query()
            ->whereNotNull('customer_phone')
            ->where('customer_phone', '!=', '')
            ->orderByDesc('id')
            ->pluck('customer_phone')
            ->map(fn (mixed $phone): string => PhoneNumber::forSms((string) $phone))
            ->filter(fn (string $phone): bool => $this->isValidGhanaSmsPhone($phone))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  list<string>|null  $customPhones
     */
    public function count(SmsCampaignAudience $audience, ?array $customPhones = null): int
    {
        return count($this->phones($audience, $customPhones));
    }

    /**
     * Parse pasted Ghana numbers (lines, commas, or spaces) into SMS-ready phones.
     *
     * @return list<string>
     */
    public function parseCustomPhones(string $input): array
    {
        $parts = preg_split('/[\s,;]+/', $input) ?: [];

        return collect($parts)
            ->map(fn (mixed $part): string => trim((string) $part))
            ->filter(fn (string $part): bool => filled($part))
            ->map(fn (string $part): string => PhoneNumber::forSms($part))
            ->filter(fn (string $phone): bool => $this->isValidGhanaSmsPhone($phone))
            ->unique()
            ->values()
            ->all();
    }

    public function helperText(SmsCampaignAudience $audience): string
    {
        if ($audience === SmsCampaignAudience::CustomPhones) {
            return $audience->getDescription() ?? 'Paste one or more Ghana phone numbers.';
        }

        $count = $this->count($audience);
        $description = $audience->getDescription() ?? '';
        $recipientLabel = $count === 1 ? '1 recipient' : "{$count} recipients";

        return trim("{$description} Currently {$recipientLabel}.");
    }

    public function isValidGhanaSmsPhone(string $phone): bool
    {
        return (bool) preg_match('/^0\d{9}$/', $phone);
    }
}
