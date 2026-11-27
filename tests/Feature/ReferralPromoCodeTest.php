<?php

use App\Actions\CompletePaidOrder;
use App\Enums\LoyaltyPointTransactionType;
use App\Enums\OrderStatus;
use App\Filament\Pages\ManageShopSettings;
use App\Models\LoyaltyWallet;
use App\Models\Order;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\User;
use App\Notifications\Channels\MnotifyChannel;
use App\Notifications\ReferralRewardSmsNotification;
use App\Support\Cart;
use App\Support\PhoneNumber;
use App\Support\ShopSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'shop.shipping_amount' => 0,
        'shop.referral_points_per_order' => 10,
        'services.mnotify.api_key' => null,
    ]);
});

test('admins can save global referral points per order', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(ManageShopSettings::class)
        ->assertOk()
        ->assertFormSet([
            ShopSettings::MINIMUM_ORDER_QUANTITY => 10,
            ShopSettings::REFERRAL_POINTS_PER_ORDER => 10,
        ])
        ->fillForm([
            ShopSettings::MINIMUM_ORDER_QUANTITY => 10,
            ShopSettings::REFERRAL_POINTS_PER_ORDER => 25,
        ])
        ->call('save')
        ->assertNotified();

    expect(app(ShopSettings::class)->referralPointsPerOrder())->toBe(25);
});

test('shoppers can apply a referral promo code that is not their own', function () {
    $promo = PromoCode::factory()->forReferrer('0244111222')->create([
        'code' => 'AMA10',
        'type' => 'percent',
        'value' => 10,
    ]);

    $product = Product::factory()->create(['price' => 20.00]);
    app(Cart::class)->add($product, 10);

    $this->post(route('checkout.loyalty.preview'), ['customer_phone' => '0555000111']);
    $this->post(route('checkout.promo.apply'), ['code' => 'AMA10'])
        ->assertRedirect(route('checkout.create'))
        ->assertSessionHas('success');

    $this->post(route('checkout.store'), [
        'customer_name' => 'Kofi Buyer',
        'customer_email' => 'kofi@example.com',
        'customer_phone' => '0555000111',
        'shipping_line1' => '12 Liberation Road',
        'shipping_city' => 'Accra',
        'shipping_region' => 'Greater Accra',
    ])->assertRedirect();

    $order = Order::query()->latest('id')->first();

    expect($order->promo_code)->toBe('AMA10')
        ->and($order->discount_amount)->toBe('20.00')
        ->and($order->total)->toBe('180.00')
        ->and($promo->fresh()->referrer_phone_normalized)->toBe(PhoneNumber::normalize('0244111222'));
});

test('self referral promo codes are rejected at apply and checkout', function () {
    PromoCode::factory()->forReferrer('0244111222')->create([
        'code' => 'AMA10',
        'type' => 'percent',
        'value' => 10,
    ]);

    $product = Product::factory()->create(['price' => 20.00]);
    app(Cart::class)->add($product, 10);

    $this->post(route('checkout.loyalty.preview'), ['customer_phone' => '0244111222']);

    $this->from(route('checkout.create'))
        ->post(route('checkout.promo.apply'), ['code' => 'AMA10'])
        ->assertRedirect(route('checkout.create'))
        ->assertSessionHasErrors('promo_code');

    $this->post(route('checkout.promo.apply'), ['code' => 'AMA10']);

    // Apply without preview phone succeeds into session, but place order with own phone fails.
    app(Cart::class)->add($product, 10);
    session()->forget('checkout_loyalty_phone');

    $this->post(route('checkout.promo.apply'), ['code' => 'AMA10'])
        ->assertSessionHas('success');

    $this->from(route('checkout.create'))
        ->post(route('checkout.store'), [
            'customer_name' => 'Ama Referrer',
            'customer_email' => 'ama@example.com',
            'customer_phone' => '0244111222',
            'shipping_line1' => '12 Liberation Road',
            'shipping_city' => 'Accra',
            'shipping_region' => 'Greater Accra',
        ])
        ->assertRedirect(route('checkout.create'))
        ->assertSessionHas('error');

    expect(Order::query()->count())->toBe(0);
});

test('confirming payment credits referrer points once and sends sms', function () {
    Notification::fake();
    config(['services.mnotify.api_key' => 'test-key']);

    app(ShopSettings::class)->putCheckout([
        ShopSettings::MINIMUM_ORDER_QUANTITY => 10,
        ShopSettings::REFERRAL_POINTS_PER_ORDER => 15,
    ]);

    $referrerPhone = '0244111222';

    PromoCode::factory()->forReferrer($referrerPhone)->create([
        'code' => 'AMA10',
        'type' => 'percent',
        'value' => 10,
    ]);

    LoyaltyWallet::factory()->create([
        'phone_normalized' => PhoneNumber::normalize($referrerPhone),
        'balance' => 5,
    ]);

    $order = Order::factory()->create([
        'status' => OrderStatus::AwaitingConfirmation,
        'customer_phone' => '0555000111',
        'customer_phone_normalized' => PhoneNumber::normalize('0555000111'),
        'promo_code' => 'AMA10',
        'discount_amount' => 20,
        'total' => 180,
    ]);

    app(CompletePaidOrder::class)->handle($order);

    $wallet = LoyaltyWallet::query()
        ->where('phone_normalized', PhoneNumber::normalize($referrerPhone))
        ->first();

    expect($wallet->balance)->toBe(20)
        ->and($wallet->transactions()->where('type', LoyaltyPointTransactionType::ReferralEarn)->count())->toBe(1);

    Notification::assertSentOnDemand(
        ReferralRewardSmsNotification::class,
        function (ReferralRewardSmsNotification $notification, array $channels, object $notifiable): bool {
            $message = $notification->toMnotify($notifiable);

            return str_contains($message, '15 points')
                && str_contains($message, 'balance is now 20 points')
                && str_contains($message, 'AMA10')
                && in_array(MnotifyChannel::class, $channels, true);
        },
    );

    app(CompletePaidOrder::class)->handle($order->fresh());

    expect($wallet->fresh()->balance)->toBe(20)
        ->and($wallet->transactions()->where('type', LoyaltyPointTransactionType::ReferralEarn)->count())->toBe(1);
});

test('referral reward is skipped when referral points setting is zero', function () {
    Notification::fake();
    config(['services.mnotify.api_key' => 'test-key']);

    app(ShopSettings::class)->putCheckout([
        ShopSettings::MINIMUM_ORDER_QUANTITY => 10,
        ShopSettings::REFERRAL_POINTS_PER_ORDER => 0,
    ]);

    PromoCode::factory()->forReferrer('0244111222')->create([
        'code' => 'AMA10',
    ]);

    $order = Order::factory()->create([
        'status' => OrderStatus::AwaitingConfirmation,
        'customer_phone' => '0555000111',
        'customer_phone_normalized' => PhoneNumber::normalize('0555000111'),
        'promo_code' => 'AMA10',
    ]);

    app(CompletePaidOrder::class)->handle($order);

    expect(LoyaltyWallet::query()->count())->toBe(0);

    Notification::assertNotSentTo(
        Notification::route(MnotifyChannel::class, PhoneNumber::forSms('0244111222')),
        ReferralRewardSmsNotification::class,
    );
});
