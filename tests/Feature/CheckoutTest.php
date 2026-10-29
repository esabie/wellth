<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Support\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'shop.shipping_amount' => 0,
        'shop.momo_number' => '233545365434',
        'shop.momo_display_number' => '+233 54 536 5434',
        'shop.momo_account_name' => 'Wellth',
        'shop.momo_network' => 'MTN MoMo',
        'services.mnotify.api_key' => null,
    ]);
});

test('checkout page requires cart items', function () {
    $this->get(route('checkout.create'))
        ->assertRedirect(route('shop.index'))
        ->assertSessionHas('error');
});

test('checkout page requires the minimum order quantity', function () {
    $product = Product::factory()->create([
        'name' => 'Ginja',
        'slug' => 'ginja',
        'price' => 20.00,
    ]);

    app(Cart::class)->add($product, 4);

    $this->get(route('checkout.create'))
        ->assertRedirect(route('cart.index'))
        ->assertSessionHas('error', 'Minimum order is 10 shots. Add 6 more to continue to checkout.');
});

test('checkout page shows cart summary with free delivery', function () {
    $product = Product::factory()->create([
        'name' => 'Ginja',
        'slug' => 'ginja',
        'price' => 20.00,
        'image_path' => '/images/formulas/ginger.jpg',
    ]);

    app(Cart::class)->add($product, 10);

    $this->get(route('checkout.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Checkout/Show')
            ->has('items', 1)
            ->where('subtotal', '200.00')
            ->where('shipping_amount', '0.00')
            ->where('total', '200.00')
            ->has('locations.Greater Accra')
        );
});

test('checkout creates a pending order and redirects to momo payment instructions', function () {
    $product = Product::factory()->create([
        'name' => 'Ginja',
        'slug' => 'ginja',
        'price' => 20.00,
        'sku' => 'WL-GINJA',
    ]);

    app(Cart::class)->add($product, 10);

    $response = $this->post(route('checkout.store'), [
        'customer_name' => 'Ama Mensah',
        'customer_phone' => '0244111222',
        'shipping_line1' => '12 Liberation Road',
        'shipping_city' => 'Accra',
        'shipping_region' => 'Greater Accra',
    ]);

    $order = Order::query()->first();

    expect($order)->not->toBeNull()
        ->and($order->status)->toBe(OrderStatus::Pending)
        ->and($order->customer_email)->toBeNull()
        ->and($order->total)->toBe('200.00')
        ->and($order->shipping_amount)->toBe('0.00')
        ->and($order->items)->toHaveCount(1)
        ->and(app(Cart::class)->count())->toBe(0);

    $response->assertRedirect(route('checkout.payment', $order));
});

test('checkout store rejects carts below the minimum order quantity', function () {
    $product = Product::factory()->create([
        'name' => 'Ginja',
        'slug' => 'ginja',
        'price' => 20.00,
    ]);

    app(Cart::class)->add($product, 3);

    $this->post(route('checkout.store'), [
        'customer_name' => 'Ama Mensah',
        'customer_phone' => '0244111222',
        'shipping_line1' => '12 Liberation Road',
        'shipping_city' => 'Accra',
        'shipping_region' => 'Greater Accra',
    ])
        ->assertRedirect(route('cart.index'))
        ->assertSessionHas('error', 'Minimum order is 10 shots. Add 7 more to continue to checkout.');

    expect(Order::query()->count())->toBe(0);
});

test('checkout validates required customer fields', function () {
    $product = Product::factory()->create(['price' => 20.00]);
    app(Cart::class)->add($product, 10);

    $this->from(route('checkout.create'))
        ->post(route('checkout.store'), [])
        ->assertRedirect(route('checkout.create'))
        ->assertSessionHasErrors([
            'customer_name',
            'customer_phone',
            'shipping_line1',
            'shipping_city',
            'shipping_region',
        ]);
});
