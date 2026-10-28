<?php

namespace App\Notifications;

use App\Enums\SmsTemplate;
use App\Models\Order;
use App\Notifications\Channels\MnotifyChannel;
use App\Support\SmsTemplates;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OrderPaymentProofSubmittedSmsNotification extends Notification implements ShouldQueue
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

        return app(SmsTemplates::class)->render(SmsTemplate::PaymentProofAdmin, [
            'order' => $order->uuid,
            'name' => $order->customer_name,
            'currency' => $order->currency ?: config('shop.currency', 'GHS'),
            'total' => number_format((float) $order->total, 2),
        ]);
    }
}
