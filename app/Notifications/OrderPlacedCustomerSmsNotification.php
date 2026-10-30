<?php

namespace App\Notifications;

use App\Enums\SmsTemplate;
use App\Models\Order;
use App\Notifications\Channels\MnotifyChannel;
use App\Support\SmsTemplates;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OrderPlacedCustomerSmsNotification extends Notification implements ShouldQueue
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
        $paymentUrl = route('checkout.payment', $order);
        $total = (float) $order->total;

        $message = app(SmsTemplates::class)->render(SmsTemplate::OrderPlacedCustomer, [
            'first_name' => $firstName,
            'order' => $order->uuid,
            'currency' => $order->currency ?: config('shop.currency', 'GHS'),
            'total' => fmod($total, 1.0) === 0.0 ? number_format($total, 0) : number_format($total, 2),
            'payment_url' => $paymentUrl,
        ]);

        if (! str_contains($message, $paymentUrl)) {
            $message = trim($message.' '.$paymentUrl);
        }

        return $message;
    }
}
