<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('product belongs to a category', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->create(['category_id' => $category->id]);

    expect($product->category->is($category))->toBeTrue();
});

test('active scope filters correctly', function () {
    Product::factory()->create();
    Product::factory()->create();
    Product::factory()->inactive()->create();

    expect(Product::query()->active()->count())->toBe(2);
});
