<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Support\Header;

uses(RefreshDatabase::class);

test('a storefront page is logged once when the same visitor opens it again', function () {
    $messages = [];
    Log::listen(function (object $event) use (&$messages): void {
        $messages[] = $event->message;
    });

    $this->startSession();
    $cookie = config('session.cookie');
    $sessionId = session()->getId();

    $this->withCookie($cookie, $sessionId)
        ->get(route('home'))
        ->assertOk();

    $this->withCookie($cookie, $sessionId)
        ->get(route('home'))
        ->assertOk();

    expect($messages)->toBe(['Visitor opened the home page.']);
});

test('a visit log includes the device, browser, IP address, and location', function () {
    Http::preventStrayRequests();
    Http::fake([
        'ipwho.is/*' => Http::response([
            'success' => true,
            'city' => 'Accra',
            'country' => 'Ghana',
        ]),
    ]);

    $context = null;
    Log::listen(function (object $event) use (&$context): void {
        if ($event->message === 'Visitor opened the home page.') {
            $context = $event->context;
        }
    });

    $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])
        ->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/120.0.0.0 Mobile/15E148 Safari/604.1')
        ->get(route('home'))
        ->assertOk();

    expect($context)->toMatchArray([
        'ip' => '8.8.8.8',
        'device' => 'Phone',
        'browser' => 'Chrome',
        'location' => 'Accra, Ghana',
    ]);

    Http::assertSentCount(1);
});

test('a visit still loads when the location lookup fails', function () {
    Http::preventStrayRequests();
    Http::fake([
        'ipwho.is/*' => Http::failedConnection(),
    ]);

    $context = null;
    Log::listen(function (object $event) use (&$context): void {
        if ($event->message === 'Visitor opened the home page.') {
            $context = $event->context;
        }
    });

    $this->withServerVariables(['REMOTE_ADDR' => '1.1.1.1'])
        ->get(route('home'))
        ->assertOk();

    expect($context['location'] ?? null)->toBe('Unknown');
});

test('partial page reloads are not logged', function () {
    $messages = [];
    Log::listen(function (object $event) use (&$messages): void {
        $messages[] = $event->message;
    });

    $this->withHeaders([
        Header::PARTIAL_COMPONENT => 'Home',
        Header::PARTIAL_ONLY => 'cart',
    ])->get(route('home'))->assertOk();

    expect($messages)->toBe([]);
});

test('opening a product logs the product name', function () {
    $messages = [];
    Log::listen(function (object $event) use (&$messages): void {
        $messages[] = $event->message;
    });

    $product = Product::factory()->create([
        'name' => 'Ginja',
        'slug' => 'ginja-visit-log',
    ]);

    $this->get(route('shop.show', $product))->assertOk();

    expect($messages)->toBe(['Visitor opened Ginja.']);
});

test('adding a product to the cart is logged and quantity changes are not', function () {
    $messages = [];
    Log::listen(function (object $event) use (&$messages): void {
        $messages[] = $event->message;
    });

    $product = Product::factory()->create([
        'name' => 'Ginja',
        'slug' => 'ginja-cart-log',
    ]);

    $this->post(route('cart.store'), [
        'product_id' => $product->id,
        'quantity' => 2,
    ])->assertRedirect();

    app(Cart::class)->add($product, 2);

    $this->from(route('shop.index'))
        ->patch(route('cart.update', $product), ['quantity' => 4])
        ->assertRedirect();

    expect($messages)->toBe(['Visitor added 2 × Ginja to the cart.']);
});

