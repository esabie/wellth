<?php

use App\Actions\CompletePaidOrder;
use App\Enums\OrderStatus;
use App\Enums\PointsRedeemStatus;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Models\LoyaltyWallet;
use App\Models\Order;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\User;
use App\Support\Cart;
use App\Support\CheckoutPricing;
use App\Support\LoyaltyPoints;
use App\Support\PhoneNumber;
use App\Support\ShopSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'shop.shipping_amount' => 0,
        'shop.second_purchase_discount_enabled' => true,
        'shop.second_purchase_discount_percent' => 5,
        'shop.points_enabled' => true,
        'shop.points_per_paid_order' => 10,
        'shop.point_value_ghs' => 0.10,
        'services.mnotify.api_key' => null,
    ]);
});

function enablePoints(array $overrides = []): void
{
    app(ShopSettings::class)->putLoyalty([
        ShopSettings::SECOND_PURCHASE_ENABLED => true,
        ShopSettings::SECOND_PURCHASE_PERCENT => 5,
        ShopSettings::SECOND_PURCHASE_LABEL => 'Second purchase 5% off',
        ShopSettings::POINTS_ENABLED => true,
        ShopSettings::POINTS_PER_PAID_ORDER => $overrides['points'] ?? 10,
        ShopSettings::POINT_VALUE_GHS => $overrides['value'] ?? 0.10,
    ]);
}

test('confirming payment credits flat loyalty points once', function () {
    enablePoints(['points' => 10]);

    $phone = '0244111222';
    $order = Order::factory()->create([
        'status' => OrderStatus::AwaitingConfirmation,
        'customer_phone' => $phone,
        'customer_phone_normalized' => PhoneNumber::normalize($phone),
    ]);

    app(CompletePaidOrder::class)->handle($order);

    $wallet = LoyaltyWallet::query()
        ->where('phone_normalized', PhoneNumber::normalize($phone))
        ->first();

    expect($wallet)->not->toBeNull()
        ->and($wallet->balance)->toBe(10)
        ->and($order->fresh()->points_earned)->toBe(10);

    app(CompletePaidOrder::class)->handle($order->fresh());

    expect($wallet->fresh()->balance)->toBe(10)
        ->and($order->fresh()->points_earned)->toBe(10);
});

test('redeeming points blocks second purchase discount and promo codes', function () {
    enablePoints();

    $phone = '0244111222';

    LoyaltyWallet::factory()->create([
        'phone_normalized' => PhoneNumber::normalize($phone),
        'balance' => 100,
    ]);

    Order::factory()->paid()->create([
        'customer_phone' => $phone,
        'customer_phone_normalized' => PhoneNumber::normalize($phone),
    ]);

    PromoCode::factory()->create([
        'code' => 'WELLTH10',
        'type' => 'percent',
        'value' => 10,
    ]);

    $product = Product::factory()->create(['price' => 20.00]);
    app(Cart::class)->add($product, 10);

    $this->post(route('checkout.loyalty.preview'), ['customer_phone' => $phone]);
    $this->post(route('checkout.promo.apply'), ['code' => 'WELLTH10']);
    $this->post(route('checkout.points.apply'));

    $this->get(route('checkout.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Checkout/Show')
            ->where('points_redeeming', true)
            ->where('points_redeemed', 100)
            ->where('points_discount_amount', '10.00')
            ->where('loyalty_eligible', false)
            ->where('loyalty_discount_amount', '0.00')
            ->where('promo_code', null)
            ->where('promo_discount_amount', '0.00')
            ->where('total', '190.00')
        );
});

test('placing an order reserves points and reduces wallet balance', function () {
    enablePoints();

    $phone = '0244111222';

    LoyaltyWallet::factory()->create([
        'phone_normalized' => PhoneNumber::normalize($phone),
        'balance' => 50,
    ]);

    $product = Product::factory()->create(['price' => 20.00]);
    app(Cart::class)->add($product, 10);

    $this->post(route('checkout.loyalty.preview'), ['customer_phone' => $phone]);
    $this->post(route('checkout.points.apply'));

    $this->post(route('checkout.store'), [
        'customer_name' => 'Ama Mensah',
        'customer_email' => 'ama@example.com',
        'customer_phone' => $phone,
        'shipping_line1' => '12 Liberation Road',
        'shipping_city' => 'Accra',
        'shipping_region' => 'Greater Accra',
    ]);

    $order = Order::query()->latest('id')->first();
    $wallet = LoyaltyWallet::query()
        ->where('phone_normalized', PhoneNumber::normalize($phone))
        ->first();

    expect($order->points_redeemed)->toBe(50)
        ->and($order->points_discount_amount)->toBe('5.00')
        ->and($order->points_redeem_status)->toBe(PointsRedeemStatus::Reserved)
        ->and($order->total)->toBe('195.00')
        ->and($wallet->balance)->toBe(0);
});

