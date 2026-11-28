<?php

namespace App\Support;

use Carbon\CarbonImmutable;

class BlackFridayCampaign
{
    /**
     * Shared Inertia payload for the site-wide Black Friday banner and first-visit modal.
     *
     * @return array{phase: string, sale_date_label: string, discount_percent: int, promo_code: string, href: string}|null
     */
    public function banner(): ?array
    {
        if (! config('shop.black_friday.enabled')) {
            return null;
        }

        $now = CarbonImmutable::now($this->timezone());
        $teaserStarts = CarbonImmutable::parse((string) config('shop.black_friday.teaser_starts'), $this->timezone())->startOfDay();

        if ($now->lt($teaserStarts) || $now->gt($this->endsAt())) {
            return null;
        }

        return [
            'phase' => $this->isLive($now) ? 'live' : 'teaser',
            'sale_date_label' => $this->startsAt()->format('j F'),
            'discount_percent' => (int) config('shop.black_friday.discount_percent', 10),
            'promo_code' => strtoupper((string) config('shop.black_friday.promo_code', 'WELLTH26')),
            'href' => '/shop',
        ];
    }

    public function timezone(): string
    {
        return (string) config('shop.black_friday.timezone', 'Africa/Accra');
    }

    public function startsAt(): CarbonImmutable
    {
        return CarbonImmutable::parse((string) config('shop.black_friday.sale_date'), $this->timezone())->startOfDay();
    }

    /**
     * Last moment the sale and WELLTH26 are valid. Africa/Accra is GMT.
     */
    public function endsAt(): CarbonImmutable
    {
        return CarbonImmutable::parse((string) config('shop.black_friday.sale_ends_at'), $this->timezone());
    }

    public function isLive(?CarbonImmutable $now = null): bool
    {
        $now ??= CarbonImmutable::now($this->timezone());

        return $now->greaterThanOrEqualTo($this->startsAt()) && $now->lessThanOrEqualTo($this->endsAt());
    }
}
