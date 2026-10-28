<?php

namespace App\Models;

use App\Enums\LoyaltyPointTransactionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'loyalty_wallet_id',
    'order_id',
    'type',
    'points',
    'balance_after',
    'note',
])]
class LoyaltyPointTransaction extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => LoyaltyPointTransactionType::class,
            'points' => 'integer',
            'balance_after' => 'integer',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(LoyaltyWallet::class, 'loyalty_wallet_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
