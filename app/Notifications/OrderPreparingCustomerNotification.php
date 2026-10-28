<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderPreparingCustomerNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order)
    {
        $this->afterCommit();
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order->loadMissing('items');

        $mail = (new MailMessage)
            ->subject('Your Wellth order is being prepared')
            ->greeting('Hi '.$order->customer_name.',')
            ->line('Good news — we\'ve started preparing your fresh shots.')
            ->line('Order number: '.$order->uuid)
            ->line('Delivering to:')
            ->line($order->shipping_line1)
            ->line(trim(implode(', ', array_filter([
                $order->shipping_city,
                $order->shipping_region,
            ]))));

        foreach ($order->items as $item) {
            $mail->line(sprintf('%s × %d', $item->product_name, $item->quantity));
        }

        return $mail
            ->action('View order', route('checkout.confirmation', $order))
            ->line('We\'ll text and email you again when your order is on the way.');
    }
}
