<?php

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('admins can list orders in filament', function () {
    $order = Order::factory()->paid()->create([
        'customer_name' => 'Ama Mensah',
    ]);

    Livewire::test(ListOrders::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$order]);
});

test('admins can view an order and its line items', function () {
    $order = Order::factory()->paid()->create([
        'customer_name' => 'Ama Mensah',
        'customer_email' => 'ama@example.com',
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_name' => 'Ginja',
        'quantity' => 10,
    ]);

    Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
        ->assertOk()
        ->assertSee('Ama Mensah')
        ->assertSee('ama@example.com');
});

test('admins can update fulfilment notes and cancel without changing customer details', function () {
    $order = Order::factory()->paid()->create([
        'customer_name' => 'Ama Mensah',
        'customer_email' => 'ama@example.com',
        'shipping_notes' => null,
    ]);

    Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
        ->assertOk()
        ->fillForm([
            'status' => OrderStatus::Cancelled->value,
            'shipping_notes' => 'Customer asked to cancel before prep.',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($order->fresh())
        ->status->toBe(OrderStatus::Cancelled)
        ->shipping_notes->toBe('Customer asked to cancel before prep.')
        ->customer_name->toBe('Ama Mensah')
        ->customer_email->toBe('ama@example.com');
});
