<?php

use App\Support\ShopSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('shop settings fall back to config when the settings table is missing', function () {
    config(['shop.minimum_order_quantity' => 10]);

    Schema::drop('settings');
    app(ShopSettings::class)->forgetCache();

    expect(app(ShopSettings::class)->minimumOrderQuantity())->toBe(10);

    $this->get('/faqs')->assertOk();
});
