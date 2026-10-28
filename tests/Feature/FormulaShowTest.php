<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('formula products render the rich detail page from the shop route', function () {
    $category = Category::factory()->create(['slug' => 'wellness-shots']);

    $product = Product::factory()->create([
        'category_id' => $category->id,
        'name' => 'Belly Reset',
        'slug' => 'tropic-cool',
        'price' => 50.00,
    ]);

    $this->get(route('shop.show', $product))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Formulas/Show')
            ->where('formula.name', 'Belly Reset')
            ->where('formula.blurb', 'Pineapple and cucumber, supporting hydration and everyday refreshment.')
            ->where('formula.bottle_size', '60ml')
            ->has('formula.highlights', 3)
            ->where('formula.taste', 'Tropical pineapple with cool cucumber and a ginger edge.')
            ->has('formula.ingredients', 4)
            ->where('formula.ingredients.0.name', 'Cucumber')
            ->where('formula.product_id', $product->id)
            ->where('formula.price', '50.00')
        );
});

test('legacy formula urls redirect to the shop product page', function () {
    $this->get('/formulas/tropic-cool')
        ->assertRedirect('/shop/tropic-cool');
});

test('unknown shop products return not found', function () {
    $this->get('/shop/missing-shot')->assertNotFound();
});

test('home formula cards omit blurbs and link to shop detail pages', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->has('formulas', 5)
            ->missing('formulas.0.blurb')
            ->missing('featuredProducts')
            ->where('formulas.0.href', route('shop.show', 'ginja'))
        );
});
