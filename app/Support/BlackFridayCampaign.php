<?php

namespace App\Support;

use Carbon\CarbonImmutable;

class BlackFridayCampaign
{
    /**
     * Shared Inertia payload for the site-wide Black Friday banner.
     *
     * @return array{phase: string, sale_date_label: string, discount_percent: int, promo_code: string, href: string}|null
     */
    public function banner(): ?array
    {
        if (! config('shop.black_friday.enabled')) {
            return null;
        }

        $timezone = (string) config('shop.black_friday.timezone');
        $today = CarbonImmutable::now($timezone)->startOfDay();
        $saleDate = CarbonImmutable::parse((string) config('shop.black_friday.sale_date'), $timezone)->startOfDay();
        $teaserStarts = CarbonImmutable::parse((string) config('shop.black_friday.teaser_starts'), $timezone)->startOfDay();

        if ($today->lt($teaserStarts) || $today->gt($saleDate)) {
            return null;
        }

        $phase = $today->equalTo($saleDate) ? 'live' : 'teaser';

        return [
            'phase' => $phase,
            'sale_date_label' => $saleDate->format('j M'),
            'discount_percent' => (int) config('shop.black_friday.discount_percent'),
            'promo_code' => (string) config('shop.black_friday.promo_code'),
            'href' => '/shop',
        ];
    }
}
