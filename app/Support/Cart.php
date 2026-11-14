<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

/**
 * @phpstan-type CartLine array{product_id: int, quantity: int, lactose_intolerant: bool|null, with_ginger: bool|null}
 * @phpstan-type CartSummaryItem array{
 *     key: string,
 *     product: array{
 *         id: int,
 *         name: string,
 *         slug: string,
 *         price: mixed,
 *         image: string|null,
 *         short_description: string|null,
 *         pack_units: int,
 *         is_drink: bool
 *     },
 *     quantity: int,
 *     line_total: string,
 *     lactose_intolerant: bool|null,
 *     lactose_label: string|null,
 *     with_ginger: bool|null,
 *     ginger_label: string|null,
 *     option_label: string|null
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

        /** @var array<int|string, array{product_id?: int, quantity?: int, lactose_intolerant?: bool|null, with_ginger?: bool|null}> $raw */
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
            $withGinger = array_key_exists('with_ginger', $item)
                ? (is_null($item['with_ginger']) ? null : (bool) $item['with_ginger'])
                : null;
            $key = $this->lineKey($productId, $lactose, $withGinger);

            $items[$key] = [
                'product_id' => $productId,
                'quantity' => (int) $item['quantity'],
                'lactose_intolerant' => $lactose,
                'with_ginger' => $withGinger,
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

    /**
     * Units that count toward the shop shot minimum (excludes drink-category products).
     */
    public function shotUnitCount(): int
    {
        return (int) $this->detailedItems()->sum(function (array $item): int {
            /** @var Product $product */
            $product = $item['product'];

            if ($product->isDrink()) {
                return 0;
            }

            return $item['quantity'] * max(1, (int) ($product->pack_units ?? 1));
        });
    }

    public function isDrinkOnlyCart(): bool
    {
        $items = $this->detailedItems();

        return $items->isNotEmpty()
            && $items->every(fn (array $item): bool => $item['product']->isDrink());
    }

    public function shopMinimumOrderQuantity(): int
    {
        return app(ShopSettings::class)->minimumOrderQuantity();
    }

    public function minimumOrderQuantity(): int
    {
        if ($this->isDrinkOnlyCart()) {
            return 1;
        }

        return $this->shopMinimumOrderQuantity();
    }

    public function minimumOrderErrorMessage(): string
    {
        $minimum = $this->minimumOrderQuantity();
        $remaining = $this->remainingForMinimumOrder();
        $unit = $this->isDrinkOnlyCart()
            ? ($minimum === 1 ? 'item' : 'items')
            : 'shots';

        return "Minimum order is {$minimum} {$unit}. Add {$remaining} more to continue to checkout.";
    }

    public function countTowardMinimum(): int
    {
        if ($this->isDrinkOnlyCart()) {
            return $this->count();
        }

        return $this->shotUnitCount();
    }

    public function meetsMinimumOrder(): bool
    {
        return $this->countTowardMinimum() >= $this->minimumOrderQuantity();
    }

    public function remainingForMinimumOrder(): int
    {
        return max(0, $this->minimumOrderQuantity() - $this->countTowardMinimum());
    }

    public function lineKey(int $productId, ?bool $lactoseIntolerant = null, ?bool $withGinger = null): string
    {
        if ($lactoseIntolerant !== null) {
            return $productId.':'.($lactoseIntolerant ? '1' : '0');
        }

        if ($withGinger !== null) {
            return $productId.':g:'.($withGinger ? '1' : '0');
        }

        return (string) $productId;
    }

    public function add(Product $product, int $quantity = 1, ?bool $lactoseIntolerant = null, ?bool $withGinger = null): void
    {
        if ($product->requiresLactoseChoice() && $lactoseIntolerant === null) {
            throw new \InvalidArgumentException('Choose a milk option for Cloudy Carrot.');
        }

        if ($product->requiresGingerChoice() && $withGinger === null) {
            throw new \InvalidArgumentException('Choose a ginger option for Tigernut Drink.');
        }

        if (! $product->requiresLactoseChoice()) {
            $lactoseIntolerant = null;
        }

        if (! $product->requiresGingerChoice()) {
            $withGinger = null;
        }

        $items = $this->items();
        $key = $this->lineKey($product->id, $lactoseIntolerant, $withGinger);

        if (isset($items[$key])) {
            $items[$key]['quantity'] += $quantity;
        } else {
            $items[$key] = [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'lactose_intolerant' => $lactoseIntolerant,
                'with_ginger' => $withGinger,
            ];
        }

        $this->store($items);
    }

    public function update(Product $product, int $quantity, ?bool $lactoseIntolerant = null, ?bool $withGinger = null): void
    {
        if ($product->requiresLactoseChoice() && $lactoseIntolerant === null) {
            throw new \InvalidArgumentException('Choose a milk option for Cloudy Carrot.');
        }

        if ($product->requiresGingerChoice() && $withGinger === null) {
            throw new \InvalidArgumentException('Choose a ginger option for Tigernut Drink.');
        }

        if (! $product->requiresLactoseChoice()) {
            $lactoseIntolerant = null;
        }

        if (! $product->requiresGingerChoice()) {
            $withGinger = null;
        }

        $items = $this->items();
        $key = $this->lineKey($product->id, $lactoseIntolerant, $withGinger);

        if ($quantity <= 0) {
            unset($items[$key]);
        } elseif (isset($items[$key])) {
            $items[$key]['quantity'] = $quantity;
        }

        $this->store($items);
    }

    public function remove(Product $product, ?bool $lactoseIntolerant = null, ?bool $withGinger = null): void
    {
        if (! $product->requiresLactoseChoice()) {
            $lactoseIntolerant = null;
        }

        if (! $product->requiresGingerChoice()) {
            $withGinger = null;
        }

        $items = $this->items();
        unset($items[$this->lineKey($product->id, $lactoseIntolerant, $withGinger)]);
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
     * @return Collection<int, array{key: string, product: Product, quantity: int, line_total: string, lactose_intolerant: bool|null, lactose_label: string|null, with_ginger: bool|null, ginger_label: string|null, option_label: string|null}>
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
            ->with('category:id,slug')
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
                $withGinger = $item['with_ginger'];
                $lactoseLabel = $this->lactoseLabel($lactose);
                $gingerLabel = $this->gingerLabel($withGinger);

                return [
                    'key' => $key,
                    'product' => $product,
                    'quantity' => $quantity,
                    'line_total' => bcmul((string) $product->price, (string) $quantity, 2),
                    'lactose_intolerant' => $lactose,
                    'lactose_label' => $lactoseLabel,
                    'with_ginger' => $withGinger,
                    'ginger_label' => $gingerLabel,
                    'option_label' => $lactoseLabel ?? $gingerLabel,
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
     *     minimum_order_quantity_shop: int,
     *     meets_minimum_order: bool,
     *     remaining_for_minimum_order: int,
     *     is_drink_only: bool,
     *     items: list<CartSummaryItem>
     * }
     */
    public function summary(): array
    {
        return [
            'count' => $this->count(),
            'subtotal' => $this->subtotal(),
            'minimum_order_quantity' => $this->minimumOrderQuantity(),
            'minimum_order_quantity_shop' => $this->shopMinimumOrderQuantity(),
            'meets_minimum_order' => $this->meetsMinimumOrder(),
            'remaining_for_minimum_order' => $this->remainingForMinimumOrder(),
            'is_drink_only' => $this->isDrinkOnlyCart(),
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
                            'is_drink' => $product->isDrink(),
                        ],
                        'quantity' => $item['quantity'],
                        'line_total' => $item['line_total'],
                        'lactose_intolerant' => $item['lactose_intolerant'],
                        'lactose_label' => $item['lactose_label'],
                        'with_ginger' => $item['with_ginger'],
                        'ginger_label' => $item['ginger_label'],
                        'option_label' => $item['option_label'],
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

        return $lactoseIntolerant ? 'Lactose free milk' : 'Full Cream Milk';
    }

    public function gingerLabel(?bool $withGinger): ?string
    {
        if ($withGinger === null) {
            return null;
        }

        return $withGinger ? 'With Ginger' : 'Without ginger';
    }

    public function optionLabel(?bool $lactoseIntolerant = null, ?bool $withGinger = null): ?string
    {
        return $this->lactoseLabel($lactoseIntolerant) ?? $this->gingerLabel($withGinger);
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
