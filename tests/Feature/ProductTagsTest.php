<?php

use App\Enums\ProductTag;
use App\Models\Product;
use App\Support\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('shop catalog includes product tags', function () {
    Product::factory()->create([
        'name' => 'ABC',
        'image_path' => 'products/abc.jpg',
        'tags' => [ProductTag::New->value, ProductTag::ComingSoon->value],
    ]);

    $this->get(route('shop.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Shop/Index')
            ->where('products.data.0.tags', [
                ['value' => 'new', 'label' => 'New'],
                ['value' => 'coming_soon', 'label' => 'Coming Soon'],
            ])
        );
});

test('home formula cards include product tags', function () {
    Product::factory()->create([
        'name' => 'Ginja',
        'slug' => 'ginja',
        'tags' => [ProductTag::ComingSoon->value],
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('formulas.0.slug', 'ginja')
            ->where('formulas.0.tags', [
                ['value' => 'coming_soon', 'label' => 'Coming Soon'],
            ])
        );
});

test('formula page includes product tags', function () {
    $product = Product::factory()->create([
        'name' => 'Ginja',
        'slug' => 'ginja',
        'tags' => [ProductTag::New->value],
    ]);

    $this->get(route('shop.show', $product))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Formulas/Show')
            ->where('formula.tags', [
                ['value' => 'new', 'label' => 'New'],
            ])
        );
});

test('coming soon products cannot be added to the cart', function () {
    $product = Product::factory()->create([
        'tags' => [ProductTag::ComingSoon->value],
    ]);

    $this->from(route('shop.show', $product))
        ->post(route('cart.store'), [
            'product_id' => $product->id,
        ])
        ->assertRedirect(route('shop.show', $product))
        ->assertSessionHasErrors([
            'product_id' => 'This shot is coming soon and cannot be added yet.',
        ]);

    expect(app(Cart::class)->count())->toBe(0);
});

test('a new tag does not stop a product being added to the cart', function () {
    $product = Product::factory()->create([
        'tags' => [ProductTag::New->value],
    ]);

    $this->post(route('cart.store'), [
        'product_id' => $product->id,
        'quantity' => 1,
    ])->assertRedirect();

    expect(app(Cart::class)->count())->toBe(1);
});
