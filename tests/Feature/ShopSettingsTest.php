<?php

use App\Models\Order;
use App\Models\Product;
use App\Support\Cart;
use App\Support\PhoneNumber;
use App\Support\SecondPurchaseDiscount;
use App\Support\ShopSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('shop settings override config for second purchase discount', function () {
    config([
        'shop.second_purchase_discount_enabled' => true,
        'shop.second_purchase_discount_percent' => 5,
    ]);

    app(ShopSettings::class)->putLoyalty([
        ShopSettings::SECOND_PURCHASE_ENABLED => true,
        ShopSettings::SECOND_PURCHASE_PERCENT => 10,
        ShopSettings::SECOND_PURCHASE_LABEL => 'Comeback 10% off',
    ]);

    $phone = '0244111222';

    Order::factory()->paid()->create([
        'customer_phone' => $phone,
        'customer_phone_normalized' => PhoneNumber::normalize($phone),
    ]);

    $quote = app(SecondPurchaseDiscount::class)->quote('200.00', $phone);

    expect($quote['eligible'])->toBeTrue()
        ->and($quote['discount_amount'])->toBe('20.00')
        ->and($quote['label'])->toBe('Comeback 10% off');
});

test('disabling loyalty in shop settings blocks the second purchase discount', function () {
    app(ShopSettings::class)->putLoyalty([
        ShopSettings::SECOND_PURCHASE_ENABLED => false,
        ShopSettings::SECOND_PURCHASE_PERCENT => 5,
        ShopSettings::SECOND_PURCHASE_LABEL => 'Second purchase 5% off',
    ]);

    $phone = '0244111222';

    Order::factory()->paid()->create([
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
        ->and($order->total)->toBe('200.00');
});
