<?php

use App\Actions\CompletePaidOrder;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Notifications\Channels\MnotifyChannel;
use App\Notifications\OrderPaidCustomerSmsNotification;
use App\Support\MnotifyClient;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('completing a paid order sends a confirmation sms when mnotify is configured', function () {
    Notification::fake();

    config([
        'services.mnotify.api_key' => 'test-mnotify-key',
        'services.mnotify.sender_id' => 'Wellth',
    ]);

    $order = Order::factory()->create([
        'status' => OrderStatus::Pending,
        'customer_phone' => '0244111222',
        'customer_phone_normalized' => PhoneNumber::normalize('0244111222'),
        'total' => '200.00',
    ]);

    OrderItem::factory()->create(['order_id' => $order->id]);

    app(CompletePaidOrder::class)->handle($order);

    Notification::assertSentOnDemand(
        OrderPaidCustomerSmsNotification::class,
        function (OrderPaidCustomerSmsNotification $notification, array $channels, object $notifiable) use ($order): bool {
            return $notifiable->routes[MnotifyChannel::class] === '0244111222'
                && $notification->order->is($order)
                && in_array(MnotifyChannel::class, $channels, true);
        },
    );
});

test('completing a paid order skips sms when mnotify is not configured', function () {
    Notification::fake();

    config(['services.mnotify.api_key' => null]);

    $order = Order::factory()->create([
        'status' => OrderStatus::Pending,
        'customer_phone' => '0244111222',
    ]);

    OrderItem::factory()->create(['order_id' => $order->id]);

    app(CompletePaidOrder::class)->handle($order);

    Notification::assertNotSentTo(
        Notification::route(MnotifyChannel::class, '0244111222'),
        OrderPaidCustomerSmsNotification::class,
    );
});

test('mnotify client posts quick sms with the configured sender and key', function () {
    Http::preventStrayRequests();
    Event::fake([MessageLogged::class]);

    config([
        'services.mnotify.api_key' => 'test-mnotify-key',
        'services.mnotify.sender_id' => 'Wellth',
        'services.mnotify.base_url' => 'https://api.mnotify.com/api',
    ]);

    Http::fake([
        'api.mnotify.com/api/sms/quick*' => Http::response([
            'status' => 'success',
            'code' => '2000',
            'message' => 'messages sent successfully',
            'summary' => [
                '_id' => 'campaign-1',
                'total_sent' => 1,
            ],
        ]),
    ]);

    $result = app(MnotifyClient::class)->send(['0244111222'], 'Payment confirmed');

    expect($result['status'])->toBe('success')
        ->and($result['summary']['_id'])->toBe('campaign-1');

    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://api.mnotify.com/api/sms/quick?key=test-mnotify-key'
            && $request['recipient'] === ['0244111222']
            && $request['sender'] === 'Wellth'
            && $request['message'] === 'Payment confirmed'
            && $request['is_schedule'] === false;
    });

    Event::assertDispatched(
        MessageLogged::class,
        fn (MessageLogged $event): bool => $event->level === 'info'
            && $event->message === 'mnotify.send_succeeded'
            && ($event->context['body'] ?? null) === 'Payment confirmed',
    );
});

test('customer sms notification message includes order number and total', function () {
    $order = Order::factory()->create([
        'uuid' => 'WLK7M2P9QX',
        'paystack_reference' => 'WLK7M2P9QX',
        'customer_name' => 'Kwamz Original',
        'total' => '185.50',
        'currency' => 'GHS',
    ]);

    $message = (new OrderPaidCustomerSmsNotification($order))->toMnotify(
        Notification::route(MnotifyChannel::class, '0244111222'),
    );

    expect($message)
        ->toBe("Hi Kwamz, Your payment for order WLK7M2P9QX (GHS 185.50) is confirmed. We're preparing your fresh shots with care. Your order will be ready within 7 days. Thank you for choosing us!");
});

test('new orders get a short order number used as the payment reference', function () {
    $order = Order::factory()->create();

    expect($order->uuid)
        ->toStartWith('WL')
        ->toHaveLength(10)
        ->and($order->paymentReference())->toBe($order->uuid);
});
