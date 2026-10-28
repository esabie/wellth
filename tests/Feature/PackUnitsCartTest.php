<?php

use App\Models\Product;
use App\Support\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a bouquet pack counts its bottles toward the minimum order', function () {
    config(['shop.minimum_order_quantity' => 10]);

    $bouquet = Product::factory()->create([
        'name' => 'Wellth Bouquet',
        'pack_units' => 10,
        'price' => 160.00,
    ]);

    app(Cart::class)->add($bouquet, 1);

    expect(app(Cart::class)->count())->toBe(10)
        ->and(app(Cart::class)->meetsMinimumOrder())->toBeTrue();
});

test('single shots still count as one unit each', function () {
    config(['shop.minimum_order_quantity' => 10]);

    $shot = Product::factory()->create([
        'pack_units' => 1,
        'price' => 20.00,
    ]);

    app(Cart::class)->add($shot, 3);

    expect(app(Cart::class)->count())->toBe(3)
        ->and(app(Cart::class)->meetsMinimumOrder())->toBeFalse()
        ->and(app(Cart::class)->remainingForMinimumOrder())->toBe(7);
});
