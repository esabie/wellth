<?php

namespace App\Models;

use App\Enums\PromoCodeType;
use App\Support\PhoneNumber;
use Database\Factories\PromoCodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable([
    'code',
    'type',
    'value',
    'label',
    'min_order_amount',
    'max_uses',
    'used_count',
    'is_active',
    'referrer_phone',
    'referrer_phone_normalized',
    'referrer_name',
])]
class PromoCode extends Model
{
    /** @use HasFactory<PromoCodeFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (PromoCode $promoCode): void {
            $promoCode->code = Str::upper(trim((string) $promoCode->code));

            if (blank($promoCode->referrer_phone)) {
                $promoCode->referrer_phone = null;
                $promoCode->referrer_phone_normalized = null;
                $promoCode->referrer_name = null;

                return;
            }

            $promoCode->referrer_phone = trim((string) $promoCode->referrer_phone);
            $promoCode->referrer_phone_normalized = PhoneNumber::normalize($promoCode->referrer_phone) ?: null;
            $promoCode->referrer_name = filled($promoCode->referrer_name)
                ? trim((string) $promoCode->referrer_name)
                : null;
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PromoCodeType::class,
            'value' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'max_uses' => 'integer',
            'used_count' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function discountLabel(): string
    {
        if (filled($this->label)) {
            return (string) $this->label;
        }

        return $this->type === PromoCodeType::Fixed
            ? number_format((float) $this->value, 2).' off'
            : rtrim(rtrim(number_format((float) $this->value, 2), '0'), '.').'% off';
    }

    public function hasRemainingUses(): bool
    {
        return $this->max_uses === null || $this->used_count < $this->max_uses;
    }

    public function meetsMinimumOrder(string $subtotal): bool
    {
        if ($this->min_order_amount === null) {
            return true;
        }

        return (float) $subtotal >= (float) $this->min_order_amount;
    }

    public function isUsableForSubtotal(string $subtotal): bool
    {
        return $this->is_active && $this->hasRemainingUses() && $this->meetsMinimumOrder($subtotal);
    }

    public function hasReferrer(): bool
    {
        return filled($this->referrer_phone_normalized);
    }

    public function isOwnedByPhone(?string $phone): bool
    {
        if (! $this->hasReferrer() || blank($phone)) {
            return false;
        }

        return PhoneNumber::normalize((string) $phone) === $this->referrer_phone_normalized;
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
