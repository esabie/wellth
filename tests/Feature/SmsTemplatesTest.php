<?php

use App\Enums\SmsTemplate;
use App\Models\Order;
use App\Notifications\Channels\MnotifyChannel;
use App\Notifications\OrderPaidCustomerSmsNotification;
use App\Notifications\OrderPlacedCustomerSmsNotification;
use App\Notifications\OrderShippedCustomerSmsNotification;
use App\Support\SmsTemplates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('sms templates fall back to defaults before anything is saved', function () {
    $templates = app(SmsTemplates::class);

    expect($templates->message(SmsTemplate::OrderPaidCustomer))
        ->toBe(SmsTemplate::OrderPaidCustomer->defaultMessage())
        ->and($templates->message(SmsTemplate::OrderPlacedCustomer))
        ->toBe(SmsTemplate::OrderPlacedCustomer->defaultMessage());
});

test('order placed customer sms includes order number and total', function () {
    $order = Order::factory()->create([
        'uuid' => 'WLPLACE001',
        'customer_name' => 'Ama Mensah',
        'total' => '200.00',
        'currency' => 'GHS',
    ]);

    $message = (new OrderPlacedCustomerSmsNotification($order))->toMnotify(
        Notification::route(MnotifyChannel::class, '0244111222'),
    );

    expect($message)
        ->toBe('Hi Ama, we received your Wellth order WLPLACE001 (GHS 200.00). Complete MoMo payment and upload your screenshot to confirm.');
});

test('sms templates render placeholders for customer payment confirmation', function () {
    $order = Order::factory()->create([
        'uuid' => 'WLK7M2P9QX',
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

test('custom sms templates are used when rendering notifications', function () {
    app(SmsTemplates::class)->put([
        SmsTemplate::OrderPaidCustomer->value => 'Thanks {first_name}! Order {order} paid ({currency} {total}).',
    ]);

    $order = Order::factory()->create([
        'uuid' => 'WLCUSTOM01',
        'customer_name' => 'Ama Mensah',
        'total' => '99.00',
        'currency' => 'GHS',
    ]);

    $message = (new OrderPaidCustomerSmsNotification($order))->toMnotify(
        Notification::route(MnotifyChannel::class, '0244111222'),
    );

    expect($message)->toBe('Thanks Ama! Order WLCUSTOM01 paid (GHS 99.00).');
});

test('shipped sms includes city suffix when a shipping city exists', function () {
    $order = Order::factory()->create([
        'uuid' => 'WLSHIPPED1',
        'customer_name' => 'Kojo Boateng',
        'shipping_city' => 'Kumasi',
    ]);

    $message = (new OrderShippedCustomerSmsNotification($order))->toMnotify(
        Notification::route(MnotifyChannel::class, '0244111222'),
    );

    expect($message)->toBe('Hi Kojo, your Wellth order WLSHIPPED1 is on the way to Kumasi. Thanks for choosing us!');
});
