<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'shop.shipping_amount' => 0,
        'services.mnotify.api_key' => null,
        'services.paystack.secret_key' => 'sk_test_wellth',
        'services.paystack.public_key' => 'pk_test_wellth',
        'services.paystack.base_url' => 'https://api.paystack.co',
    ]);
});

function fakePaystackInitialize(): void
{
    Http::fake(function ($request) {
        if (! str_ends_with($request->url(), '/transaction/initialize')) {
            return Http::response(['status' => false], 404);
        }

        return Http::response([
            'status' => true,
            'message' => 'Authorization URL created',
            'data' => [
                'authorization_url' => 'https://checkout.paystack.com/test-checkout',
                'access_code' => 'access_test_123',
                'reference' => $request['reference'],
            ],
        ]);
    });
}

function fakePaystackVerify(string $reference, int $amount = 20000, string $status = 'success', string $currency = 'GHS'): void
{
    Http::fake([
        "api.paystack.co/transaction/verify/{$reference}" => Http::response([
            'status' => true,
            'message' => 'Verification successful',
            'data' => [
                'status' => $status,
                'amount' => $amount,
                'currency' => $currency,
                'reference' => $reference,
                'paid_at' => '2026-11-27T10:00:00.000Z',
            ],
        ]),
    ]);
}

test('checkout store sends the customer to mobile money instructions without calling paystack', function () {
    Http::fake();

    $product = Product::factory()->create(['price' => 20.00]);
    app(Cart::class)->add($product, 10);

    $response = $this->post(route('checkout.store'), [
        'customer_name' => 'Ama Mensah',
        'customer_phone' => '0244111222',
        'shipping_line1' => '12 Liberation Road',
        'shipping_city' => 'Accra',
        'shipping_region' => 'Greater Accra',
    ]);

    $order = Order::query()->first();

    expect($order)->not->toBeNull()
        ->and($order->status)->toBe(OrderStatus::Pending)
        ->and($order->total)->toBe('200.00')
        ->and($order->service_fee_amount)->toBe('0.00')
        ->and($order->paystack_access_code)->toBeNull();

    $response->assertRedirect(route('checkout.payment', $order));

    Http::assertNothingSent();
});

test('a rejected paystack initialize logs the paystack status and message', function () {
    $logged = [];

    Log::listen(function (object $event) use (&$logged): void {
        $logged[] = [
            'level' => $event->level,
            'message' => $event->message,
            'context' => $event->context,
        ];
    });

    Http::fake([
        'api.paystack.co/transaction/initialize' => Http::response([
            'status' => false,
            'message' => 'Currency not supported',
        ], 400),
    ]);

    $order = Order::factory()->create([
        'status' => OrderStatus::Pending,
        'total' => '200.00',
    ]);

    $this->post(route('checkout.payment.start', $order))->assertRedirect();

    $entry = collect($logged)->firstWhere('message', 'paystack.initialize_http_error');

    expect($entry)->not->toBeNull()
        ->and($entry['level'])->toBe('error')
        ->and($entry['context']['status'])->toBe(400)
        ->and($entry['context']['key_mode'])->toBe('test')
        ->and($entry['context']['response']['message'])->toBe('Currency not supported')
        ->and($entry['context']['reference'])->toBe(Order::query()->value('paystack_reference'))
        ->and($entry['context']['exception'])->toBe(RequestException::class);
});

test('payment start retries paystack initialize with a fresh reference', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Pending,
        'total' => '200.00',
        'uuid' => 'WLRETRY001',
        'paystack_reference' => 'WLRETRY001',
        'paystack_access_code' => 'old_access_code',
    ]);

    OrderItem::factory()->create(['order_id' => $order->id]);

    Http::fake(function ($request) {
        if ($request->url() !== 'https://api.paystack.co/transaction/initialize') {
            return Http::response(['status' => false], 404);
        }

        $reference = $request['reference'];

        return Http::response([
            'status' => true,
            'message' => 'Authorization URL created',
            'data' => [
                'authorization_url' => 'https://checkout.paystack.com/test-checkout',
                'access_code' => 'access_test_123',
                'reference' => $reference,
            ],
        ]);
    });

    $this->post(route('checkout.payment.start', $order))
        ->assertRedirect('https://checkout.paystack.com/test-checkout');

    $order->refresh();

    expect($order->paystack_access_code)->toBe('access_test_123')
        ->and($order->paystack_reference)->not->toBe('WLRETRY001')
        ->and($order->paystack_reference)->toStartWith('WLRETRY001-');
});

