<?php

namespace App\Actions;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RestoreOrderStock
{
    public function handle(Order $order): Order
    {
        if ($order->stock_decremented_at === null) {
            return $order;
        }

        return DB::transaction(function () use ($order): Order {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->stock_decremented_at === null) {
                return $order;
            }

            $order->loadMissing('items');

            foreach ($order->items as $item) {
                if ($item->product_id === null) {
                    continue;
                }

                $product = Product::query()
                    ->whereKey($item->product_id)
                    ->lockForUpdate()
                    ->first();

                if ($product === null) {
                    continue;
                }

                $previous = $product->stock_quantity;
                $next = $previous + $item->quantity;

                $product->update(['stock_quantity' => $next]);

                Log::info('fulfilment.stock_restored', [
                    'order' => $order->uuid,
                    'product_id' => $product->id,
                    'slug' => $product->slug,
                    'quantity' => $item->quantity,
                    'previous_stock' => $previous,
                    'stock' => $next,
                ]);
            }

            $order->update(['stock_decremented_at' => null]);

            return $order->refresh();
        });
    }
}
