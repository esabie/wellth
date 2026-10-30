<?php

use App\Actions\CompletePaidOrder;
use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Notifications\OrderPaidCustomerNotification;
use App\Notifications\OrderPaymentProofSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');

    config([
        'shop.shipping_amount' => 0,
        'shop.admin_notification_email' => 'ops@wellth.test',
        'shop.momo_display_number' => '+233 54 536 5434',
        'shop.momo_account_name' => 'Wellth',
        'shop.momo_network' => 'MTN MoMo',
    ]);
});

test('payment page shows momo instructions for a pending order', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Pending,
        'total' => '200.00',
        'customer_name' => 'Ama Mensah',
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_name' => 'Ginja',
        'quantity' => 10,
        'line_total' => '200.00',
    ]);

    $this->get(route('checkout.payment', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Checkout/Payment')
            ->where('order.uuid', $order->uuid)
            ->where('order.total', '200.00')
            ->where('momo.display_number', '+233 54 536 5434')
            ->where('momo.account_name', 'Wellth')
        );
});

test('customers can upload a payment screenshot and move to awaiting confirmation', function () {
    Notification::fake();

    $order = Order::factory()->create([
        'status' => OrderStatus::Pending,
        'customer_email' => 'ama@example.com',
        'total' => '200.00',
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 10,
    ]);

    $file = UploadedFile::fake()->image('momo-proof.jpg', 800, 1200);

    $this->post(route('checkout.payment.store', $order), [
        'payment_proof' => $file,
    ])->assertRedirect(route('checkout.confirmation', $order));

    $order->refresh();

    expect($order->status)->toBe(OrderStatus::AwaitingConfirmation)
        ->and($order->payment_proof_path)->not->toBeNull()
        ->and($order->payment_submitted_at)->not->toBeNull();

    Storage::disk('local')->assertExists($order->payment_proof_path);

    Notification::assertSentOnDemand(OrderPaymentProofSubmittedNotification::class);
});

test('payment proof upload requires an image', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);

    $this->from(route('checkout.payment', $order))
        ->post(route('checkout.payment.store', $order), [])
        ->assertRedirect(route('checkout.payment', $order))
        ->assertSessionHasErrors('payment_proof');
});

test('awaiting confirmation orders can view the confirmation page', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::AwaitingConfirmation,
        'customer_name' => 'Ama Mensah',
        'total' => '200.00',
        'payment_proof_path' => 'payment-proofs/example.jpg',
        'payment_submitted_at' => now(),
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_name' => 'Ginja',
        'quantity' => 10,
        'line_total' => '200.00',
    ]);

    $this->get(route('checkout.confirmation', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Checkout/Confirmation')
            ->where('order.status', 'awaiting_confirmation')
            ->where('order.customer_name', 'Ama Mensah')
        );
});

test('admins can confirm momo payment from filament', function () {
    Notification::fake();

    $this->actingAs(User::factory()->create());

    $order = Order::factory()->create([
        'status' => OrderStatus::AwaitingConfirmation,
        'customer_email' => 'ama@example.com',
        'payment_proof_path' => 'payment-proofs/example.jpg',
        'payment_submitted_at' => now(),
    ]);

    OrderItem::factory()->create(['order_id' => $order->id]);

    Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
        ->callAction('confirmPayment')
        ->assertHasNoActionErrors();

    expect($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($order->fresh()->paid_at)->not->toBeNull();

    Notification::assertSentOnDemand(OrderPaidCustomerNotification::class);
});

test('admins can confirm cash payment on a pending order without momo proof', function () {
    Notification::fake();

    $this->actingAs(User::factory()->create());

    $order = Order::factory()->create([
        'status' => OrderStatus::Pending,
        'customer_email' => 'ama@example.com',
        'payment_proof_path' => null,
        'payment_submitted_at' => null,
    ]);

    OrderItem::factory()->create(['order_id' => $order->id]);

    Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
        ->assertActionVisible('confirmPayment')
        ->callAction('confirmPayment')
        ->assertHasNoActionErrors();

    expect($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($order->fresh()->paid_at)->not->toBeNull();

    Notification::assertSentOnDemand(OrderPaidCustomerNotification::class);
});

test('paid confirmation page still renders order details', function () {
    $order = Order::factory()->paid()->create([
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

    $this->get(route('checkout.confirmation', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Checkout/Confirmation')
            ->where('order.customer_name', 'Ama Mensah')
            ->where('order.status', 'paid')
            ->has('order.items', 1)
        );
});

test('pending orders without proof cannot view the confirmation page', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);

    $this->get(route('checkout.confirmation', $order))->assertNotFound();
});

test('complete paid order still notifies when confirming payment', function () {
    Notification::fake();

    $order = Order::factory()->create([
        'status' => OrderStatus::AwaitingConfirmation,
        'customer_email' => 'ama@example.com',
    ]);

    OrderItem::factory()->create(['order_id' => $order->id]);

    app(CompletePaidOrder::class)->handle($order);

    Notification::assertSentOnDemand(OrderPaidCustomerNotification::class);
});
