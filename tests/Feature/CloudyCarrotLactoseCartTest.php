<?php

use App\Actions\CreateCheckoutOrder;
use App\Models\Product;
use App\Support\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function cloudyCarrot(): Product
{
    return Product::factory()->create([
        'name' => 'Cloudy Carrot',
        'slug' => Product::CLOUDY_CARROT_SLUG,
        'price' => 20.00,
        'pack_units' => 1,
    ]);
}

test('cloudy carrot cannot be added without a lactose choice', function () {
    $product = cloudyCarrot();

    $this->from(route('shop.show', $product))
        ->post(route('cart.store'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ])
        ->assertRedirect(route('shop.show', $product))
        ->assertSessionHasErrors('lactose_intolerant');

    expect(app(Cart::class)->count())->toBe(0);
});

test('cloudy carrot can be added with a lactose choice and keeps separate cart lines', function () {
    $product = cloudyCarrot();

    $this->post(route('cart.store'), [
        'product_id' => $product->id,
        'quantity' => 2,
        'lactose_intolerant' => true,
    ])->assertSessionHas('open_cart', true);

    $this->post(route('cart.store'), [
        'product_id' => $product->id,
        'quantity' => 3,
        'lactose_intolerant' => false,
    ])->assertSessionHas('open_cart', true);

    $summary = app(Cart::class)->summary();

    expect($summary['count'])->toBe(5)
        ->and($summary['items'])->toHaveCount(2)
        ->and(collect($summary['items'])->pluck('lactose_label')->sort()->values()->all())->toBe([
            'Lactose intolerant',
            'Not lactose intolerant',
        ]);
});

test('other products do not require a lactose choice', function () {
    $product = Product::factory()->create([
        'slug' => 'ginja',
        'name' => 'Ginja',
    ]);

    $this->post(route('cart.store'), [
        'product_id' => $product->id,
        'quantity' => 1,
    ])->assertSessionHas('open_cart', true);

    expect(app(Cart::class)->count())->toBe(1)
        ->and(app(Cart::class)->summary()['items'][0]['lactose_intolerant'])->toBeNull();
});

test('checkout stores the lactose preference on the order item', function () {
    $product = cloudyCarrot();
    $filler = Product::factory()->create(['price' => 20.00, 'pack_units' => 1]);

    app(Cart::class)->add($product, 2, true);
    app(Cart::class)->add($filler, 8);

    $order = app(CreateCheckoutOrder::class)->handle(app(Cart::class), [
        'customer_name' => 'Ama Mensah',
        'customer_phone' => '0244111222',
        'shipping_line1' => '12 Liberation Road',
        'shipping_city' => 'Accra',
        'shipping_region' => 'Greater Accra',
    ]);

    $carrotLine = $order->items->firstWhere('product_slug', Product::CLOUDY_CARROT_SLUG);

    expect($carrotLine)->not->toBeNull()
        ->and($carrotLine->lactose_intolerant)->toBeTrue()
        ->and($carrotLine->lactoseLabel())->toBe('Lactose intolerant');
});
