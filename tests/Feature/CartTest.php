<?php

use App\Models\Product;
use App\Support\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Session;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('guests can add an active product to the cart', function () {
    $product = Product::factory()->create(['name' => 'Ginger Fire Shot']);

    $this->from(route('shop.show', $product))
        ->post(route('cart.store'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ])
        ->assertRedirect(route('shop.show', $product))
        ->assertSessionHas('open_cart', true);

    expect(app(Cart::class)->count())->toBe(2);
});

test('inactive products cannot be added to the cart', function () {
    $product = Product::factory()->inactive()->create();

    $this->post(route('cart.store'), [
        'product_id' => $product->id,
    ])->assertSessionHasErrors('product_id');

    expect(app(Cart::class)->count())->toBe(0);
});

test('cart page shows cart items and subtotal', function () {
    $product = Product::factory()->create([
        'name' => 'Turmeric Glow Shot',
        'price' => 12.00,
    ]);

    app(Cart::class)->add($product, 3);

    $this->get(route('cart.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Cart/Index')
            ->has('items', 1)
            ->where('items.0.product.name', 'Turmeric Glow Shot')
            ->where('items.0.quantity', 3)
            ->where('subtotal', '36.00')
        );
});

test('cart quantities can be updated and removed', function () {
    $product = Product::factory()->create(['slug' => 'green-recovery-juice']);

    app(Cart::class)->add($product, 2);

    $this->from(route('cart.index'))
        ->patch(route('cart.update', $product), ['quantity' => 5])
        ->assertRedirect(route('cart.index'));

    expect(app(Cart::class)->count())->toBe(5);

    $this->from(route('cart.index'))
        ->delete(route('cart.destroy', $product))
        ->assertRedirect(route('cart.index'));

    expect(app(Cart::class)->count())->toBe(0);
});

test('cart can be cleared', function () {
    $product = Product::factory()->create();
    app(Cart::class)->add($product, 2);

    $this->from(route('home'))
        ->delete(route('cart.clear'))
        ->assertRedirect(route('home'));

    expect(app(Cart::class)->count())->toBe(0);
});

test('shared inertia props include the cart summary', function () {
    $product = Product::factory()->create([
        'name' => 'Ginja',
        'slug' => 'ginja',
        'price' => 20.00,
    ]);
    app(Cart::class)->add($product, 2);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('cart.count', 2)
            ->where('cart.subtotal', '40.00')
            ->where('cart.minimum_order_quantity', 10)
            ->where('cart.meets_minimum_order', false)
            ->where('cart.remaining_for_minimum_order', 8)
            ->has('cart.items', 1)
            ->where('cart.items.0.product.name', 'Ginja')
            ->where('cart.items.0.product.image', '/images/formulas/ginger.jpg')
        );
});

test('abandoned carts clear automatically after twenty minutes if unpaid', function () {
    config(['shop.cart_ttl_minutes' => 20]);

    $product = Product::factory()->create();
    $cart = app(Cart::class);
    $cart->add($product, 2);

    expect($cart->count())->toBe(2);

    Carbon::setTestNow(now()->addMinutes(20)->addSecond());

    expect($cart->count())->toBe(0)
        ->and(Session::has('cart'))->toBeFalse();

    Carbon::setTestNow();
});
