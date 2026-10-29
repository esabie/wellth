<?php

use App\Models\Product;
use App\Support\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
