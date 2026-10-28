<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderShippedCustomerNotification extends Notification implements ShouldQueue
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
            ->subject('Your Wellth order is on the way')
            ->greeting('Hi '.$order->customer_name.',')
            ->line('Your shots have left our kitchen and are on the way.')
            ->line('Order number: '.$order->uuid)
            ->line('Delivering to:')
            ->line($order->shipping_line1)
            ->line(trim(implode(', ', array_filter([
                $order->shipping_city,
                $order->shipping_region,
            ]))));

        if (filled($order->shipping_notes)) {
            $mail->line('Delivery note: '.$order->shipping_notes);
        }

        foreach ($order->items as $item) {
            $mail->line(sprintf('%s × %d', $item->product_name, $item->quantity));
        }

        return $mail
            ->action('View order', route('checkout.confirmation', $order))
            ->line('Thanks for choosing Wellth — enjoy every shot.');
    }
}
