<?php

use App\Models\Product;
use App\Support\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Support\Header;

uses(RefreshDatabase::class);

test('cart updates past the limit redirect back and leave the quantity unchanged', function () {
    config(['shop.rate_limits.cart' => 1]);

    $product = Product::factory()->create(['slug' => 'ginger-shot']);
    $this->startSession();
    $sessionCookie = config('session.cookie');

    $this->withCookie($sessionCookie, session()->getId())
        ->post(route('cart.store'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ])
        ->assertRedirect();

    expect(app(Cart::class)->count())->toBe(2);

    $this->withCookie($sessionCookie, session()->getId())
        ->from(route('shop.index'))
        ->patch(route('cart.update', $product), ['quantity' => 6])
        ->assertRedirect(route('shop.index'))
        ->assertSessionHas('error');

    expect(session('error'))->toContain('Too many attempts')
        ->and(app(Cart::class)->count())->toBe(2);
});

test('inertia requests past the limit stay on the page with an error', function () {
    config(['shop.rate_limits.promo' => 1]);

    $this->from(route('checkout.create'))
        ->post(route('checkout.promo.apply'), [])
        ->assertRedirect(route('checkout.create'));

    $this->from(route('checkout.create'))
        ->withHeaders([Header::INERTIA => 'true'])
        ->post(route('checkout.promo.apply'), ['code' => 'WELLTH'])
        ->assertStatus(409)
        ->assertHeader(Header::REDIRECT, route('checkout.create'))
        ->assertSessionHas('error');

    expect(session('error'))->toContain('Too many attempts');
});
