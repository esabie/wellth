<?php

namespace App\Support;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Collection;

class OrderFeedbackChoices
{
    /**
     * Blends the customer can pick as a favourite for this order.
     * Bouquet packs expand into the individual formulas inside them.
     *
     * @return list<array{id: int, name: string, image: string|null, from_bouquet: bool}>
     */
    public function forOrder(Order $order): array
    {
        $order->loadMissing('items.product');

        $choices = collect();

        foreach ($order->items as $item) {
            if (blank($item->product_id)) {
                continue;
            }

            $slug = $item->product_slug ?: $item->product?->slug;

            if ($slug === PackingReport::BOUQUET_SLUG) {
                $choices = $choices->merge($this->bouquetBlends());

                continue;
            }

            $choices->push([
                'id' => (int) $item->product_id,
                'name' => $item->product_name,
                'image' => $item->product?->imageUrl(),
                'from_bouquet' => false,
            ]);
        }

        return $choices
            ->unique('id')
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, int>
     */
    public function allowedProductIds(Order $order): Collection
    {
        return collect($this->forOrder($order))->pluck('id')->map(fn ($id): int => (int) $id);
    }

    /**
     * @return Collection<int, array{id: int, name: string, image: string|null, from_bouquet: bool}>
     */
    private function bouquetBlends(): Collection
    {
        $products = Product::query()
            ->whereIn('slug', PackingReport::BOUQUET_PRODUCT_SLUGS)
            ->get()
            ->keyBy('slug');

        return collect(PackingReport::BOUQUET_PRODUCT_SLUGS)
            ->map(function (string $slug) use ($products): ?array {
                $product = $products->get($slug);

                if ($product === null) {
                    return null;
                }

                return [
                    'id' => (int) $product->id,
                    'name' => $product->name,
                    'image' => $product->imageUrl(),
                    'from_bouquet' => true,
                ];
            })
            ->filter()
            ->values();
    }
}