test('opening and submitting feedback is logged for the order', function () {
    Notification::fake();

    $messages = [];
    Log::listen(function (object $event) use (&$messages): void {
        if (str_starts_with($event->message, 'Customer opened the feedback')
            || str_starts_with($event->message, 'Visitor submitted feedback')) {
            $messages[] = $event->message;
        }
    });

    $order = Order::factory()->delivered()->create([
        'feedback_token' => 'visitfeedbacktoken',
    ]);
    $item = OrderItem::factory()->create(['order_id' => $order->id]);

    $this->get(route('feedback.show', $order->feedback_token))->assertOk();

    $this->post(route('feedback.store', $order->feedback_token), [
        'product_id' => $item->product_id,
        'shopping_rating' => 5,
        'improvement_tags' => ['all_good'],
    ])->assertRedirect(route('feedback.show', $order->feedback_token));

    expect($messages[0] ?? null)->toBe("Customer opened the feedback invite link for order {$order->uuid}.")
        ->and($messages[1] ?? null)->toStartWith("Visitor submitted feedback for order {$order->uuid}:")
        ->and($messages[1] ?? null)->toContain('favourite')
        ->and($messages)->toHaveCount(2);

    $this->get(route('feedback.show', $order->feedback_token))->assertOk();

    expect($messages[2] ?? null)->toBe("Customer opened the feedback page for order {$order->uuid} (already submitted).");
});

test('uploading payment proof is logged for the order', function () {
    Storage::fake('local');
    Notification::fake();

    config([
        'services.mnotify.api_key' => null,
        'shop.payment_proof_admin_phones' => [],
        'shop.admin_notification_email' => null,
        'shop.admin_notification_phone' => null,
    ]);

    $messages = [];
    Log::listen(function (object $event) use (&$messages): void {
        if (str_starts_with($event->message, 'Visitor ')) {
            $messages[] = $event->message;
        }
    });

    $order = Order::factory()->create([
        'status' => OrderStatus::Pending,
        'total' => '200.00',
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 10,
    ]);

    $this->post(route('checkout.payment.store', $order), [
        'payment_proof' => UploadedFile::fake()->image('momo-proof.jpg', 800, 1200),
    ])->assertRedirect(route('checkout.confirmation', $order));

    expect($messages)->toBe(["Visitor uploaded payment proof for order {$order->uuid}."]);
});

test('a visit log records a readable external referrer', function () {
    Http::fake([
        'ipwho.is/*' => Http::response([
            'success' => true,
            'city' => 'Accra',
            'country' => 'Ghana',
        ]),
    ]);

    $context = null;
    Log::listen(function (object $event) use (&$context): void {
        if ($event->message === 'Visitor opened the home page.') {
            $context = $event->context;
        }
    });

    $this->withHeader('Referer', 'https://www.instagram.com/p/abc123/')
        ->get(route('home'))
        ->assertOk();

    expect($context['from'] ?? null)->toBe('Instagram')
        ->and($context)->not->toHaveKey('utm')
        ->and($context)->not->toHaveKey('referrer');
});

test('campaign tags stay readable and follow later pages in the same visit', function () {
    Http::fake([
        'ipwho.is/*' => Http::response([
            'success' => true,
            'city' => 'Accra',
            'country' => 'Ghana',
        ]),
    ]);

    $contexts = [];
    Log::listen(function (object $event) use (&$contexts): void {
        $contexts[] = [
            'message' => $event->message,
            'campaign' => $event->context['campaign'] ?? null,
        ];
    });

    $this->get(route('home', [
        'utm_source' => 'instagram',
        'utm_medium' => 'social',
        'utm_campaign' => 'ginja-launch',
    ]))->assertOk();

    $this->get(route('shop.index'))->assertOk();

    expect($contexts)->toHaveCount(2)
        ->and($contexts[0]['campaign'])->toBe('instagram · social · ginja-launch')
        ->and($contexts[1]['message'])->toBe('Visitor opened the shop.')
        ->and($contexts[1]['campaign'])->toBe('instagram · social · ginja-launch');
});

test('opening the cart logs a readable snapshot of what is inside it', function () {
    Http::fake([
        'ipwho.is/*' => Http::response([
            'success' => true,
            'city' => 'Accra',
            'country' => 'Ghana',
        ]),
    ]);

    $product = Product::factory()->create([
        'name' => 'Ginja',
        'slug' => 'ginja-cart-snapshot',
        'price' => 20,
        'pack_units' => 1,
    ]);

    app(Cart::class)->add($product, 3);

    $context = null;
    Log::listen(function (object $event) use (&$context): void {
        if ($event->message === 'Visitor opened the cart.') {
            $context = $event->context;
        }
    });

    $this->get(route('cart.index'))->assertOk();

    expect($context['cart'] ?? null)->toBe('3 bottles · Ginja × 3 · GH₵60.00');
});
