<?php

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('product detail page shows an active product', function () {
    $product = Product::factory()->create([
        'name' => 'Citrus Press Juice',
        'slug' => 'citrus-press-juice',
        'image_path' => 'products/citrus.jpg',
    ]);

    $this->get(route('shop.show', $product))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Shop/Show')
            ->where('product.name', 'Citrus Press Juice')
            ->where('product.slug', 'citrus-press-juice')
            ->where('product.image', '/storage/products/citrus.jpg')
            ->has('relatedProducts')
        );
});

test('inactive products return not found', function () {
    $product = Product::factory()->inactive()->create([
        'slug' => 'draft-shot',
    ]);

    $this->get(route('shop.show', $product))->assertNotFound();
});
