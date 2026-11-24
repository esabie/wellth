<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Support\Cart;
use App\Support\ShopSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('drink-only carts meet the minimum from a single bottle', function () {
    $drink = Product::factory()->drink()->create(['price' => 45.00]);
    app(Cart::class)->add($drink, 1);

    expect(app(Cart::class)->isDrinkOnlyCart())->toBeTrue()
        ->and(app(Cart::class)->minimumOrderQuantity())->toBe(1)
        ->and(app(Cart::class)->meetsMinimumOrder())->toBeTrue()
        ->and(app(Cart::class)->remainingForMinimumOrder())->toBe(0);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('cart.minimum_order_quantity', 1)
            ->where('cart.minimum_order_quantity_shop', 10)
            ->where('cart.meets_minimum_order', true)
            ->where('cart.is_drink_only', true)
            ->where('cart.items.0.product.is_drink', true)
        );
});

test('mixed carts still require the shop shot minimum excluding drinks', function () {
    app(ShopSettings::class)->putCheckout([
        ShopSettings::MINIMUM_ORDER_QUANTITY => 10,
    ]);

    $drink = Product::factory()->drink()->create(['price' => 45.00]);
    $shot = Product::factory()->create(['price' => 20.00, 'pack_units' => 1]);

    $cart = app(Cart::class);
    $cart->add($drink, 3);
    $cart->add($shot, 4);

    expect($cart->isDrinkOnlyCart())->toBeFalse()
        ->and($cart->shotUnitCount())->toBe(4)
        ->and($cart->minimumOrderQuantity())->toBe(10)
        ->and($cart->meetsMinimumOrder())->toBeFalse()
        ->and($cart->remainingForMinimumOrder())->toBe(6);

    $cart->add($shot, 6);

    expect($cart->shotUnitCount())->toBe(10)
        ->and($cart->meetsMinimumOrder())->toBeTrue();
});

test('checkout accepts a single drink bottle', function () {
    $drink = Product::factory()->drink()->create(['price' => 45.00]);
    app(Cart::class)->add($drink, 1);

    $this->post(route('checkout.store'), [
        'customer_name' => 'Ama Mensah',
        'customer_email' => 'ama@example.com',
        'customer_phone' => '0244111222',
        'shipping_line1' => '12 Liberation Road',
        'shipping_city' => 'Accra',
        'shipping_region' => 'Greater Accra',
    ])->assertRedirect();

    expect(Order::query()->count())->toBe(1);
});

test('production-style drinks category slug still skips the shot minimum', function () {
    $category = Category::factory()->create([
        'name' => 'Drinks',
        'slug' => 'drinksdrinks',
    ]);
    $drink = Product::factory()->create([
        'category_id' => $category->id,
        'price' => 45.00,
        'pack_units' => 1,
    ]);

    app(Cart::class)->add($drink, 1);

    expect($drink->isDrink())->toBeTrue()
        ->and(app(Cart::class)->isDrinkOnlyCart())->toBeTrue()
        ->and(app(Cart::class)->minimumOrderQuantity())->toBe(1)
        ->and(app(Cart::class)->meetsMinimumOrder())->toBeTrue();
});
