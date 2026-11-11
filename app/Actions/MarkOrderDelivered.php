<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Notifications\Channels\MnotifyChannel;
use App\Notifications\OrderDeliveredCustomerSmsNotification;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class MarkOrderDelivered
{
    public function handle(Order $order): Order
    {
        if ($order->status === OrderStatus::Delivered) {
            Log::info('fulfilment.deliver_skipped', [
                'order' => $order->uuid,
                'status' => $order->status->value,
                'reason' => 'already_delivered',
            ]);

            return $order;
        }

        if ($order->status !== OrderStatus::Shipped) {
            Log::warning('fulfilment.deliver_skipped', [
                'order' => $order->uuid,
                'status' => $order->status->value,
                'reason' => 'not_shipped',
            ]);

            return $order;
        }

        $previousStatus = $order->status->value;
        $didTransition = false;

        $order = DB::transaction(function () use ($order, &$didTransition): Order {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->status !== OrderStatus::Shipped) {
                return $order;
            }

            $deliveredAt = $order->delivered_at ?? now();

            $order->update([
                'status' => OrderStatus::Delivered,
                'delivered_at' => $deliveredAt,
                'feedback_auto_invite_on' => Order::feedbackAutoInviteOnFor($deliveredAt),
            ]);

            $didTransition = true;

            return $order->refresh()->load('items');
        });

        if (! $didTransition) {
            return $order;
        }

        Log::info('fulfilment.delivered', [
            'order' => $order->uuid,
            'previous_status' => $previousStatus,
            'status' => $order->status->value,
            'delivered_at' => $order->delivered_at?->toIso8601String(),
            'feedback_auto_invite_on' => $order->feedback_auto_invite_on?->toDateString(),
        ]);

        $this->notifyCustomer($order);

        return $order;
    }

    private function notifyCustomer(Order $order): void
    {
        if (! filled(config('services.mnotify.api_key'))) {
            Log::warning('fulfilment.customer_delivered_sms_skipped', [
                'order' => $order->uuid,
                'reason' => 'mnotify_not_configured',
            ]);

            return;
        }

        if (blank($order->customer_phone)) {
            Log::warning('fulfilment.customer_delivered_sms_skipped', [
                'order' => $order->uuid,
                'reason' => 'missing_customer_phone',
            ]);

            return;
        }

        Notification::route(MnotifyChannel::class, PhoneNumber::forSms($order->customer_phone))
            ->notify(new OrderDeliveredCustomerSmsNotification($order));

        Log::info('fulfilment.customer_delivered_sms_queued', [
            'order' => $order->uuid,
            'phone' => PhoneNumber::normalize((string) $order->customer_phone),
        ]);
    }
}
