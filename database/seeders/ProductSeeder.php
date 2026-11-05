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
        $juices = Category::query()->where('slug', 'cold-pressed-juices')->firstOrFail();
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
            'short_description' => 'A ready-made mixed box of all six shots — no choosing required.',
            'description' => 'Twelve bottles for the week ahead: two of each Wellth formula. Ginja, Glow Getter, Ruby Rush, Crave Control, Belly Reset, and ABC — mixed, packed, and ready for your daily ritual.',
            'bottle_size' => '12 × 60ml',
            'pack_units' => 12,
            'taste' => null,
            'ritual' => 'One shot a day. Let the mix rotate through your week.',
            'highlights' => [
                '2 × every Wellth formula',
                'Ready-made — no building required',
                'Best value way to try the full range',
            ],
            'ingredients' => null,
            'price' => 240.00,
            'compare_at_price' => null,
            'stock_quantity' => $bouquet->exists ? $bouquet->stock_quantity : 40,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        if (blank($bouquet->image_path)) {
            $bouquet->image_path = '/images/formulas/bouquet.jpg';
        }

        $bouquet->save();

        Product::query()->updateOrCreate(
            ['slug' => 'cloudy-carrot'],
            [
                'category_id' => $juices->id,
                'name' => 'Cloudy Carrot',
                'sku' => 'WL-CLOUDY-CARROT',
                'short_description' => 'Carrot, banana and milk, warmed with cinnamon.',
                'description' => 'Sweet carrot and banana blended with milk and condensed milk, finished with cinnamon. A creamy, naturally cloudy 300ml drink for the everyday ritual.',
                'bottle_size' => '300ml',
                'pack_units' => 1,
                'taste' => 'Sweet carrot and banana with a warm cinnamon finish.',
                'ritual' => 'Best chilled, with breakfast or as an afternoon lift.',
                'highlights' => [
                    'Carrot, banana, milk and cinnamon',
                    'Creamy and naturally cloudy',
                    'Blended and bottled fresh',
                ],
                'ingredients' => [
                    ['name' => 'Carrot', 'image' => '/images/ingredients/carrot.jpg'],
                    ['name' => 'Milk', 'image' => '/images/ingredients/milk.jpg'],
                    ['name' => 'Condensed milk', 'image' => '/images/ingredients/condensed-milk.jpg'],
                    ['name' => 'Cinnamon', 'image' => '/images/ingredients/cinnamon.jpg'],
                    ['name' => 'Banana', 'image' => '/images/ingredients/banana.jpg'],
                ],
                'price' => 20.00,
                'compare_at_price' => null,
                'stock_quantity' => 50,
                'image_path' => '/images/formulas/cloudy-carrot.jpg',
                'is_active' => true,
                'sort_order' => $sort,
            ],
        );

        Product::query()
            ->whereNotIn('slug', $formulaSlugs->push('weekly-shot-pack', 'cloudy-carrot')->all())
            ->update(['is_active' => false]);
    }
}
