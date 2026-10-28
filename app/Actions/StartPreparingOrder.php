<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Notifications\OrderPreparingCustomerNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class StartPreparingOrder
{
    public function handle(Order $order): Order
    {
        if (in_array($order->status, [OrderStatus::Preparing, OrderStatus::Shipped], true)) {
            Log::info('fulfilment.prepare_skipped', [
                'order' => $order->uuid,
                'status' => $order->status->value,
                'reason' => 'already_preparing_or_shipped',
            ]);

            return $order;
        }

        if ($order->status !== OrderStatus::Paid) {
            Log::warning('fulfilment.prepare_skipped', [
                'order' => $order->uuid,
                'status' => $order->status->value,
                'reason' => 'not_paid',
            ]);

            return $order;
        }

        $previousStatus = $order->status->value;
        $didTransition = false;

        $order = DB::transaction(function () use ($order, &$didTransition): Order {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->status !== OrderStatus::Paid) {
                return $order;
            }

            $this->decrementStock($order);

            $order->update([
                'status' => OrderStatus::Preparing,
                'prepared_at' => $order->prepared_at ?? now(),
                'stock_decremented_at' => $order->stock_decremented_at ?? now(),
            ]);

            $didTransition = true;

            return $order->refresh()->load('items');
        });

        if (! $didTransition) {
            return $order;
        }

        Log::info('fulfilment.preparing_started', [
            'order' => $order->uuid,
            'previous_status' => $previousStatus,
            'status' => $order->status->value,
            'prepared_at' => $order->prepared_at?->toIso8601String(),
        ]);

        $this->notifyCustomer($order);

        return $order;
    }

    private function decrementStock(Order $order): void
    {
        if ($order->stock_decremented_at !== null) {
            return;
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
                Log::warning('fulfilment.stock_skip_missing_product', [
                    'order' => $order->uuid,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                ]);

                continue;
            }

            $previous = $product->stock_quantity;
            $next = max(0, $previous - $item->quantity);

            $product->update(['stock_quantity' => $next]);

            Log::info('fulfilment.stock_decremented', [
                'order' => $order->uuid,
                'product_id' => $product->id,
                'slug' => $product->slug,
                'quantity' => $item->quantity,
                'previous_stock' => $previous,
                'stock' => $next,
            ]);
        }
    }

    private function notifyCustomer(Order $order): void
    {
        Notification::route('mail', $order->customer_email)
            ->notify(new OrderPreparingCustomerNotification($order));

        Log::info('fulfilment.customer_preparing_email_queued', [
            'order' => $order->uuid,
            'email' => $order->customer_email,
        ]);
    }
}
