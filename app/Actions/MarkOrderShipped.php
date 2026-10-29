<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Notifications\Channels\MnotifyChannel;
use App\Notifications\OrderShippedCustomerNotification;
use App\Notifications\OrderShippedCustomerSmsNotification;
use App\Support\PhoneNumber;
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
        if (filled($order->customer_email)) {
            Notification::route('mail', $order->customer_email)
                ->notify(new OrderShippedCustomerNotification($order));

            Log::info('fulfilment.customer_shipped_email_queued', [
                'order' => $order->uuid,
                'email' => $order->customer_email,
            ]);
        }

        if (! filled(config('services.mnotify.api_key'))) {
            Log::warning('fulfilment.customer_shipped_sms_skipped', [
                'order' => $order->uuid,
                'reason' => 'mnotify_not_configured',
            ]);

            return;
        }

        if (blank($order->customer_phone)) {
            Log::warning('fulfilment.customer_shipped_sms_skipped', [
                'order' => $order->uuid,
                'reason' => 'missing_customer_phone',
            ]);

            return;
        }

        Notification::route(MnotifyChannel::class, PhoneNumber::forSms($order->customer_phone))
            ->notify(new OrderShippedCustomerSmsNotification($order));

        Log::info('fulfilment.customer_shipped_sms_queued', [
            'order' => $order->uuid,
            'phone' => PhoneNumber::normalize((string) $order->customer_phone),
        ]);
    }
}
