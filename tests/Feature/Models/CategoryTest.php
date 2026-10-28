<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('category has many products', function () {
    $category = Category::factory()->create();
    Product::factory()->count(2)->create(['category_id' => $category->id]);

    expect($category->products)->toHaveCount(2);
});

test('active scope excludes inactive categories', function () {
    Category::factory()->create(['is_active' => true]);
    Category::factory()->inactive()->create();

    expect(Category::query()->active()->count())->toBe(1);
});
