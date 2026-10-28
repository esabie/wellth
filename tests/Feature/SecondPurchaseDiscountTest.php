<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\PromoCode;
use App\Support\Cart;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'shop.shipping_amount' => 0,
        'shop.second_purchase_discount_enabled' => true,
        'shop.second_purchase_discount_percent' => 5,
    ]);
});

test('phone blur preview applies second purchase discount when exactly one paid order exists', function () {
    $phone = '0244111222';

    Order::factory()->paid()->create([
        'customer_phone' => $phone,
        'customer_phone_normalized' => PhoneNumber::normalize($phone),
    ]);

    $product = Product::factory()->create(['price' => 20.00]);
    app(Cart::class)->add($product, 10);

    $this->from(route('checkout.create'))
        ->post(route('checkout.loyalty.preview'), ['customer_phone' => $phone])
        ->assertRedirect(route('checkout.create'));

    $this->get(route('checkout.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Checkout/Show')
            ->where('subtotal', '200.00')
            ->where('loyalty_eligible', true)
            ->where('loyalty_discount_amount', '10.00')
            ->where('discount_amount', '10.00')
            ->where('total', '190.00')
        );
});

test('pending orders do not unlock the second purchase discount', function () {
    $phone = '0244111222';

    Order::factory()->create([
        'status' => OrderStatus::Pending,
        'customer_phone' => $phone,
        'customer_phone_normalized' => PhoneNumber::normalize($phone),
    ]);

    $product = Product::factory()->create(['price' => 20.00]);
    app(Cart::class)->add($product, 10);

    $this->post(route('checkout.loyalty.preview'), ['customer_phone' => $phone]);

    $this->get(route('checkout.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('loyalty_eligible', false)
            ->where('loyalty_discount_amount', '0.00')
            ->where('total', '200.00')
        );
});

test('third paid order does not receive the second purchase discount', function () {
    $phone = '0244111222';

    Order::factory()->paid()->count(2)->create([
        'customer_phone' => $phone,
        'customer_phone_normalized' => PhoneNumber::normalize($phone),
    ]);

    $product = Product::factory()->create(['price' => 20.00]);
    app(Cart::class)->add($product, 10);

    $this->post(route('checkout.store'), [
        'customer_name' => 'Ama Mensah',
        'customer_email' => 'ama@example.com',
        'customer_phone' => $phone,
        'shipping_line1' => '12 Liberation Road',
        'shipping_city' => 'Accra',
        'shipping_region' => 'Greater Accra',
    ]);

    $order = Order::query()->latest('id')->first();

    expect($order->loyalty_discount_amount)->toBe('0.00')
        ->and($order->discount_amount)->toBe('0.00')
        ->and($order->total)->toBe('200.00');
});

test('checkout stores loyalty discount on the second paid-order purchase', function () {
    $phone = '0244111222';

    Order::factory()->paid()->create([
        'customer_phone' => $phone,
        'customer_phone_normalized' => PhoneNumber::normalize($phone),
    ]);

    $product = Product::factory()->create(['price' => 20.00]);
    app(Cart::class)->add($product, 10);

    $response = $this->post(route('checkout.store'), [
        'customer_name' => 'Ama Mensah',
        'customer_email' => 'ama@example.com',
        'customer_phone' => '+233 24 411 1222',
        'shipping_line1' => '12 Liberation Road',
        'shipping_city' => 'Accra',
        'shipping_region' => 'Greater Accra',
    ]);

    $order = Order::query()->latest('id')->first();

    expect($order)->not->toBeNull()
        ->and($order->customer_phone_normalized)->toBe('233244111222')
        ->and($order->loyalty_discount_amount)->toBe('10.00')
        ->and($order->discount_amount)->toBe('10.00')
        ->and($order->total)->toBe('190.00');

    $response->assertRedirect(route('checkout.payment', $order));
});

test('loyalty discount can stack with a promo code', function () {
    PromoCode::factory()->create([
        'code' => 'WELLTH10',
        'type' => 'percent',
        'value' => 10,
        'label' => '10% off',
    ]);

    $phone = '0244111222';

    Order::factory()->paid()->create([
        'customer_phone' => $phone,
        'customer_phone_normalized' => PhoneNumber::normalize($phone),
    ]);

    $product = Product::factory()->create(['price' => 20.00]);
    app(Cart::class)->add($product, 10);

    $this->post(route('checkout.promo.apply'), ['code' => 'WELLTH10']);

    $this->post(route('checkout.store'), [
        'customer_name' => 'Ama Mensah',
        'customer_email' => 'ama@example.com',
        'customer_phone' => $phone,
        'shipping_line1' => '12 Liberation Road',
        'shipping_city' => 'Accra',
        'shipping_region' => 'Greater Accra',
    ]);

    $order = Order::query()->latest('id')->first();

    // 5% loyalty on 200 = 10; 10% promo on remaining 190 = 19; total discount 29; total 171
    expect($order->loyalty_discount_amount)->toBe('10.00')
        ->and($order->discount_amount)->toBe('29.00')
        ->and($order->promo_code)->toBe('WELLTH10')
        ->and($order->total)->toBe('171.00');
});
