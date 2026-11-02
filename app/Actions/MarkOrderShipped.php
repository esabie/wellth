<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Notifications\OrderShippedCustomerNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class MarkOrderShipped
{
    public function handle(Order $order): Order
    {
        if ($order->status === OrderStatus::Shipped) {
            Log::info('fulfilment.ship_skipped', [
                'order' => $order->uuid,
                'status' => $order->status->value,
                'reason' => 'already_shipped',
            ]);

            return $order;
        }

        if ($order->status !== OrderStatus::Preparing) {
            Log::warning('fulfilment.ship_skipped', [
                'order' => $order->uuid,
                'status' => $order->status->value,
                'reason' => 'not_preparing',
            ]);

            return $order;
        }

        $previousStatus = $order->status->value;
        $didTransition = false;

        $order = DB::transaction(function () use ($order, &$didTransition): Order {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->status !== OrderStatus::Preparing) {
                return $order;
            }

            $order->update([
                'status' => OrderStatus::Shipped,
                'shipped_at' => $order->shipped_at ?? now(),
            ]);

            $didTransition = true;

            return $order->refresh()->load('items');
        });

        if (! $didTransition) {
            return $order;
        }

        Log::info('fulfilment.shipped', [
            'order' => $order->uuid,
            'previous_status' => $previousStatus,
            'status' => $order->status->value,
            'shipped_at' => $order->shipped_at?->toIso8601String(),
        ]);

        $this->notifyCustomer($order);

        return $order;
    }

    private function notifyCustomer(Order $order): void
    {
        if (blank($order->customer_email)) {
            return;
        }

        Notification::route('mail', $order->customer_email)
            ->notify(new OrderShippedCustomerNotification($order));

        Log::info('fulfilment.customer_shipped_email_queued', [
            'order' => $order->uuid,
            'email' => $order->customer_email,
        ]);
    }
}
