<?php

use Database\Seeders\CategorySeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('home page renders formula cards without featured products', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->has('formulas', 5)
            ->where('bouquet', null)
            ->missing('featuredProducts')
        );
});

test('home page shows the wellth bouquet when the pack is active', function () {
    $this->seed(CategorySeeder::class);
    $this->seed(ProductSeeder::class);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->has('formulas', 5)
            ->where('bouquet.name', 'Wellth Bouquet')
            ->where('bouquet.slug', 'weekly-shot-pack')
            ->has('bouquet.contents', 5)
        );
});
