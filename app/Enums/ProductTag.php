<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProductTag: string implements HasColor, HasLabel
{
    case New = 'new';
    case ComingSoon = 'coming_soon';

    public function getLabel(): string
    {
        return match ($this) {
            self::New => 'New',
            self::ComingSoon => 'Coming Soon',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'success',
            self::ComingSoon => 'warning',
        };
    }
}
