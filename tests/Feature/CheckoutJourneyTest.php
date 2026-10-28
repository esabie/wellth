<?php

use App\Enums\LoyaltyPointTransactionType;
use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Models\LoyaltyWallet;
use App\Models\Order;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\User;
use App\Notifications\OrderPaidCustomerNotification;
use App\Notifications\OrderPaidCustomerSmsNotification;
use App\Notifications\OrderPaymentProofSubmittedNotification;
use App\Notifications\OrderPlacedAdminNotification;
use App\Notifications\ReferralRewardSmsNotification;
use App\Support\PhoneNumber;
use App\Support\ShopSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Notification::fake();

    config([
        'shop.shipping_amount' => 0,
        'shop.admin_notification_email' => 'ops@wellth.test',
        'shop.momo_display_number' => '+233 54 536 5434',
        'shop.momo_account_name' => 'Wellth',
        'shop.momo_network' => 'MTN MoMo',
        'shop.points_enabled' => true,
        'shop.points_per_paid_order' => 10,
        'shop.point_value_ghs' => 0.10,
        'services.mnotify.api_key' => 'test-key',
    ]);

    app(ShopSettings::class)->putLoyalty([
        ShopSettings::SECOND_PURCHASE_ENABLED => false,
        ShopSettings::SECOND_PURCHASE_PERCENT => 0,
        ShopSettings::SECOND_PURCHASE_LABEL => '',
        ShopSettings::POINTS_ENABLED => true,
        ShopSettings::POINTS_PER_PAID_ORDER => 10,
        ShopSettings::POINT_VALUE_GHS => 0.10,
    ]);

    app(ShopSettings::class)->putCheckout([
        ShopSettings::MINIMUM_ORDER_QUANTITY => 10,
        ShopSettings::REFERRAL_POINTS_PER_ORDER => 15,
        ShopSettings::ADMIN_NOTIFICATION_EMAIL => 'ops@wellth.test',
        ShopSettings::ADMIN_NOTIFICATION_PHONE => '0244000111',
    ]);
});

test('full buy path awards buyer and referrer points after momo proof and admin confirm', function () {
    $buyerPhone = '0555000111';
    $referrerPhone = '0244111222';

    User::factory()->create();

    PromoCode::factory()->forReferrer($referrerPhone, 'Ama Referrer')->create([
        'code' => 'AMA10',
        'type' => 'percent',
        'value' => 10,
    ]);

    $product = Product::factory()->create([
        'price' => 20.00,
        'stock_quantity' => 100,
    ]);

    $this->post(route('cart.store'), [
        'product_id' => $product->id,
        'quantity' => 10,
    ])->assertRedirect();

    $this->post(route('checkout.promo.apply'), ['code' => 'AMA10'])
        ->assertSessionHas('success');

    $this->post(route('checkout.store'), [
        'customer_name' => 'Kofi Buyer',
        'customer_email' => 'kofi@example.com',
        'customer_phone' => $buyerPhone,
        'shipping_line1' => '12 Liberation Road',
        'shipping_city' => 'Accra',
        'shipping_region' => 'Greater Accra',
    ])->assertRedirect();

    $order = Order::query()->latest('id')->first();

    expect($order)->not->toBeNull()
        ->and($order->status)->toBe(OrderStatus::Pending)
        ->and($order->promo_code)->toBe('AMA10')
        ->and($order->total)->toBe('180.00');

    Notification::assertSentOnDemand(OrderPlacedAdminNotification::class);

    $this->post(route('checkout.payment.store', $order), [
        'payment_proof' => UploadedFile::fake()->image('momo-proof.jpg', 800, 1200),
    ])->assertRedirect(route('checkout.confirmation', $order));

    expect($order->fresh()->status)->toBe(OrderStatus::AwaitingConfirmation);

    Notification::assertSentOnDemand(OrderPaymentProofSubmittedNotification::class);

    $this->actingAs(User::factory()->create());

    Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
        ->callAction('confirmPayment')
        ->assertHasNoActionErrors();

    $order->refresh();

    expect($order->status)->toBe(OrderStatus::Paid)
        ->and($order->paid_at)->not->toBeNull()
        ->and($order->points_earned)->toBe(10);

    $buyerWallet = LoyaltyWallet::query()
        ->where('phone_normalized', PhoneNumber::normalize($buyerPhone))
        ->first();

    $referrerWallet = LoyaltyWallet::query()
        ->where('phone_normalized', PhoneNumber::normalize($referrerPhone))
        ->first();

    expect($buyerWallet)->not->toBeNull()
        ->and($buyerWallet->balance)->toBe(10)
        ->and($buyerWallet->customerName())->toBe('Kofi Buyer')
        ->and($referrerWallet)->not->toBeNull()
        ->and($referrerWallet->balance)->toBe(15)
        ->and($referrerWallet->customerName())->toBe('Ama Referrer')
        ->and($referrerWallet->transactions()->where('type', LoyaltyPointTransactionType::ReferralEarn)->count())->toBe(1);

    Notification::assertSentOnDemand(OrderPaidCustomerNotification::class);
    Notification::assertSentOnDemand(OrderPaidCustomerSmsNotification::class);
    Notification::assertSentOnDemand(ReferralRewardSmsNotification::class);
});
