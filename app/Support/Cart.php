<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

/**
 * @phpstan-type CartLine array{product_id: int, quantity: int, lactose_intolerant: bool|null}
 * @phpstan-type CartSummaryItem array{
 *     key: string,
 *     product: array{
 *         id: int,
 *         name: string,
 *         slug: string,
 *         price: mixed,
 *         image: string|null,
 *         short_description: string|null,
 *         pack_units: int
 *     },
 *     quantity: int,
 *     line_total: string,
 *     lactose_intolerant: bool|null,
 *     lactose_label: string|null
 * }
 */
class Cart
{
    private const SESSION_KEY = 'cart';

    private const UPDATED_AT_KEY = 'cart_updated_at';

    /**
     * @return array<string, CartLine>
     */
    public function items(): array
    {
        $this->forgetIfExpired();

        /** @var array<int|string, array{product_id?: int, quantity?: int, lactose_intolerant?: bool|null}> $raw */
        $raw = Session::get(self::SESSION_KEY, []);

        $items = [];

        foreach ($raw as $item) {
            if (! is_array($item) || ! isset($item['product_id'], $item['quantity'])) {
                continue;
            }

            $productId = (int) $item['product_id'];
            $lactose = array_key_exists('lactose_intolerant', $item)
                ? (is_null($item['lactose_intolerant']) ? null : (bool) $item['lactose_intolerant'])
                : null;
            $key = $this->lineKey($productId, $lactose);

            $items[$key] = [
                'product_id' => $productId,
                'quantity' => (int) $item['quantity'],
                'lactose_intolerant' => $lactose,
            ];
        }

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

    public function lineKey(int $productId, ?bool $lactoseIntolerant): string
    {
        if ($lactoseIntolerant === null) {
            return (string) $productId;
        }

        return $productId.':'.($lactoseIntolerant ? '1' : '0');
    }

    public function add(Product $product, int $quantity = 1, ?bool $lactoseIntolerant = null): void
    {
        if ($product->requiresLactoseChoice() && $lactoseIntolerant === null) {
            throw new \InvalidArgumentException('Choose whether you are lactose intolerant for Cloudy Carrot.');
        }

        if (! $product->requiresLactoseChoice()) {
            $lactoseIntolerant = null;
        }

        $items = $this->items();
        $key = $this->lineKey($product->id, $lactoseIntolerant);

        if (isset($items[$key])) {
            $items[$key]['quantity'] += $quantity;
        } else {
            $items[$key] = [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'lactose_intolerant' => $lactoseIntolerant,
            ];
        }

        $this->store($items);
    }

    public function update(Product $product, int $quantity, ?bool $lactoseIntolerant = null): void
    {
        if ($product->requiresLactoseChoice() && $lactoseIntolerant === null) {
            throw new \InvalidArgumentException('Choose whether you are lactose intolerant for Cloudy Carrot.');
        }

        if (! $product->requiresLactoseChoice()) {
            $lactoseIntolerant = null;
        }

        $items = $this->items();
        $key = $this->lineKey($product->id, $lactoseIntolerant);

        if ($quantity <= 0) {
            unset($items[$key]);
        } elseif (isset($items[$key])) {
            $items[$key]['quantity'] = $quantity;
        }

        $this->store($items);
    }

    public function remove(Product $product, ?bool $lactoseIntolerant = null): void
    {
        if (! $product->requiresLactoseChoice()) {
            $lactoseIntolerant = null;
        }

        $items = $this->items();
        unset($items[$this->lineKey($product->id, $lactoseIntolerant)]);
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
     * @return Collection<int, array{key: string, product: Product, quantity: int, line_total: string, lactose_intolerant: bool|null, lactose_label: string|null}>
     */
    public function detailedItems(): Collection
    {
        $items = $this->items();

        if ($items === []) {
            return collect();
        }

        $productIds = collect($items)->pluck('product_id')->unique()->all();

        $products = Product::query()
            ->active()
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        return collect($items)
            ->filter(fn (array $item): bool => $products->has($item['product_id']))
            ->map(function (array $item, string $key) use ($products): array {
                /** @var Product $product */
                $product = $products->get($item['product_id']);
                $quantity = (int) $item['quantity'];
                $lactose = $item['lactose_intolerant'];

                return [
                    'key' => $key,
                    'product' => $product,
                    'quantity' => $quantity,
                    'line_total' => bcmul((string) $product->price, (string) $quantity, 2),
                    'lactose_intolerant' => $lactose,
                    'lactose_label' => $this->lactoseLabel($lactose),
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
                        'key' => $item['key'],
                        'product' => [
                            'id' => $product->id,
                            'name' => $product->name,
                            'slug' => $product->slug,
                            'price' => $product->price,
                            'image' => $product->imageUrl(),
                            'short_description' => $product->short_description,
                            'pack_units' => max(1, (int) ($product->pack_units ?? 1)),
                        ],
                        'quantity' => $item['quantity'],
                        'line_total' => $item['line_total'],
                        'lactose_intolerant' => $item['lactose_intolerant'],
                        'lactose_label' => $item['lactose_label'],
                    ];
                })
                ->all(),
        ];
    }

    public function lactoseLabel(?bool $lactoseIntolerant): ?string
    {
        if ($lactoseIntolerant === null) {
            return null;
        }

        return $lactoseIntolerant ? 'Lactose intolerant' : 'Not lactose intolerant';
    }

    /**
     * @param  array<string, CartLine>  $items
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
