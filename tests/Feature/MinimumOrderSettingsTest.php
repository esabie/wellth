<?php

use App\Filament\Pages\ManageShopSettings;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\Cart;
use App\Support\ShopSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('admins can open and save the minimum order quantity', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(ManageShopSettings::class)
        ->assertOk()
        ->assertFormSet([
            ShopSettings::MINIMUM_ORDER_QUANTITY => 10,
            ShopSettings::REFERRAL_POINTS_PER_ORDER => 10,
            ShopSettings::ADMIN_NOTIFICATION_EMAIL => '',
            ShopSettings::ADMIN_NOTIFICATION_PHONE => '',
        ])
        ->fillForm([
            ShopSettings::MINIMUM_ORDER_QUANTITY => 6,
            ShopSettings::REFERRAL_POINTS_PER_ORDER => 10,
            ShopSettings::ADMIN_NOTIFICATION_EMAIL => 'ops@wellth.test',
            ShopSettings::ADMIN_NOTIFICATION_PHONE => '0244000111',
        ])
        ->call('save')
        ->assertNotified();

    expect(app(ShopSettings::class)->minimumOrderQuantity())->toBe(6)
        ->and(app(ShopSettings::class)->referralPointsPerOrder())->toBe(10)
        ->and(app(ShopSettings::class)->adminNotificationEmail())->toBe('ops@wellth.test')
        ->and(app(ShopSettings::class)->adminNotificationPhone())->toBe('0244000111');
});

test('cart uses the admin-configured minimum order quantity', function () {
    app(ShopSettings::class)->putCheckout([
        ShopSettings::MINIMUM_ORDER_QUANTITY => 4,
    ]);

    $product = Product::factory()->create(['price' => 20.00]);
    app(Cart::class)->add($product, 2);

    expect(app(Cart::class)->minimumOrderQuantity())->toBe(4)
        ->and(app(Cart::class)->meetsMinimumOrder())->toBeFalse()
        ->and(app(Cart::class)->remainingForMinimumOrder())->toBe(2);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('cart.minimum_order_quantity', 4)
            ->where('cart.meets_minimum_order', false)
            ->where('cart.remaining_for_minimum_order', 2)
        );
});

test('checkout allows placing an order once the configured minimum is met', function () {
    app(ShopSettings::class)->putCheckout([
        ShopSettings::MINIMUM_ORDER_QUANTITY => 5,
    ]);

    $product = Product::factory()->create(['price' => 20.00]);
    app(Cart::class)->add($product, 5);

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
