<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PointsRedeemStatus: string implements HasLabel
{
    case None = 'none';
    case Reserved = 'reserved';
    case Settled = 'settled';
    case Released = 'released';

    public function getLabel(): string
    {
        return match ($this) {
            self::None => 'None',
            self::Reserved => 'Reserved',
            self::Settled => 'Settled',
            self::Released => 'Released',
        };
    }
}
