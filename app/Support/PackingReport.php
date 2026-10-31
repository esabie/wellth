<?php

namespace App\Support;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Builder;

class PackingReport
{
    public const BOUQUET_SLUG = 'weekly-shot-pack';

    /**
     * Bottles of each formula inside one Wellth Bouquet.
     *
     * @var array<string, int>
     */
    public const BOUQUET_FORMULAS = [
        'Ginja' => 2,
        'Glow Getter' => 2,
        'Ruby Rush' => 2,
        'Crave Control' => 2,
        'Belly Reset' => 2,
    ];

    /**
     * @param  Builder<Order>  $orders
     */
    public function csv(Builder $orders): string
    {
        $handle = fopen('php://temp', 'r+');

        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['Order', 'Customer', 'Phone', 'City', 'Status', 'Item', 'Quantity', 'Pack']);

        /** @var array<string, int> $totals */
        $totals = [];

        $loaded = (clone $orders)->with('items')->orderBy('paid_at')->orderBy('id')->get();

        foreach ($loaded as $order) {
            foreach ($order->items as $item) {
                fputcsv($handle, [
                    $order->uuid,
                    $order->customer_name,
                    $order->customer_phone,
                    $order->shipping_city,
                    $order->status->getLabel(),
                    $item->product_name,
                    $item->quantity,
                    $this->packNote($item),
                ]);

                foreach ($this->bottles($item) as $formula => $count) {
                    $totals[$formula] = ($totals[$formula] ?? 0) + $count;
                }
            }
        }

        ksort($totals);

        fputcsv($handle, []);
        fputcsv($handle, ['Formula', 'Bottles to pack']);

        foreach ($totals as $formula => $count) {
            fputcsv($handle, [$formula, $count]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $csv;
    }

    private function packNote(OrderItem $item): string
    {
        if (! $this->isBouquet($item)) {
            return $item->quantity.' × '.$item->product_name;
        }

        return collect(self::BOUQUET_FORMULAS)
            ->map(fn (int $each, string $formula): string => ($each * $item->quantity).' × '.$formula)
            ->implode(', ');
    }

    /**
     * @return array<string, int>
     */
    private function bottles(OrderItem $item): array
    {
        if (! $this->isBouquet($item)) {
            return [$item->product_name => $item->quantity];
        }

        $bottles = [];

        foreach (self::BOUQUET_FORMULAS as $formula => $each) {
            $bottles[$formula] = $each * $item->quantity;
        }

        return $bottles;
    }

    private function isBouquet(OrderItem $item): bool
    {
        return $item->product_slug === self::BOUQUET_SLUG;
    }
}
