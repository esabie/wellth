<?php

use App\Actions\CompletePaidOrder;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Notifications\Channels\MnotifyChannel;
use App\Notifications\OrderPaidAdminNotification;
use App\Notifications\OrderPaidCustomerNotification;
use App\Notifications\OrderPaidCustomerSmsNotification;
use App\Support\ShopSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('completing a paid order notifies the customer and admin', function () {
    Notification::fake();

    config([
        'shop.admin_notification_email' => null,
        'services.mnotify.api_key' => null,
    ]);

    app(ShopSettings::class)->putCheckout([
        ShopSettings::ADMIN_NOTIFICATION_EMAIL => 'ops@wellth.test',
    ]);

    $admin = User::factory()->create();

    $order = Order::factory()->create([
        'status' => OrderStatus::Pending,
        'customer_name' => 'Ama Mensah',
        'customer_email' => 'ama@example.com',
        'total' => '200.00',
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_name' => 'Ginja',
        'quantity' => 10,
        'line_total' => '200.00',
    ]);

    app(CompletePaidOrder::class)->handle($order);

    expect($order->fresh()->status)->toBe(OrderStatus::Paid);

    Notification::assertSentOnDemand(
        OrderPaidCustomerNotification::class,
        function (OrderPaidCustomerNotification $notification, array $channels, object $notifiable) use ($order): bool {
            return $notifiable->routes['mail'] === 'ama@example.com'
                && $notification->order->is($order)
                && in_array('mail', $channels, true);
        },
    );

    Notification::assertSentOnDemand(
        OrderPaidAdminNotification::class,
        function (OrderPaidAdminNotification $notification, array $channels, object $notifiable) use ($order): bool {
            return $notifiable->routes['mail'] === 'ops@wellth.test'
                && $notification->order->is($order)
                && in_array('mail', $channels, true);
        },
    );

    Notification::assertSentTo(
        $admin,
        OrderPaidAdminNotification::class,
        fn (OrderPaidAdminNotification $notification, array $channels): bool => $notification->order->is($order)
            && in_array('database', $channels, true),
    );

    Notification::assertNotSentTo(
        Notification::route(MnotifyChannel::class, '0244111222'),
        OrderPaidCustomerSmsNotification::class,
    );
});

test('completing a paid order skips admin mail when no admin email is configured', function () {
    Notification::fake();

    config(['shop.admin_notification_email' => null]);

    app(ShopSettings::class)->putCheckout([
        ShopSettings::ADMIN_NOTIFICATION_EMAIL => null,
    ]);

    $order = Order::factory()->create([
        'status' => OrderStatus::Pending,
        'customer_email' => 'ama@example.com',
    ]);

    OrderItem::factory()->create(['order_id' => $order->id]);

    app(CompletePaidOrder::class)->handle($order);

    Notification::assertSentOnDemand(OrderPaidCustomerNotification::class);
    Notification::assertNotSentTo(
        Notification::route('mail', 'ops@wellth.test'),
        OrderPaidAdminNotification::class,
    );
});

test('already paid orders do not resend notifications', function () {
    Notification::fake();

    config(['shop.admin_notification_email' => 'ops@wellth.test']);

    $order = Order::factory()->paid()->create([
        'customer_email' => 'ama@example.com',
    ]);

    app(CompletePaidOrder::class)->handle($order);

    Notification::assertNothingSent();
});
