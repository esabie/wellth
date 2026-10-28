<?php

namespace App\Notifications;

use App\Enums\SmsTemplate;
use App\Models\Order;
use App\Notifications\Channels\MnotifyChannel;
use App\Support\SmsTemplates;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OrderShippedCustomerSmsNotification extends Notification implements ShouldQueue
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
        $firstName = str($order->customer_name)->trim()->before(' ')->toString() ?: 'there';
        $citySuffix = filled($order->shipping_city) ? " to {$order->shipping_city}" : '';

        return app(SmsTemplates::class)->render(SmsTemplate::OrderShippedCustomer, [
            'first_name' => $firstName,
            'order' => $order->uuid,
            'city_suffix' => $citySuffix,
        ]);
    }
}
