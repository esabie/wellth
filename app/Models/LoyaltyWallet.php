<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Database\Factories\LoyaltyWalletFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'phone_normalized',
    'balance',
])]
class LoyaltyWallet extends Model
{
    /** @use HasFactory<LoyaltyWalletFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'balance' => 'integer',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(LoyaltyPointTransaction::class);
    }

    /**
     * Most recent paid (or fulfilled) order for this phone.
     */
    public function latestPaidOrder(): HasOne
    {
        return $this->hasOne(Order::class, 'customer_phone_normalized', 'phone_normalized')
            ->ofMany(
                ['id' => 'max'],
                function (Builder $query): void {
                    $query->whereIn('status', [
                        OrderStatus::Paid,
                        OrderStatus::Preparing,
                        OrderStatus::Shipped,
                    ]);
                },
            );
    }

    /**
     * Most recent referral promo that recorded a name for this phone.
     */
    public function latestReferrerPromo(): HasOne
    {
        return $this->hasOne(PromoCode::class, 'referrer_phone_normalized', 'phone_normalized')
            ->ofMany(
                ['id' => 'max'],
                function (Builder $query): void {
                    $query->whereNotNull('referrer_name')
                        ->where('referrer_name', '!=', '');
                },
            );
    }

    public function customerName(): ?string
    {
        $fromOrder = $this->latestPaidOrder?->customer_name;

        if (filled($fromOrder)) {
            return (string) $fromOrder;
        }

        $fromPromo = $this->latestReferrerPromo?->referrer_name;

        return filled($fromPromo) ? (string) $fromPromo : null;
    }
}
