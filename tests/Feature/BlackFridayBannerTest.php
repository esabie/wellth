<?php

use App\Support\BlackFridayCampaign;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

afterEach(function () {
    CarbonImmutable::setTestNow();
});

function configureBlackFridayCampaign(): void
{
    config([
        'shop.black_friday.enabled' => true,
        'shop.black_friday.timezone' => 'Africa/Accra',
        'shop.black_friday.teaser_starts' => '2026-11-16',
        'shop.black_friday.sale_date' => '2026-11-27',
        'shop.black_friday.promo_code' => 'WELLTH26',
    ]);
}

test('home shares a teaser black friday banner before the sale day', function () {
    configureBlackFridayCampaign();
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-16 09:00:00', 'Africa/Accra'));

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('black_friday.phase', 'teaser')
            ->where('black_friday.sale_date_label', '27 November')
            ->where('black_friday.discount_percent', 10)
            ->where('black_friday.promo_code', 'WELLTH26')
            ->where('black_friday.href', '/shop')
        );
});

test('black friday campaign returns a live banner on the sale day', function () {
    configureBlackFridayCampaign();
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-27 12:00:00', 'Africa/Accra'));

    expect(app(BlackFridayCampaign::class)->banner())->toMatchArray([
        'phase' => 'live',
        'sale_date_label' => '27 November',
        'discount_percent' => 10,
        'promo_code' => 'WELLTH26',
        'href' => '/shop',
    ]);
});

test('black friday banner is hidden before the teaser window and after the sale', function () {
    configureBlackFridayCampaign();

    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-15 18:00:00', 'Africa/Accra'));
    expect(app(BlackFridayCampaign::class)->banner())->toBeNull();

    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-28 00:00:00', 'Africa/Accra'));
    expect(app(BlackFridayCampaign::class)->banner())->toBeNull();
});

test('black friday banner can be disabled by config', function () {
    configureBlackFridayCampaign();
    config(['shop.black_friday.enabled' => false]);
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-20 10:00:00', 'Africa/Accra'));

    expect(app(BlackFridayCampaign::class)->banner())->toBeNull();
});
