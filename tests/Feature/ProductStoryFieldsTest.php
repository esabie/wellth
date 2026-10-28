<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('products with story fields render the rich detail page without a catalog match', function () {
    $category = Category::factory()->create(['slug' => 'wellness-shots']);

    $product = Product::factory()->create([
        'category_id' => $category->id,
        'name' => 'Sunrise Reset',
        'slug' => 'sunrise-reset',
        'short_description' => 'Pineapple and mint for a bright morning lift.',
        'description' => 'A fresh pineapple shot with cooling mint. Built for an easy daily reset.',
        'bottle_size' => '60ml',
        'taste' => 'Bright pineapple with cool mint.',
        'ritual' => 'Best first thing after waking.',
        'highlights' => [
            'Pineapple brightness',
            'Mint for a cool finish',
            'Easy everyday reset',
        ],
        'ingredients' => [
            ['name' => 'Pineapple', 'image' => '/images/ingredients/pineapple.jpg'],
            ['name' => 'Mint', 'image' => null],
        ],
        'price' => 22.00,
        'image_path' => '/images/formulas/tropical.jpg',
    ]);

    $this->get(route('shop.show', $product))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Formulas/Show')
            ->where('formula.name', 'Sunrise Reset')
            ->where('formula.blurb', 'Pineapple and mint for a bright morning lift.')
            ->where('formula.taste', 'Bright pineapple with cool mint.')
            ->where('formula.ritual', 'Best first thing after waking.')
            ->where('formula.bottle_size', '60ml')
            ->has('formula.highlights', 3)
            ->has('formula.ingredients', 2)
            ->where('formula.ingredients.0.name', 'Pineapple')
            ->where('formula.ingredients.0.image', '/images/ingredients/pineapple.jpg')
            ->where('formula.product_id', $product->id)
        );
});

test('product story fields override matching formula catalog copy', function () {
    $category = Category::factory()->create(['slug' => 'wellness-shots']);

    $product = Product::factory()->create([
        'category_id' => $category->id,
        'name' => 'Glow Getter',
        'slug' => 'glow-getter',
        'short_description' => 'Admin-written blurb.',
        'description' => 'Admin-written description.',
        'taste' => 'Admin-written taste.',
        'ritual' => 'Admin-written ritual.',
        'highlights' => ['One', 'Two', 'Three'],
        'ingredients' => [
            ['name' => 'Admin Carrot', 'image' => null],
        ],
        'bottle_size' => '75ml',
        'price' => 25.00,
    ]);

    $this->get(route('shop.show', $product))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Formulas/Show')
            ->where('formula.blurb', 'Admin-written blurb.')
            ->where('formula.taste', 'Admin-written taste.')
            ->where('formula.ritual', 'Admin-written ritual.')
            ->where('formula.bottle_size', '75ml')
            ->where('formula.highlights.0', 'One')
            ->where('formula.ingredients.0.name', 'Admin Carrot')
        );
});
