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

        $sort = 1;
        foreach (app(FormulaCatalog::class)->all() as $formula) {
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

        Product::query()
            ->whereNotIn('slug', app(FormulaCatalog::class)->all()->pluck('product_slug'))
            ->update(['is_active' => false]);
    }
}