test('paystack callback marks the order paid on successful verification', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Pending,
        'total' => '200.00',
        'currency' => 'GHS',
        'paystack_reference' => 'WLCBACK001',
        'customer_email' => 'ama@example.com',
    ]);

    OrderItem::factory()->create(['order_id' => $order->id]);

    fakePaystackVerify('WLCBACK001', 20000);

    $this->get(route('checkout.paystack.callback', ['reference' => 'WLCBACK001']))
        ->assertRedirect(route('checkout.confirmation', $order));

    expect($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($order->fresh()->paid_at)->not->toBeNull();
});

test('paystack callback rejects a failed payment and returns to the payment page', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Pending,
        'total' => '200.00',
        'currency' => 'GHS',
        'paystack_reference' => 'WLFAIL001',
    ]);

    OrderItem::factory()->create(['order_id' => $order->id]);

    fakePaystackVerify('WLFAIL001', 20000, 'failed');

    $this->get(route('checkout.paystack.callback', ['reference' => 'WLFAIL001']))
        ->assertRedirect(route('checkout.payment', $order))
        ->assertSessionHas('error');

    expect($order->fresh()->status)->toBe(OrderStatus::Pending);
});

test('paystack callback rejects an amount mismatch', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Pending,
        'total' => '200.00',
        'currency' => 'GHS',
        'paystack_reference' => 'WLAMT001',
    ]);

    OrderItem::factory()->create(['order_id' => $order->id]);

    fakePaystackVerify('WLAMT001', 15000);

    $this->get(route('checkout.paystack.callback', ['reference' => 'WLAMT001']))
        ->assertRedirect(route('checkout.payment', $order))
        ->assertSessionHas('error');

    expect($order->fresh()->status)->toBe(OrderStatus::Pending);
});

test('paystack webhook with a valid signature completes payment idempotently', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Pending,
        'total' => '200.00',
        'currency' => 'GHS',
        'paystack_reference' => 'WLHOOK001',
        'customer_email' => 'ama@example.com',
    ]);

    OrderItem::factory()->create(['order_id' => $order->id]);

    fakePaystackVerify('WLHOOK001', 20000);

    $payload = json_encode([
        'event' => 'charge.success',
        'data' => [
            'reference' => 'WLHOOK001',
            'amount' => 20000,
            'currency' => 'GHS',
        ],
    ], JSON_THROW_ON_ERROR);

    $signature = hash_hmac('sha512', $payload, 'sk_test_wellth');

    $this->call(
        'POST',
        route('webhooks.paystack'),
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_PAYSTACK_SIGNATURE' => $signature,
        ],
        $payload,
    )->assertOk()
        ->assertJson(['status' => 'ok']);

    expect($order->fresh()->status)->toBe(OrderStatus::Paid);

    $this->call(
        'POST',
        route('webhooks.paystack'),
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_PAYSTACK_SIGNATURE' => $signature,
        ],
        $payload,
    )->assertOk()
        ->assertJson(['status' => 'ok']);

    expect($order->fresh()->status)->toBe(OrderStatus::Paid);
});

test('paystack webhook rejects an invalid signature', function () {
    $payload = json_encode([
        'event' => 'charge.success',
        'data' => ['reference' => 'WLHOOKBAD'],
    ], JSON_THROW_ON_ERROR);

    $this->call(
        'POST',
        route('webhooks.paystack'),
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_PAYSTACK_SIGNATURE' => 'not-valid',
        ],
        $payload,
    )->assertUnauthorized();
});

test('paid confirmation page still renders after paystack payment', function () {
    $order = Order::factory()->paid()->create([
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
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Checkout/Confirmation')
            ->where('order.status', 'paid')
            ->where('order.customer_name', 'Ama Mensah')
        );
});
