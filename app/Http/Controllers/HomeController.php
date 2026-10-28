<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\FormulaCatalog;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(FormulaCatalog $formulas): Response
    {
        $formulaCards = $formulas->all()->map(fn (array $formula): array => [
            'slug' => $formula['slug'],
            'name' => $formula['name'],
            'image' => $formula['image'],
            'points' => collect($formula['ingredients'])->pluck('name')->all(),
            'href' => route('shop.show', $formula['slug']),
        ]);

        $bouquet = Product::query()
            ->active()
            ->where('slug', 'weekly-shot-pack')
            ->first();

        return Inertia::render('Home', [
            'formulas' => $formulaCards,
            'bouquet' => $bouquet === null ? null : [
                'name' => $bouquet->name,
                'slug' => $bouquet->slug,
                'href' => route('shop.show', $bouquet),
                'blurb' => $bouquet->short_description,
                'price' => $bouquet->price,
                'compare_at_price' => $bouquet->compare_at_price,
                'contents' => [
                    '2 × Ginja',
                    '2 × Glow Getter',
                    '2 × Ruby Rush',
                    '2 × Crave Control',
                    '2 × Belly Reset',
                ],
            ],
        ]);
    }
}
