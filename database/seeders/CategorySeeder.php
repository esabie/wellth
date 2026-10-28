<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Wellness Shots',
                'slug' => 'wellness-shots',
                'description' => 'Concentrated daily shots for immunity, energy, and recovery.',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Cold-Pressed Juices',
                'slug' => 'cold-pressed-juices',
                'description' => 'Fresh cold-pressed juices bottled for everyday sipping.',
                'is_active' => false,
                'sort_order' => 2,
            ],
            [
                'name' => 'Bundles',
                'slug' => 'bundles',
                'description' => 'Curated packs for the week — mix shots and juices.',
                'is_active' => false,
                'sort_order' => 3,
            ],
        ];

        foreach ($categories as $category) {
            Category::query()->updateOrCreate(
                ['slug' => $category['slug']],
                $category,
            );
        }
    }
}
