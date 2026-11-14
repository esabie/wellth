<?php

use App\Actions\CreateCheckoutOrder;
use App\Models\Product;
use App\Support\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function tigernutDrink(): Product
{
    return Product::factory()->drink()->create([
        'name' => 'Tigernut Drink',
        'slug' => Product::TIGERNUT_SLUG,
        'price' => 45.00,
        'pack_units' => 1,
    ]);
}

test('tigernut cannot be added without a ginger choice', function () {
    $product = tigernutDrink();

    $this->from(route('shop.show', $product))
        ->post(route('cart.store'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ])
        ->assertRedirect(route('shop.show', $product))
        ->assertSessionHasErrors('with_ginger');

    expect(app(Cart::class)->count())->toBe(0);
});

test('tigernut can be added with a ginger choice and keeps separate cart lines', function () {
    $product = tigernutDrink();

    $this->post(route('cart.store'), [
        'product_id' => $product->id,
        'quantity' => 2,
        'with_ginger' => true,
    ])->assertSessionHas('open_cart', true);

    $this->post(route('cart.store'), [
        'product_id' => $product->id,
        'quantity' => 3,
        'with_ginger' => false,
    ])->assertSessionHas('open_cart', true);

    $summary = app(Cart::class)->summary();

    expect($summary['count'])->toBe(5)
        ->and($summary['items'])->toHaveCount(2)
        ->and(collect($summary['items'])->pluck('ginger_label')->sort()->values()->all())->toBe([
            'With Ginger',
            'Without ginger',
        ]);
});

test('other products do not require a ginger choice', function () {
    $product = Product::factory()->create([
        'slug' => 'ginja',
        'name' => 'Ginja',
    ]);

    $this->post(route('cart.store'), [
        'product_id' => $product->id,
        'quantity' => 1,
    ])->assertSessionHas('open_cart', true);

    expect(app(Cart::class)->count())->toBe(1)
        ->and(app(Cart::class)->summary()['items'][0]['with_ginger'])->toBeNull();
});

test('checkout stores the ginger preference on the order item', function () {
    $product = tigernutDrink();

    app(Cart::class)->add($product, 1, null, false);

    $order = app(CreateCheckoutOrder::class)->handle(app(Cart::class), [
        'customer_name' => 'Ama Mensah',
        'customer_phone' => '0244111222',
        'shipping_line1' => '12 Liberation Road',
        'shipping_city' => 'Accra',
        'shipping_region' => 'Greater Accra',
    ]);

    $tigernutLine = $order->items->firstWhere('product_slug', Product::TIGERNUT_SLUG);

    expect($tigernutLine)->not->toBeNull()
        ->and($tigernutLine->with_ginger)->toBeFalse()
        ->and($tigernutLine->gingerLabel())->toBe('Without ginger');
});
