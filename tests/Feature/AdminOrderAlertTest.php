<?php

use App\Actions\CompletePaidOrder;
use App\Actions\CreateCheckoutOrder;
use App\Actions\SubmitPaymentProof;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Notifications\Channels\MnotifyChannel;
use App\Notifications\OrderPaidCustomerSmsNotification;
use App\Notifications\OrderPaymentProofSubmittedNotification;
use App\Notifications\OrderPaymentProofSubmittedSmsNotification;
use App\Notifications\OrderPlacedAdminNotification;
use App\Notifications\OrderPlacedAdminSmsNotification;
use App\Support\Cart;
use App\Support\PhoneNumber;
use App\Support\ShopSettings;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');

    config([
        'shop.shipping_amount' => 0,
        'shop.admin_notification_email' => null,
        'shop.admin_notification_phone' => null,
        'services.mnotify.api_key' => 'test-mnotify-key',
    ]);

    app(ShopSettings::class)->putCheckout([
        ShopSettings::MINIMUM_ORDER_QUANTITY => 10,
        ShopSettings::REFERRAL_POINTS_PER_ORDER => 10,
        ShopSettings::ADMIN_NOTIFICATION_EMAIL => 'ops@wellth.test',
        ShopSettings::ADMIN_NOTIFICATION_PHONE => '0244000111',
    ]);
});

test('the admin panel shows database notifications', function () {
    $panel = Filament::getPanel('admin');

    expect($panel->hasDatabaseNotifications())->toBeTrue();
});

test('placing an order emails, texts, and databases the admin', function () {
    Notification::fake();

    $admin = User::factory()->create();

    $product = Product::factory()->create(['price' => 20.00]);
    app(Cart::class)->add($product, 10);

    $order = app(CreateCheckoutOrder::class)->handle(app(Cart::class), [
        'customer_name' => 'Ama Mensah',
        'customer_email' => 'ama@example.com',
        'customer_phone' => '0555000111',
        'shipping_line1' => '12 Liberation Road',
        'shipping_city' => 'Accra',
        'shipping_region' => 'Greater Accra',
    ]);

    Notification::assertSentOnDemand(
        OrderPlacedAdminNotification::class,
        fn (OrderPlacedAdminNotification $notification, array $channels, object $notifiable): bool => $notifiable->routes['mail'] === 'ops@wellth.test'
            && $notification->order->is($order)
            && in_array('mail', $channels, true),
    );

    Notification::assertSentTo(
        $admin,
        OrderPlacedAdminNotification::class,
        fn (OrderPlacedAdminNotification $notification, array $channels): bool => $notification->order->is($order)
            && in_array('database', $channels, true),
    );

    Notification::assertSentOnDemand(
        OrderPlacedAdminSmsNotification::class,
        fn (OrderPlacedAdminSmsNotification $notification, array $channels, object $notifiable): bool => $notifiable->routes[MnotifyChannel::class] === PhoneNumber::forSms('0244000111')
            && $notification->order->is($order)
            && in_array(MnotifyChannel::class, $channels, true),
    );
});

test('uploading payment proof emails, texts, and databases the admin', function () {
    Notification::fake();

    $admin = User::factory()->create();

    $order = Order::factory()->create([
        'status' => OrderStatus::Pending,
        'customer_name' => 'Ama Mensah',
        'total' => '200.00',
    ]);

    OrderItem::factory()->create(['order_id' => $order->id]);

    app(SubmitPaymentProof::class)->handle(
        $order,
        UploadedFile::fake()->image('momo-proof.jpg', 800, 1200),
    );

    Notification::assertSentOnDemand(
        OrderPaymentProofSubmittedNotification::class,
        fn (OrderPaymentProofSubmittedNotification $notification, array $channels, object $notifiable): bool => $notifiable->routes['mail'] === 'ops@wellth.test'
            && in_array('mail', $channels, true),
    );

    Notification::assertSentTo(
        $admin,
        OrderPaymentProofSubmittedNotification::class,
        fn (OrderPaymentProofSubmittedNotification $notification, array $channels): bool => in_array('database', $channels, true),
    );

    Notification::assertSentOnDemand(
        OrderPaymentProofSubmittedSmsNotification::class,
        fn (OrderPaymentProofSubmittedSmsNotification $notification, array $channels, object $notifiable): bool => $notifiable->routes[MnotifyChannel::class] === PhoneNumber::forSms('0244000111')
            && in_array(MnotifyChannel::class, $channels, true),
    );
});

test('confirming payment texts the buyer', function () {
    Notification::fake();

    $order = Order::factory()->create([
        'status' => OrderStatus::AwaitingConfirmation,
        'customer_phone' => '0555000111',
        'customer_phone_normalized' => PhoneNumber::normalize('0555000111'),
        'customer_email' => 'ama@example.com',
        'total' => '200.00',
    ]);

    OrderItem::factory()->create(['order_id' => $order->id]);

    app(CompletePaidOrder::class)->handle($order);

    Notification::assertSentOnDemand(
        OrderPaidCustomerSmsNotification::class,
        fn (OrderPaidCustomerSmsNotification $notification, array $channels, object $notifiable): bool => $notifiable->routes[MnotifyChannel::class] === PhoneNumber::forSms('0555000111')
            && $notification->order->is($order)
            && in_array(MnotifyChannel::class, $channels, true),
    );
});