test('settling a paid order finalizes redeem and still earns flat points', function () {
    enablePoints(['points' => 10]);

    $phone = '0244111222';

    LoyaltyWallet::factory()->create([
        'phone_normalized' => PhoneNumber::normalize($phone),
        'balance' => 40,
    ]);

    $product = Product::factory()->create(['price' => 20.00]);
    app(Cart::class)->add($product, 10);

    $this->post(route('checkout.loyalty.preview'), ['customer_phone' => $phone]);
    $this->post(route('checkout.points.apply'));
    $this->post(route('checkout.store'), [
        'customer_name' => 'Ama Mensah',
        'customer_email' => 'ama@example.com',
        'customer_phone' => $phone,
        'shipping_line1' => '12 Liberation Road',
        'shipping_city' => 'Accra',
        'shipping_region' => 'Greater Accra',
    ]);

    $order = Order::query()->latest('id')->first();

    app(CompletePaidOrder::class)->handle($order);

    $wallet = LoyaltyWallet::query()
        ->where('phone_normalized', PhoneNumber::normalize($phone))
        ->first();

    expect($order->fresh()->points_redeem_status)->toBe(PointsRedeemStatus::Settled)
        ->and($order->fresh()->points_earned)->toBe(10)
        ->and($wallet->balance)->toBe(10);
});

test('cancelling a reserved order releases points back to the wallet', function () {
    enablePoints();

    $phone = '0244111222';

    LoyaltyWallet::factory()->create([
        'phone_normalized' => PhoneNumber::normalize($phone),
        'balance' => 30,
    ]);

    $product = Product::factory()->create(['price' => 20.00]);
    app(Cart::class)->add($product, 10);

    $this->post(route('checkout.loyalty.preview'), ['customer_phone' => $phone]);
    $this->post(route('checkout.points.apply'));
    $this->post(route('checkout.store'), [
        'customer_name' => 'Ama Mensah',
        'customer_email' => 'ama@example.com',
        'customer_phone' => $phone,
        'shipping_line1' => '12 Liberation Road',
        'shipping_city' => 'Accra',
        'shipping_region' => 'Greater Accra',
    ]);

    $order = Order::query()->latest('id')->first();

    $this->actingAs(User::factory()->create());

    Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
        ->fillForm([
            'status' => OrderStatus::Cancelled->value,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $wallet = LoyaltyWallet::query()
        ->where('phone_normalized', PhoneNumber::normalize($phone))
        ->first();

    expect($order->fresh()->points_redeem_status)->toBe(PointsRedeemStatus::Released)
        ->and($wallet->balance)->toBe(30);
});

test('points redemption is capped by order total', function () {
    enablePoints(['value' => 1.00]);

    $phone = '0244111222';

    LoyaltyWallet::factory()->create([
        'phone_normalized' => PhoneNumber::normalize($phone),
        'balance' => 500,
    ]);

    $product = Product::factory()->create(['price' => 20.00]);
    app(Cart::class)->add($product, 10);

    $this->post(route('checkout.loyalty.preview'), ['customer_phone' => $phone]);
    $this->post(route('checkout.points.apply'));

    $quote = app(CheckoutPricing::class)->quote('200.00', '0.00', $phone);

    expect($quote['points_redeemed'])->toBe(200)
        ->and($quote['points_discount_amount'])->toBe('200.00')
        ->and($quote['total'])->toBe('0.00');
});

test('admin settings control earn rate and point value', function () {
    enablePoints(['points' => 25, 'value' => 0.20]);

    expect(app(LoyaltyPoints::class)->pointsPerPaidOrder())->toBe(25)
        ->and(app(LoyaltyPoints::class)->pointValueGhs())->toBe(0.20)
        ->and(app(LoyaltyPoints::class)->ghsValue(10))->toBe('2.00');
});
