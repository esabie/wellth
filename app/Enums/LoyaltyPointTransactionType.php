<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum LoyaltyPointTransactionType: string implements HasColor, HasLabel
{
    case Earn = 'earn';
    case ReferralEarn = 'referral_earn';
    case RedeemReserve = 'redeem_reserve';
    case RedeemSettle = 'redeem_settle';
    case RedeemRelease = 'redeem_release';
    case Adjust = 'adjust';

    public function getLabel(): string
    {
        return match ($this) {
            self::Earn => 'Earn',
            self::ReferralEarn => 'Referral earn',
            self::RedeemReserve => 'Redeem reserved',
            self::RedeemSettle => 'Redeem settled',
            self::RedeemRelease => 'Redeem released',
            self::Adjust => 'Adjustment',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Earn => 'success',
            self::ReferralEarn => 'success',
            self::RedeemReserve => 'warning',
            self::RedeemSettle => 'danger',
            self::RedeemRelease => 'info',
            self::Adjust => 'gray',
        };
    }
}
