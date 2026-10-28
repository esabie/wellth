<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Support\FormulaCatalog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request, FormulaCatalog $formulas): Response
    {
        $categorySlug = $request->string('category')->toString();
        $formulaSlugs = $formulas->all()->pluck('slug')->all();

        $categories = Category::query()
            ->active()
            ->ordered()
            ->whereHas(
                'products',
                fn ($productQuery) => $productQuery
                    ->active()
                    ->withStorefrontImage($formulaSlugs),
            )
            ->get(['id', 'name', 'slug']);

        $products = Product::query()
            ->active()
            ->withStorefrontImage($formulaSlugs)
            ->with('category:id,name,slug')
            ->when(
                $categorySlug !== '',
                fn ($query) => $query->whereHas(
                    'category',
                    fn ($categoryQuery) => $categoryQuery->where('slug', $categorySlug),
                ),
            )
            ->ordered()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'short_description' => $product->short_description,
                'price' => $product->price,
                'compare_at_price' => $product->compare_at_price,
                'bottle_size' => $product->bottle_size ?: ($formulas->find($product->slug)['bottle_size'] ?? null),
                'image' => $product->imageUrl($formulas),
                'category' => $product->category,
            ]);

        return Inertia::render('Shop/Index', [
            'products' => $products,
            'categories' => $categories,
            'filters' => [
                'category' => $categorySlug !== '' ? $categorySlug : null,
            ],
        ]);
    }

    public function show(Product $product, FormulaCatalog $formulas): Response
    {
        abort_unless($product->is_active, 404);

        $formula = $formulas->find($product->slug);

        if ($product->hasStoryContent() || $formula !== null) {
            return Inertia::render('Formulas/Show', [
                'formula' => $this->formulaPayload($product, $formula, $formulas),
            ]);
        }

        $product->load('category:id,name,slug');

        $relatedProducts = Product::query()
            ->active()
            ->whereKeyNot($product->id)
            ->when(
                $product->category_id,
                fn ($query) => $query->where('category_id', $product->category_id),
            )
            ->ordered()
            ->limit(4)
            ->get([
                'id',
                'category_id',
                'name',
                'slug',
                'short_description',
                'price',
                'compare_at_price',
                'bottle_size',
                'image_path',
            ])
            ->map(fn (Product $related): array => [
                'id' => $related->id,
                'name' => $related->name,
                'slug' => $related->slug,
                'short_description' => $related->short_description,
                'price' => $related->price,
                'compare_at_price' => $related->compare_at_price,
                'bottle_size' => $related->bottle_size ?: ($formulas->find($related->slug)['bottle_size'] ?? null),
                'image' => $related->imageUrl($formulas),
            ]);

        return Inertia::render('Shop/Show', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'short_description' => $product->short_description,
                'description' => $product->description,
                'price' => $product->price,
                'compare_at_price' => $product->compare_at_price,
                'image' => $product->imageUrl($formulas),
                'stock_quantity' => $product->stock_quantity,
                'category' => $product->category,
            ],
            'relatedProducts' => $relatedProducts,
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $formula
     * @return array<string, mixed>
     */
    private function formulaPayload(Product $product, ?array $formula, FormulaCatalog $formulas): array
    {
        $preferProductStory = $product->hasStoryContent();
        $highlights = $product->highlightList();
        $ingredients = $product->storefrontIngredients();

        return [
            'slug' => $product->slug,
            'name' => $product->name,
            'blurb' => $preferProductStory
                ? ($product->short_description ?: ($formula['blurb'] ?? ''))
                : ($formula['blurb'] ?? $product->short_description ?? ''),
            'description' => $preferProductStory
                ? ($product->description ?: ($formula['description'] ?? ''))
                : ($formula['description'] ?? $product->description ?? ''),
            'taste' => $preferProductStory
                ? ($product->taste ?: ($formula['taste'] ?? null))
                : ($formula['taste'] ?? $product->taste),
            'ritual' => $preferProductStory
                ? ($product->ritual ?: ($formula['ritual'] ?? null))
                : ($formula['ritual'] ?? $product->ritual),
            'highlights' => $preferProductStory && $highlights !== []
                ? $highlights
                : ($formula['highlights'] ?? $highlights),
            'image' => $product->imageUrl($formulas) ?: ($formula['image'] ?? null),
            'bottle_size' => $preferProductStory
                ? ($product->bottle_size ?: ($formula['bottle_size'] ?? null))
                : ($formula['bottle_size'] ?? $product->bottle_size),
            'price' => $product->price,
            'stock_quantity' => $product->stock_quantity,
            'ingredients' => $preferProductStory && $ingredients !== []
                ? $ingredients
                : ($formula['ingredients'] ?? $ingredients),
            'product_id' => $product->id,
        ];
    }
}
