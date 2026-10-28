<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);
        $price = fake()->randomFloat(2, 8, 45);

        return [
            'category_id' => Category::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'sku' => strtoupper(fake()->unique()->bothify('WL-####')),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraphs(2, true),
            'bottle_size' => null,
            'pack_units' => 1,
            'taste' => null,
            'ritual' => null,
            'highlights' => null,
            'ingredients' => null,
            'price' => $price,
            'compare_at_price' => fake()->boolean(30) ? round($price * 1.25, 2) : null,
            'image_path' => null,
            'stock_quantity' => fake()->numberBetween(0, 100),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 20),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
