<?php

use App\Models\Product;
use App\Support\FormulaCatalog;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('product seeder fills story fields from the formula catalog', function () {
    $this->seed(CategorySeeder::class);
    $this->seed(ProductSeeder::class);

    $formulas = app(FormulaCatalog::class)->all();

    expect($formulas)->not->toBeEmpty();

    foreach ($formulas as $formula) {
        $product = Product::query()->where('slug', $formula['product_slug'])->first();

        expect($product)->not->toBeNull()
            ->and($product->hasStoryContent())->toBeTrue()
            ->and($product->short_description)->toBe($formula['blurb'])
            ->and($product->description)->toBe($formula['description'])
            ->and($product->taste)->toBe($formula['taste'])
            ->and($product->ritual)->toBe($formula['ritual'])
            ->and($product->bottle_size)->toBe($formula['bottle_size'] ?? null)
            ->and($product->highlightList())->toBe($formula['highlights']);
    }
});

test('seeded formula product detail prefers product story fields', function () {
    $this->seed(CategorySeeder::class);
    $this->seed(ProductSeeder::class);

    $formula = app(FormulaCatalog::class)->all()->first();
    $product = Product::query()->where('slug', $formula['product_slug'])->firstOrFail();

    $product->update([
        'short_description' => 'Custom storefront blurb',
        'taste' => 'Custom taste note',
    ]);

    $this->get(route('shop.show', $product))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Formulas/Show')
            ->where('formula.blurb', 'Custom storefront blurb')
            ->where('formula.taste', 'Custom taste note')
            ->where('formula.ritual', $formula['ritual'])
        );
});
