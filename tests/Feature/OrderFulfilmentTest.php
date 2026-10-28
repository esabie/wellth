<?php

use App\Actions\MarkOrderShipped;
use App\Actions\RestoreOrderStock;
use App\Actions\StartPreparingOrder;
use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderPreparingCustomerNotification;
use App\Notifications\OrderShippedCustomerNotification;
use App\Notifications\OrderShippedCustomerSmsNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['services.mnotify.api_key' => 'test-key']);
});

test('starting preparation decrements stock and notifies the customer once', function () {
    Notification::fake();

    $product = Product::factory()->create(['stock_quantity' => 40]);
    $order = Order::factory()->paid()->create([
        'customer_email' => 'ama@example.com',
        'customer_phone' => '0555000111',
        'customer_name' => 'Ama Mensah',
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 10,
    ]);

    app(StartPreparingOrder::class)->handle($order);

    expect($order->fresh()->status)->toBe(OrderStatus::Preparing)
        ->and($order->fresh()->prepared_at)->not->toBeNull()
        ->and($order->fresh()->stock_decremented_at)->not->toBeNull()
        ->and($product->fresh()->stock_quantity)->toBe(30);

    Notification::assertSentOnDemand(OrderPreparingCustomerNotification::class);

    app(StartPreparingOrder::class)->handle($order->fresh());

    expect($product->fresh()->stock_quantity)->toBe(30);

    Notification::assertSentOnDemandTimes(OrderPreparingCustomerNotification::class, 1);
});

test('marking shipped notifies the customer once and requires preparing status', function () {
    Notification::fake();

    $order = Order::factory()->preparing()->create([
        'customer_email' => 'kofi@example.com',
        'customer_phone' => '0244111222',
        'customer_name' => 'Kofi Boateng',
        'shipping_city' => 'Accra',
    ]);

    OrderItem::factory()->create(['order_id' => $order->id]);

    app(MarkOrderShipped::class)->handle($order);

    expect($order->fresh()->status)->toBe(OrderStatus::Shipped)
        ->and($order->fresh()->shipped_at)->not->toBeNull();

    Notification::assertSentOnDemand(OrderShippedCustomerNotification::class);
    Notification::assertSentOnDemand(OrderShippedCustomerSmsNotification::class);

    app(MarkOrderShipped::class)->handle($order->fresh());

    Notification::assertSentOnDemandTimes(OrderShippedCustomerNotification::class, 1);

    $paidOnly = Order::factory()->paid()->create();
    OrderItem::factory()->create(['order_id' => $paidOnly->id]);

    app(MarkOrderShipped::class)->handle($paidOnly);

    expect($paidOnly->fresh()->status)->toBe(OrderStatus::Paid);
    Notification::assertSentOnDemandTimes(OrderShippedCustomerNotification::class, 1);
});

test('cancelling a preparing order restores stock', function () {
    $product = Product::factory()->create(['stock_quantity' => 20]);
    $order = Order::factory()->preparing()->create();

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 10,
    ]);

    app(RestoreOrderStock::class)->handle($order);

    expect($product->fresh()->stock_quantity)->toBe(30)
        ->and($order->fresh()->stock_decremented_at)->toBeNull();

    app(RestoreOrderStock::class)->handle($order->fresh());

    expect($product->fresh()->stock_quantity)->toBe(30);
});

test('admins can start preparing and mark shipped from the order view', function () {
    Notification::fake();

    $this->actingAs(User::factory()->create());

    $product = Product::factory()->create(['stock_quantity' => 50]);
    $order = Order::factory()->paid()->create([
        'customer_email' => 'ama@example.com',
        'customer_phone' => '0555000111',
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 10,
    ]);

    Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
        ->callAction('startPreparing')
        ->assertHasNoActionErrors();

    expect($order->fresh()->status)->toBe(OrderStatus::Preparing)
        ->and($product->fresh()->stock_quantity)->toBe(40);

    Livewire::test(ViewOrder::class, ['record' => $order->fresh()->getRouteKey()])
        ->callAction('markShipped')
        ->assertHasNoActionErrors();

    expect($order->fresh()->status)->toBe(OrderStatus::Shipped);

    Notification::assertSentOnDemand(OrderPreparingCustomerNotification::class);
    Notification::assertSentOnDemand(OrderShippedCustomerNotification::class);
});

test('cancelling from filament restores stock after preparation', function () {
    $this->actingAs(User::factory()->create());

    $product = Product::factory()->create(['stock_quantity' => 12]);
    $order = Order::factory()->preparing()->create([
        'customer_name' => 'Ama Mensah',
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 10,
    ]);

    Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
        ->fillForm([
            'status' => OrderStatus::Cancelled->value,
            'shipping_notes' => 'Customer cancelled after prep started.',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($order->fresh()->stock_decremented_at)->toBeNull()
        ->and($product->fresh()->stock_quantity)->toBe(22);
});

test('confirmation page still renders for preparing and shipped orders', function () {
    $order = Order::factory()->preparing()->create([
        'customer_name' => 'Ama Mensah',
        'total' => '200.00',
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_name' => 'Ginja',
        'quantity' => 10,
        'line_total' => '200.00',
    ]);

    $this->get(route('checkout.confirmation', $order))
        ->assertOk();

    $shipped = Order::factory()->shipped()->create([
        'customer_name' => 'Kofi Boateng',
    ]);

    OrderItem::factory()->create(['order_id' => $shipped->id]);

    $this->get(route('checkout.confirmation', $shipped))
        ->assertOk();
});
