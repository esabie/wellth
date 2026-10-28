<?php

namespace App\Notifications;

use App\Models\Order;
use App\Notifications\Channels\MnotifyChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OrderPlacedAdminSmsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order)
    {
        $this->afterCommit();
    }

    /**
     * @return array<int, class-string>
     */
    public function via(object $notifiable): array
    {
        return [MnotifyChannel::class];
    }

    public function toMnotify(object $notifiable): string
    {
        $order = $this->order;
        $currency = $order->currency ?: config('shop.currency', 'GHS');
        $total = number_format((float) $order->total, 2);

        return "New order {$order->uuid} from {$order->customer_name}. Total {$currency} {$total}. Waiting for MoMo proof.";
    }
}
