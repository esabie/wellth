<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SmsCampaignStatus: string implements HasColor, HasLabel
{
    case Queued = 'queued';
    case Sending = 'sending';
    case Completed = 'completed';
    case Failed = 'failed';
    case Partial = 'partial';

    public function getLabel(): string
    {
        return match ($this) {
            self::Queued => 'Queued',
            self::Sending => 'Sending',
            self::Completed => 'Completed',
            self::Failed => 'Failed',
            self::Partial => 'Partial',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Queued => 'gray',
            self::Sending => 'info',
            self::Completed => 'success',
            self::Failed => 'danger',
            self::Partial => 'warning',
        };
    }
}
