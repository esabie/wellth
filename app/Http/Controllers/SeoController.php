<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /hq',
            'Disallow: /cart',
            'Disallow: /checkout',
            '',
            'Sitemap: '.route('sitemap'),
            '',
        ];

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    public function sitemap(): Response
    {
        $pages = [
            ['loc' => route('home'), 'priority' => '1.0'],
            ['loc' => route('shop.index'), 'priority' => '0.9'],
            ['loc' => route('faqs'), 'priority' => '0.5'],
        ];

        $products = Product::query()
            ->active()
            ->ordered()
            ->get(['slug', 'updated_at']);

        return response()
            ->view('seo.sitemap', [
                'pages' => $pages,
                'products' => $products,
            ])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
