<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case AwaitingConfirmation = 'awaiting_confirmation';
    case Paid = 'paid';
    case Preparing = 'preparing';
    case Shipped = 'shipped';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Pending payment',
            self::AwaitingConfirmation => 'Awaiting confirmation',
            self::Paid => 'Paid',
            self::Preparing => 'Preparing',
            self::Shipped => 'Shipped',
            self::Failed => 'Failed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pending => 'warning',
            self::AwaitingConfirmation => 'info',
            self::Paid => 'success',
            self::Preparing => 'info',
            self::Shipped => 'primary',
            self::Failed => 'danger',
            self::Cancelled => 'gray',
        };
    }
}
