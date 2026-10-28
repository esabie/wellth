<?php

namespace App\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum SmsCampaignAudience: string implements HasDescription, HasLabel
{
    case AllCustomers = 'all_customers';
    case CustomPhones = 'custom_phones';

    /** @deprecated Kept so historical campaigns still cast cleanly. */
    case PaidCustomers = 'paid_customers';

    /** @deprecated Kept so historical campaigns still cast cleanly. */
    case AwaitingConfirmation = 'awaiting_confirmation';

    /** @deprecated Kept so historical campaigns still cast cleanly. */
    case PendingPayment = 'pending_payment';

    /** @deprecated Kept so historical campaigns still cast cleanly. */
    case PreparingOrShipped = 'preparing_or_shipped';

    public function getLabel(): string
    {
        return match ($this) {
            self::AllCustomers => 'All customers',
            self::CustomPhones => 'Custom phone numbers',
            self::PaidCustomers => 'Paid customers',
            self::AwaitingConfirmation => 'Awaiting confirmation',
            self::PendingPayment => 'Pending payment',
            self::PreparingOrShipped => 'Preparing or shipped',
        };
    }

    public function getDescription(): ?string
    {
        return match ($this) {
            self::AllCustomers => 'Unique phone numbers from every order.',
            self::CustomPhones => 'Paste one or more Ghana phone numbers.',
            default => null,
        };
    }

    /**
     * Audiences available when composing a new campaign.
     *
     * @return list<self>
     */
    public static function composeOptions(): array
    {
        return [
            self::AllCustomers,
            self::CustomPhones,
        ];
    }
}
