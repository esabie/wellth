<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

/**
 * @phpstan-type CartSummaryItem array{
 *     product: array{
 *         id: int,
 *         name: string,
 *         slug: string,
 *         price: mixed,
 *         image: string|null,
 *         short_description: string|null
 *     },
 *     quantity: int,
 *     line_total: string
 * }
 */
class Cart
{
    private const SESSION_KEY = 'cart';

    private const UPDATED_AT_KEY = 'cart_updated_at';

    /**
     * @return array<int, array{product_id: int, quantity: int}>
     */
    public function items(): array
    {
        $this->forgetIfExpired();

        /** @var array<int, array{product_id: int, quantity: int}> $items */
        $items = Session::get(self::SESSION_KEY, []);

        return $items;
    }

    public function count(): int
    {
        return (int) $this->detailedItems()->sum(
            fn (array $item): int => $item['quantity'] * max(1, (int) ($item['product']->pack_units ?? 1)),
        );
    }

    public function minimumOrderQuantity(): int
    {
        return app(ShopSettings::class)->minimumOrderQuantity();
    }

    public function meetsMinimumOrder(): bool
    {
        return $this->count() >= $this->minimumOrderQuantity();
    }

    public function remainingForMinimumOrder(): int
    {
        return max(0, $this->minimumOrderQuantity() - $this->count());
    }

    public function add(Product $product, int $quantity = 1): void
    {
        $items = $this->items();
        $productId = $product->id;

        if (isset($items[$productId])) {
            $items[$productId]['quantity'] += $quantity;
        } else {
            $items[$productId] = [
                'product_id' => $productId,
                'quantity' => $quantity,
            ];
        }

        $this->store($items);
    }

    public function update(Product $product, int $quantity): void
    {
        $items = $this->items();
        $productId = $product->id;

        if ($quantity <= 0) {
            unset($items[$productId]);
        } elseif (isset($items[$productId])) {
            $items[$productId]['quantity'] = $quantity;
        }

        $this->store($items);
    }

    public function remove(Product $product): void
    {
        $items = $this->items();
        unset($items[$product->id]);
        $this->store($items);
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
        Session::forget(self::UPDATED_AT_KEY);

        // Checkout discounts belong to the current cart attempt — don't carry into the next order.
        app(PromoCodes::class)->clear();
        app(LoyaltyPoints::class)->clearRedeem();
        app(SecondPurchaseDiscount::class)->clearPreviewPhone();
    }

    /**
     * @return Collection<int, array{product: Product, quantity: int, line_total: string}>
     */
    public function detailedItems(): Collection
    {
        $items = $this->items();

        if ($items === []) {
            return collect();
        }

        $products = Product::query()
            ->active()
            ->whereIn('id', array_keys($items))
            ->get()
            ->keyBy('id');

        return collect($items)
            ->filter(fn (array $item): bool => $products->has($item['product_id']))
            ->map(function (array $item) use ($products): array {
                /** @var Product $product */
                $product = $products->get($item['product_id']);
                $quantity = (int) $item['quantity'];

                return [
                    'product' => $product,
                    'quantity' => $quantity,
                    'line_total' => bcmul((string) $product->price, (string) $quantity, 2),
                ];
            })
            ->values();
    }

    public function subtotal(): string
    {
        return $this->detailedItems()
            ->reduce(
                fn (string $carry, array $item): string => bcadd($carry, $item['line_total'], 2),
                '0.00',
            );
    }

    /**
     * @return array{
     *     count: int,
     *     subtotal: string,
     *     minimum_order_quantity: int,
     *     meets_minimum_order: bool,
     *     remaining_for_minimum_order: int,
     *     items: list<CartSummaryItem>
     * }
     */
    public function summary(): array
    {
        return [
            'count' => $this->count(),
            'subtotal' => $this->subtotal(),
            'minimum_order_quantity' => $this->minimumOrderQuantity(),
            'meets_minimum_order' => $this->meetsMinimumOrder(),
            'remaining_for_minimum_order' => $this->remainingForMinimumOrder(),
            'items' => $this->detailedItems()
                ->map(function (array $item): array {
                    /** @var Product $product */
                    $product = $item['product'];

                    return [
                        'product' => [
                            'id' => $product->id,
                            'name' => $product->name,
                            'slug' => $product->slug,
                            'price' => $product->price,
                            'image' => $product->imageUrl(),
                            'short_description' => $product->short_description,
                        ],
                        'quantity' => $item['quantity'],
                        'line_total' => $item['line_total'],
                    ];
                })
                ->all(),
        ];
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $items
     */
    private function store(array $items): void
    {
        if ($items === []) {
            $this->clear();

            return;
        }

        Session::put(self::SESSION_KEY, $items);
        Session::put(self::UPDATED_AT_KEY, Carbon::now()->getTimestamp());
    }

    private function forgetIfExpired(): void
    {
        if (! Session::has(self::SESSION_KEY)) {
            Session::forget(self::UPDATED_AT_KEY);

            return;
        }

        $updatedAt = Session::get(self::UPDATED_AT_KEY);

        if ($updatedAt === null) {
            Session::put(self::UPDATED_AT_KEY, Carbon::now()->getTimestamp());

            return;
        }

        $ttlMinutes = max(1, (int) config('shop.cart_ttl_minutes', 20));
        $expiresAt = Carbon::createFromTimestamp((int) $updatedAt)->addMinutes($ttlMinutes);

        if (Carbon::now()->greaterThanOrEqualTo($expiresAt)) {
            $this->clear();
        }
    }
}
