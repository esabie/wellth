<?php

namespace App\Support;

use App\Enums\PromoCodeType;
use App\Models\PromoCode;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PromoCodes
{
    private const SESSION_KEY = 'checkout_promo_code';

    public function find(string $code): ?PromoCode
    {
        $key = Str::upper(trim($code));

        if ($key === '') {
            return null;
        }

        return PromoCode::query()->where('code', $key)->first();
    }

    public function appliedCode(): ?string
    {
        $code = Session::get(self::SESSION_KEY);

        if (! is_string($code) || $code === '') {
            return null;
        }

        $promo = $this->find($code);

        return $promo?->code;
    }

    public function applied(): ?PromoCode
    {
        $code = $this->appliedCode();

        return $code !== null ? $this->find($code) : null;
    }

    /**
     * @return array{type: string, value: float, label: string}
     */
    public function apply(string $code, ?string $subtotal = null, ?string $customerPhone = null): array
    {
        $promo = $this->find($code);

        if ($promo === null || ! $promo->is_active) {
            throw new InvalidArgumentException('That promo code is not valid.');
        }

        if (! $promo->hasRemainingUses()) {
            throw new InvalidArgumentException('That promo code has reached its usage limit.');
        }

        $amount = $subtotal ?? app(Cart::class)->subtotal();

        if (! $promo->meetsMinimumOrder($amount)) {
            throw new InvalidArgumentException(
                'This promo requires a minimum order of GHS '.number_format((float) $promo->min_order_amount, 2).'.',
            );
        }

        $phone = $customerPhone ?? app(SecondPurchaseDiscount::class)->previewPhone();
        $this->assertNotSelfReferral($promo, $phone);

        Session::put(self::SESSION_KEY, $promo->code);

        return $this->definitionFromModel($promo);
    }

    public function assertNotSelfReferral(PromoCode $promo, ?string $customerPhone): void
    {
        if ($promo->isOwnedByPhone($customerPhone)) {
            throw new InvalidArgumentException('You can’t use your own referral code.');
        }
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    public function recordRedemption(?string $code): void
    {
        if (blank($code)) {
            return;
        }

        $promo = $this->find($code);

        if ($promo === null) {
            return;
        }

        $promo->increment('used_count');
    }

    /**
     * @return array{code: string|null, label: string|null, discount_amount: string, total: string}
     */
    public function quote(string $subtotal, string $shippingAmount = '0.00', ?string $customerPhone = null): array
    {
        $code = $this->appliedCode();
        $promo = $code !== null ? $this->find($code) : null;
        $discount = '0.00';
        $label = null;

        if ($promo !== null && $promo->isUsableForSubtotal($subtotal)) {
            $phone = $customerPhone ?? app(SecondPurchaseDiscount::class)->previewPhone();

            if ($promo->isOwnedByPhone($phone)) {
                $this->clear();
                $promo = null;
                $code = null;
            } else {
                $discount = $this->discountAmount($promo, $subtotal);
                $label = $promo->discountLabel();
                $code = $promo->code;
            }
        } else {
            $code = null;
        }

        $afterDiscount = bcsub($subtotal, $discount, 2);
        $total = bcadd($afterDiscount, $shippingAmount, 2);

        return [
            'code' => $code,
            'label' => $label,
            'discount_amount' => $discount,
            'total' => $total,
        ];
    }

    /**
     * @return array{type: string, value: float, label: string}
     */
    private function definitionFromModel(PromoCode $promo): array
    {
        return [
            'type' => $promo->type->value,
            'value' => (float) $promo->value,
            'label' => $promo->discountLabel(),
        ];
    }

    private function discountAmount(PromoCode $promo, string $subtotal): string
    {
        if ($promo->type === PromoCodeType::Fixed) {
            return number_format(min((float) $subtotal, (float) $promo->value), 2, '.', '');
        }

        return number_format(
            min((float) $subtotal, ((float) $subtotal) * ((float) $promo->value / 100)),
            2,
            '.',
            '',
        );
    }
}
