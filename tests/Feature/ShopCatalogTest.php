<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('shop catalog lists active products that have images', function () {
    Product::factory()->create([
        'name' => 'Turmeric Glow Shot',
        'image_path' => 'products/turmeric.jpg',
        'bottle_size' => '60ml',
    ]);
    Product::factory()->inactive()->create([
        'name' => 'Draft Juice',
        'image_path' => 'products/draft.jpg',
    ]);
    Product::factory()->create([
        'name' => 'Ginger Fire Shot',
        'image_path' => null,
    ]);

    $this->get(route('shop.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Shop/Index')
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Turmeric Glow Shot')
            ->where('products.data.0.bottle_size', '60ml')
            ->has('categories')
        );
});

test('shop catalog can filter by category slug', function () {
    $shots = Category::factory()->create(['name' => 'Wellness Shots', 'slug' => 'wellness-shots']);
    $juices = Category::factory()->create(['name' => 'Juices', 'slug' => 'juices']);

    Product::factory()->create([
        'category_id' => $shots->id,
        'name' => 'Ginger Fire Shot',
        'image_path' => 'products/ginger.jpg',
    ]);
    Product::factory()->create([
        'category_id' => $juices->id,
        'name' => 'Green Recovery Juice',
        'image_path' => 'products/green.jpg',
    ]);

    $this->get(route('shop.index', ['category' => 'wellness-shots']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Shop/Index')
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Ginger Fire Shot')
            ->where('filters.category', 'wellness-shots')
        );
});

test('shop catalog resolves formula catalog images when products have no upload', function () {
    Product::factory()->create([
        'name' => 'Ginja',
        'slug' => 'ginja',
        'image_path' => null,
    ]);

    $this->get(route('shop.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Shop/Index')
            ->has('products.data', 1)
            ->where('products.data.0.image', '/images/formulas/ginger.jpg')
        );
});

test('shop catalog prefers uploaded product images over formula catalog images', function () {
    Product::factory()->create([
        'name' => 'Ginja',
        'slug' => 'ginja',
        'image_path' => 'products/custom-ginja.jpg',
    ]);

    $this->get(route('shop.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Shop/Index')
            ->where('products.data.0.image', '/storage/products/custom-ginja.jpg')
        );
});

test('shop catalog hides products without a storefront image', function () {
    Product::factory()->create([
        'name' => 'Weekly Shot Pack',
        'slug' => 'weekly-shot-pack',
        'image_path' => null,
    ]);

    $this->get(route('shop.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Shop/Index')
            ->has('products.data', 0)
        );
});

test('shop catalog only lists categories that have storefront products', function () {
    $shots = Category::factory()->create(['name' => 'Wellness Shots', 'slug' => 'wellness-shots']);
    Category::factory()->create(['name' => 'Bundles', 'slug' => 'bundles']);

    Product::factory()->create([
        'category_id' => $shots->id,
        'name' => 'Ginja',
        'slug' => 'ginja',
        'image_path' => null,
    ]);

    $this->get(route('shop.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Shop/Index')
            ->has('categories', 1)
            ->where('categories.0.slug', 'wellness-shots')
        );
});
