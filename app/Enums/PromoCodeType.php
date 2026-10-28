<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PromoCodeType: string implements HasLabel
{
    case Percent = 'percent';
    case Fixed = 'fixed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Percent => 'Percentage',
            self::Fixed => 'Fixed amount',
        };
    }
}
