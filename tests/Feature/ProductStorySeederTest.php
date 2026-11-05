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

test('product seeder keeps cloudy carrot on the shop', function () {
    $this->seed(CategorySeeder::class);
    $this->seed(ProductSeeder::class);

    $product = Product::query()->where('slug', 'cloudy-carrot')->first();

    expect($product)->not->toBeNull()
        ->and($product->is_active)->toBeTrue()
        ->and($product->name)->toBe('Cloudy Carrot')
        ->and($product->bottle_size)->toBe('300ml')
        ->and($product->category->slug)->toBe('cold-pressed-juices')
        ->and($product->short_description)->toBe('Carrot, banana and milk, warmed with cinnamon.')
        ->and($product->storefrontIngredients())->toBe([
            ['name' => 'Carrot', 'image' => '/images/ingredients/carrot.jpg'],
            ['name' => 'Milk', 'image' => '/images/ingredients/milk.jpg'],
            ['name' => 'Condensed milk', 'image' => '/images/ingredients/condensed-milk.jpg'],
            ['name' => 'Cinnamon', 'image' => '/images/ingredients/cinnamon.jpg'],
            ['name' => 'Banana', 'image' => '/images/ingredients/banana.jpg'],
        ]);

    $this->get(route('shop.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Shop/Index')
            ->where('products.data', fn ($products) => collect($products)->contains(
                fn (array $item): bool => $item['name'] === 'Cloudy Carrot'
                    && $item['image'] === '/images/formulas/cloudy-carrot.jpg',
            ))
        );
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
