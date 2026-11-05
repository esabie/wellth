<?php

use App\Enums\ProductTag;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('home page advertises the shop to search engines', function () {
    $siteName = config('app.name');

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<title>Organic wellness shots · '.$siteName.'</title>', false)
        ->assertSee('name="description" content="Organic wellness shots with bold flavor and clean ingredients, made fresh in Ghana for your daily ritual."', false)
        ->assertSee('rel="canonical" href="'.route('home').'"', false)
        ->assertSee('property="og:image" content="'.url('/images/formulas/bouquet.jpg').'"', false)
        ->assertSee('"@type":"Organization"', false)
        ->assertInertia(fn (Assert $page) => $page
            ->where('seo.title', 'Organic wellness shots · '.$siteName)
            ->where('seo.robots', 'index, follow')
        );
});

test('product page describes that shot for search and social cards', function () {
    $product = Product::factory()->create([
        'name' => 'Ginja',
        'slug' => 'ginja',
        'short_description' => 'Ginger, lemon and cayenne in a 60ml shot.',
        'price' => 20,
        'image_path' => '/images/formulas/ginger.jpg',
    ]);

    $this->get(route('shop.show', $product))
        ->assertOk()
        ->assertSee('<title>Ginja · '.config('app.name').'</title>', false)
        ->assertSee('name="description" content="Ginger, lemon and cayenne in a 60ml shot."', false)
        ->assertSee('rel="canonical" href="'.route('shop.show', $product).'"', false)
        ->assertSee('property="og:image" content="'.url('/images/formulas/ginger.jpg').'"', false)
        ->assertSee('"priceCurrency":"GHS"', false)
        ->assertSee('"price":"20.00"', false)
        ->assertSee('https://schema.org/InStock', false);
});

test('product descriptions drop html before they are published', function () {
    $product = Product::factory()->create([
        'name' => 'Ginja',
        'slug' => 'ginja-markup',
        'short_description' => '</script><script>alert(1)</script>',
        'image_path' => '/images/formulas/ginger.jpg',
    ]);

    $this->get(route('shop.show', $product))
        ->assertOk()
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('name="description" content="alert(1)"', false);
});

test('coming soon products stay searchable but are marked as a preorder', function () {
    $product = Product::factory()->create([
        'name' => 'ABC',
        'slug' => 'abc',
        'short_description' => 'Apple, beetroot and carrot.',
        'tags' => [ProductTag::ComingSoon->value],
        'image_path' => '/images/formulas/abc.jpg',
    ]);

    $this->get(route('shop.show', $product))
        ->assertOk()
        ->assertSee('content="index, follow"', false)
        ->assertSee('https://schema.org/PreOrder', false);
});

test('cart and checkout pages ask search engines not to index them', function () {
    $this->get(route('cart.index'))
        ->assertOk()
        ->assertSee('<title>Your cart · '.config('app.name').'</title>', false)
        ->assertSee('content="noindex, nofollow"', false);

    $order = Order::factory()->paid()->create();

    $this->get(route('checkout.confirmation', $order))
        ->assertOk()
        ->assertSee('<title>Order confirmed · '.config('app.name').'</title>', false)
        ->assertSee('content="noindex, nofollow"', false);
});

test('sitemap lists public shop pages and leaves out checkout', function () {
    $product = Product::factory()->create([
        'name' => 'Ginja',
        'slug' => 'ginja',
        'is_active' => true,
    ]);
    $hidden = Product::factory()->create([
        'name' => 'Retired',
        'slug' => 'retired-shot',
        'is_active' => false,
    ]);

    $this->get(route('sitemap'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee(route('home'), false)
        ->assertSee(route('shop.index'), false)
        ->assertSee(route('faqs'), false)
        ->assertSee(route('shop.show', $product), false)
        ->assertDontSee(route('shop.show', $hidden), false)
        ->assertDontSee(route('checkout.create'), false)
        ->assertDontSee(route('cart.index'), false);
});

test('robots file hides the cart checkout and admin and points at the sitemap', function () {
    $this->get(route('robots'))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSee("Disallow: /hq\nDisallow: /cart\nDisallow: /checkout", false)
        ->assertSee('Sitemap: '.route('sitemap'), false);
});
