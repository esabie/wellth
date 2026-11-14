<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Models\Product;
use App\Support\Cart;
use App\Support\StorefrontVisitLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function index(Cart $cart): Response
    {
        $items = $cart->detailedItems()->map(fn (array $item): array => [
            'key' => $item['key'],
            'product' => [
                'id' => $item['product']->id,
                'name' => $item['product']->name,
                'slug' => $item['product']->slug,
                'price' => $item['product']->price,
                'image' => $item['product']->imageUrl(),
            ],
            'quantity' => $item['quantity'],
            'line_total' => $item['line_total'],
            'lactose_intolerant' => $item['lactose_intolerant'],
            'lactose_label' => $item['lactose_label'],
            'with_ginger' => $item['with_ginger'],
            'ginger_label' => $item['ginger_label'],
            'option_label' => $item['option_label'],
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

        if ($product->isComingSoon()) {
            return back()->withErrors([
                'product_id' => 'This shot is coming soon and cannot be added yet.',
            ]);
        }

        $quantity = (int) ($validated['quantity'] ?? 1);
        $lactoseIntolerant = $product->requiresLactoseChoice()
            ? $request->boolean('lactose_intolerant')
            : null;
        $withGinger = $product->requiresGingerChoice()
            ? $request->boolean('with_ginger')
            : null;

        $cart->add($product, $quantity, $lactoseIntolerant, $withGinger);

        $preference = $cart->optionLabel($lactoseIntolerant, $withGinger);
        $note = $preference
            ? "Visitor added {$quantity} × {$product->name} ({$preference}) to the cart."
            : "Visitor added {$quantity} × {$product->name} to the cart.";

        app(StorefrontVisitLog::class)->note($request, $note);

        return redirect()
            ->back()
            ->with('success', $product->name.' added to cart.')
            ->with('open_cart', true);
    }

    public function update(UpdateCartItemRequest $request, Product $product, Cart $cart): RedirectResponse
    {
        $quantity = (int) $request->validated('quantity');
        $lactoseIntolerant = $product->requiresLactoseChoice()
            ? ($request->exists('lactose_intolerant') ? $request->boolean('lactose_intolerant') : null)
            : null;
        $withGinger = $product->requiresGingerChoice()
            ? ($request->exists('with_ginger') ? $request->boolean('with_ginger') : null)
            : null;

        if ($product->requiresLactoseChoice() && $lactoseIntolerant === null) {
            return back()->withErrors([
                'lactose_intolerant' => 'Please choose a milk option for Cloudy Carrot.',
            ]);
        }

        if ($product->requiresGingerChoice() && $withGinger === null) {
            return back()->withErrors([
                'with_ginger' => 'Please choose a ginger option for Tigernut Drink.',
            ]);
        }

        $key = $cart->lineKey($product->id, $lactoseIntolerant, $withGinger);
        $currentQuantity = (int) ($cart->items()[$key]['quantity'] ?? 0);

        if ($product->isComingSoon() && $quantity > $currentQuantity) {
            return back()->withErrors([
                'quantity' => 'This shot is coming soon and cannot be added yet.',
            ]);
        }

        $cart->update($product, $quantity, $lactoseIntolerant, $withGinger);

        return redirect()->back();
    }

    public function destroy(Request $request, Product $product, Cart $cart): RedirectResponse
    {
        $lactoseIntolerant = $product->requiresLactoseChoice()
            ? ($request->exists('lactose_intolerant') ? $request->boolean('lactose_intolerant') : null)
            : null;
        $withGinger = $product->requiresGingerChoice()
            ? ($request->exists('with_ginger') ? $request->boolean('with_ginger') : null)
            : null;

        $cart->remove($product, $lactoseIntolerant, $withGinger);

        return redirect()->back();
    }

    public function clear(Cart $cart): RedirectResponse
    {
        $cart->clear();

        return redirect()->back();
    }
}
