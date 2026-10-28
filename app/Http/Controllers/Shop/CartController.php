<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Models\Product;
use App\Support\Cart;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function index(Cart $cart): Response
    {
        $items = $cart->detailedItems()->map(fn (array $item): array => [
            'product' => [
                'id' => $item['product']->id,
                'name' => $item['product']->name,
                'slug' => $item['product']->slug,
                'price' => $item['product']->price,
                'image' => $item['product']->imageUrl(),
            ],
            'quantity' => $item['quantity'],
            'line_total' => $item['line_total'],
        ]);

        return Inertia::render('Cart/Index', [
            'items' => $items,
            'subtotal' => $cart->subtotal(),
        ]);
    }

    public function store(StoreCartItemRequest $request, Cart $cart): RedirectResponse
    {
        $validated = $request->validated();
        $product = Product::query()->findOrFail($validated['product_id']);

        $cart->add($product, (int) ($validated['quantity'] ?? 1));

        return redirect()
            ->back()
            ->with('success', $product->name.' added to cart.')
            ->with('open_cart', true);
    }

    public function update(UpdateCartItemRequest $request, Product $product, Cart $cart): RedirectResponse
    {
        $cart->update($product, (int) $request->validated('quantity'));

        return redirect()->back();
    }

    public function destroy(Product $product, Cart $cart): RedirectResponse
    {
        $cart->remove($product);

        return redirect()->back();
    }

    public function clear(Cart $cart): RedirectResponse
    {
        $cart->clear();

        return redirect()->back();
    }
}
