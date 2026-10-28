<?php

namespace App\Support;

use Illuminate\Support\Collection;

class FormulaCatalog
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function all(): Collection
    {
        return collect($this->definitions())->values();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $slug): ?array
    {
        return $this->definitions()[$slug] ?? null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function definitions(): array
    {
        return [
            'ginja' => [
                'slug' => 'ginja',
                'name' => 'Ginja',
                'blurb' => 'Lemon and ginger, supporting normal immune function.',
                'description' => 'A bright, warming shot built around fresh ginger and lemon with a cayenne kick. Designed as a sharp daily ritual for immune support.',
                'taste' => 'Spicy-citrus heat with a clean ginger finish.',
                'ritual' => 'Best first thing in the morning, straight from the fridge.',
                'highlights' => [
                    'Immune-focused ginger and lemon blend',
                    'Cayenne for a bold warming kick',
                    'Cold-pressed and bottled fresh',
                ],
                'image' => '/images/formulas/ginger.jpg',
                'price' => 20.00,
                'sku' => 'WL-GINJA',
                'stock_quantity' => 64,
                'category' => 'Wellness Shots',
                'bottle_size' => '60ml',
                'product_slug' => 'ginja',
                'ingredients' => [
                    ['name' => 'Ginger', 'image' => '/images/ingredients/ginger.jpg'],
                    ['name' => 'Lemon', 'image' => '/images/ingredients/lemon.jpg'],
                    ['name' => 'Cayenne Pepper', 'image' => '/images/ingredients/cayenne.jpg'],
                    ['name' => 'Water', 'image' => null],
                ],
            ],
            'glow-getter' => [
                'slug' => 'glow-getter',
                'name' => 'Glow Getter',
                'blurb' => 'Turmeric and carrot, supporting normal hair, skin and nails.',
                'description' => 'A golden blend of turmeric and carrot with citrus brightness. Made for everyday radiance support without the fuss.',
                'taste' => 'Earthy turmeric sweetness with soft citrus lift.',
                'ritual' => 'Take mid-morning with breakfast or as an afternoon reset.',
                'highlights' => [
                    'Turmeric and carrot for radiance support',
                    'Clementine and lemon for brightness',
                    'Smooth enough for everyday sipping',
                ],
                'image' => '/images/formulas/turmeric.jpg',
                'price' => 20.00,
                'sku' => 'WL-GLOW',
                'stock_quantity' => 58,
                'category' => 'Wellness Shots',
                'bottle_size' => '60ml',
                'product_slug' => 'glow-getter',
                'ingredients' => [
                    ['name' => 'Carrot', 'image' => '/images/ingredients/carrot.jpg'],
                    ['name' => 'Turmeric', 'image' => '/images/ingredients/turmeric.jpg'],
                    ['name' => 'Lemon', 'image' => '/images/ingredients/lemon.jpg'],
                    ['name' => 'Clementine', 'image' => '/images/ingredients/clementine.jpg'],
                ],
            ],
            'ruby-rush' => [
                'slug' => 'ruby-rush',
                'name' => 'Ruby Rush',
                'blurb' => 'Beetroot with vitamin B12, supporting normal psychological function.',
                'description' => 'Deep beetroot with orange and pomegranate brightness, finished with vitamin B12 for clear daily focus support.',
                'taste' => 'Rich beetroot depth with juicy citrus and berry notes.',
                'ritual' => 'Ideal before deep work or as a midday focus boost.',
                'highlights' => [
                    'Beetroot-led blend with vitamin B12',
                    'Orange and pomegranate for brightness',
                    'Built for clear everyday focus',
                ],
                'image' => '/images/formulas/beetroot.jpg',
                'price' => 20.00,
                'sku' => 'WL-RUBY',
                'stock_quantity' => 51,
                'category' => 'Wellness Shots',
                'bottle_size' => '60ml',
                'product_slug' => 'ruby-rush',
                'ingredients' => [
                    ['name' => 'Beetroot', 'image' => '/images/ingredients/beetroot.jpg'],
                    ['name' => 'Orange', 'image' => '/images/ingredients/orange.jpg'],
                    ['name' => 'Pomegranate', 'image' => '/images/ingredients/pomegranate.jpg'],
                    ['name' => 'Lemon', 'image' => '/images/ingredients/lemon.jpg'],
                ],
            ],
            'crave-control' => [
                'slug' => 'crave-control',
                'name' => 'Crave Control',
                'blurb' => 'Apple with vitamin B12, supporting the reduction of tiredness and fatigue.',
                'description' => 'Crisp apple and cooling cucumber with mint and lemon. A clean green shot with vitamin B12 for everyday energy support.',
                'taste' => 'Fresh green apple with cool cucumber and mint.',
                'ritual' => 'Reach for it when energy dips mid-afternoon.',
                'highlights' => [
                    'Green apple freshness with vitamin B12',
                    'Cucumber and mint for a clean finish',
                    'Supports everyday energy and balance',
                ],
                'image' => '/images/formulas/green.jpg',
                'price' => 20.00,
                'sku' => 'WL-CRAVE',
                'stock_quantity' => 60,
                'category' => 'Wellness Shots',
                'bottle_size' => '60ml',
                'product_slug' => 'crave-control',
                'ingredients' => [
                    ['name' => 'Apple', 'image' => '/images/ingredients/apple.jpg'],
                    ['name' => 'Cucumber', 'image' => '/images/ingredients/cucumber.jpg'],
                    ['name' => 'Mint', 'image' => '/images/ingredients/mint.jpg'],
                    ['name' => 'Lemon', 'image' => '/images/ingredients/lemon.jpg'],
                ],
            ],
            'tropic-cool' => [
                'slug' => 'tropic-cool',
                'name' => 'Belly Reset',
                'blurb' => 'Pineapple and cucumber, supporting hydration and everyday refreshment.',
                'description' => 'Pineapple sweetness meets cucumber coolness, lifted with lemon and ginger. A bright shot for daily refreshment.',
                'taste' => 'Tropical pineapple with cool cucumber and a ginger edge.',
                'ritual' => 'Perfect post-workout or as a hot-day refresher.',
                'highlights' => [
                    'Hydrating pineapple and cucumber blend',
                    'Lemon brightness with ginger warmth',
                    'Light, refreshing, and easy to drink',
                ],
                'image' => '/images/formulas/tropical.jpg',
                'price' => 20.00,
                'sku' => 'WL-TROPIC',
                'stock_quantity' => 55,
                'category' => 'Wellness Shots',
                'bottle_size' => '60ml',
                'product_slug' => 'tropic-cool',
                'ingredients' => [
                    ['name' => 'Cucumber', 'image' => '/images/ingredients/cucumber.jpg'],
                    ['name' => 'Pineapple', 'image' => '/images/ingredients/pineapple.jpg'],
                    ['name' => 'Lemon', 'image' => '/images/ingredients/lemon.jpg'],
                    ['name' => 'Ginger', 'image' => '/images/ingredients/ginger.jpg'],
                ],
            ],
        ];
    }
}
