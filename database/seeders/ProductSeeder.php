<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Support\FormulaCatalog;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shots = Category::query()->where('slug', 'wellness-shots')->firstOrFail();
        $bundles = Category::query()->where('slug', 'bundles')->firstOrFail();
        $formulas = app(FormulaCatalog::class)->all();
        $formulaSlugs = $formulas->pluck('product_slug');

        $sort = 1;
        foreach ($formulas as $formula) {
            Product::query()->updateOrCreate(
                ['slug' => $formula['product_slug']],
                [
                    'category_id' => $shots->id,
                    'name' => $formula['name'],
                    'sku' => $formula['sku'],
                    'short_description' => $formula['blurb'],
                    'description' => $formula['description'],
                    'bottle_size' => $formula['bottle_size'] ?? null,
                    'taste' => $formula['taste'],
                    'ritual' => $formula['ritual'],
                    'highlights' => $formula['highlights'],
                    'ingredients' => $formula['ingredients'],
                    'price' => $formula['price'],
                    'compare_at_price' => null,
                    'stock_quantity' => $formula['stock_quantity'],
                    'image_path' => $formula['image'],
                    'is_active' => true,
                    'sort_order' => $sort++,
                ],
            );
        }

        $bouquet = Product::query()->firstOrNew(['slug' => 'weekly-shot-pack']);

        $bouquet->fill([
            'category_id' => $bundles->id,
            'name' => 'Wellth Bouquet',
            'sku' => 'WL-BOUQUET',
            'short_description' => 'A ready-made mixed box of all five shots — no choosing required.',
            'description' => 'Ten bottles for the week ahead: two of each Wellth formula. Ginja, Glow Getter, Ruby Rush, Crave Control, and Belly Reset — mixed, packed, and ready for your daily ritual.',
            'bottle_size' => '10 × 60ml',
            'pack_units' => 10,
            'taste' => null,
            'ritual' => 'One shot a day. Let the mix rotate through your week.',
            'highlights' => [
                '2 × every Wellth formula',
                'Ready-made — no building required',
                'Best value way to try the full range',
            ],
            'ingredients' => null,
            'price' => 160.00,
            'compare_at_price' => 200.00,
            'stock_quantity' => $bouquet->exists ? $bouquet->stock_quantity : 40,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        if (blank($bouquet->image_path)) {
            $bouquet->image_path = '/images/formulas/bouquet.jpg';
        }

        $bouquet->save();

        Product::query()
            ->whereNotIn('slug', $formulaSlugs->push('weekly-shot-pack')->all())
            ->update(['is_active' => false]);
    }
}
