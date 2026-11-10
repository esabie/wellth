<?php

namespace Database\Factories;

use App\Models\Feedback;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Feedback>
 */
class FeedbackFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory()->delivered(),
            'product_id' => Product::factory(),
            'shopping_rating' => fake()->numberBetween(3, 5),
            'improvement_tags' => ['all_good'],
            'note' => null,
        ];
    }

    public function forOrder(Order $order): static
    {
        return $this->state(function () use ($order): array {
            $item = $order->items()->first()
                ?? OrderItem::factory()->create(['order_id' => $order->id]);

            return [
                'order_id' => $order->id,
                'product_id' => $item->product_id,
            ];
        });
    }
}
