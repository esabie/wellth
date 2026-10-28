<?php

namespace App\Http\Controllers;

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

        return Inertia::render('Home', [
            'formulas' => $formulaCards,
        ]);
    }
}
