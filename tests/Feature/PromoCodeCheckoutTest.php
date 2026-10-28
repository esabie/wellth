<?php

use App\Actions\CompletePaidOrder;
use App\Models\Order;
use App\Models\Product;
use App\Models\PromoCode;
use App\Support\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'shop.shipping_amount' => 0,
        'services.mnotify.api_key' => null,
    ]);

    PromoCode::factory()->create([
        'code' => 'WELLTH10',
        'type' => 'percent',
        'value' => 10,
        'label' => '10% off',
    ]);
});

test('checkout can apply a valid promo code', function () {
    $product = Product::factory()->create(['price' => 20.00]);
    app(Cart::class)->add($product, 10);

    $this->from(route('checkout.create'))
        ->post(route('checkout.promo.apply'), ['code' => 'wellth10'])
        ->assertRedirect(route('checkout.create'))
        ->assertSessionHas('success');

    $this->get(route('checkout.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Checkout/Show')
            ->where('subtotal', '200.00')
            ->where('promo_discount_amount', '20.00')
            ->where('promo_code', 'WELLTH10')
            ->where('total', '180.00')
        );
});

test('checkout rejects an invalid promo code', function () {
    $product = Product::factory()->create(['price' => 20.00]);
    app(Cart::class)->add($product, 10);

    $this->from(route('checkout.create'))
        ->post(route('checkout.promo.apply'), ['code' => 'NOPE'])
        ->assertRedirect(route('checkout.create'))
        ->assertSessionHasErrors('promo_code');
});

test('checkout rejects promo codes below the minimum order amount', function () {
    PromoCode::factory()->create([
        'code' => 'BIGORDER',
        'type' => 'percent',
        'value' => 15,
        'min_order_amount' => 500,
    ]);

    $product = Product::factory()->create(['price' => 20.00]);
    app(Cart::class)->add($product, 10);

    $this->from(route('checkout.create'))
        ->post(route('checkout.promo.apply'), ['code' => 'BIGORDER'])
        ->assertRedirect(route('checkout.create'))
        ->assertSessionHasErrors('promo_code');
});

test('checkout order total includes the applied promo discount', function () {
    $product = Product::factory()->create(['price' => 20.00]);
    app(Cart::class)->add($product, 10);

    $this->post(route('checkout.promo.apply'), ['code' => 'WELLTH10']);

    $response = $this->post(route('checkout.store'), [
        'customer_name' => 'Ama Mensah',
        'customer_email' => 'ama@example.com',
        'customer_phone' => '0244111222',
        'shipping_line1' => '12 Liberation Road',
        'shipping_city' => 'Accra',
        'shipping_region' => 'Greater Accra',
    ]);

    $order = Order::query()->first();

    expect($order)->not->toBeNull()
        ->and($order->promo_code)->toBe('WELLTH10')
        ->and($order->discount_amount)->toBe('20.00')
        ->and($order->subtotal)->toBe('200.00')
        ->and($order->total)->toBe('180.00');

    $response->assertRedirect(route('checkout.payment', $order));
});

test('placing an order clears the promo so the next checkout starts clean', function () {
    $product = Product::factory()->create(['price' => 20.00]);
    app(Cart::class)->add($product, 10);

    $this->post(route('checkout.promo.apply'), ['code' => 'WELLTH10']);

    $this->post(route('checkout.store'), [
        'customer_name' => 'Ama Mensah',
        'customer_email' => 'ama@example.com',
        'customer_phone' => '0244111222',
        'shipping_line1' => '12 Liberation Road',
        'shipping_city' => 'Accra',
        'shipping_region' => 'Greater Accra',
    ]);

    expect(session('checkout_promo_code'))->toBeNull();

    app(Cart::class)->add($product, 10);

    $this->get(route('checkout.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('promo_code', null)
            ->where('promo_discount_amount', '0.00')
            ->where('total', '200.00')
        );
});

test('emptying the cart clears an applied promo code', function () {
    $product = Product::factory()->create(['price' => 20.00]);
    app(Cart::class)->add($product, 10);

    $this->post(route('checkout.promo.apply'), ['code' => 'WELLTH10']);
    expect(session('checkout_promo_code'))->toBe('WELLTH10');

    app(Cart::class)->clear();

    expect(session('checkout_promo_code'))->toBeNull();
});

test('checkout can remove an applied promo code', function () {
    $product = Product::factory()->create(['price' => 20.00]);
    app(Cart::class)->add($product, 10);

    $this->post(route('checkout.promo.apply'), ['code' => 'WELLTH10']);

    $this->from(route('checkout.create'))
        ->delete(route('checkout.promo.remove'))
        ->assertRedirect(route('checkout.create'));

    $this->get(route('checkout.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('promo_code', null)
            ->where('promo_discount_amount', '0.00')
            ->where('total', '200.00')
        );
});

test('confirming payment increments the promo code used count', function () {
    $promo = PromoCode::query()->where('code', 'WELLTH10')->firstOrFail();

    $order = Order::factory()->create([
        'promo_code' => 'WELLTH10',
        'discount_amount' => 20,
    ]);

    app(CompletePaidOrder::class)->handle($order);

    expect($promo->fresh()->used_count)->toBe(1);
});
