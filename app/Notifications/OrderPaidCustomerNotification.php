<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderPaidCustomerNotification extends Notification implements ShouldQueue
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
        $currency = $order->currency ?: config('shop.currency', 'GHS');
        $total = number_format((float) $order->total, 2);

        $mail = (new MailMessage)
            ->subject('Your Wellth order is confirmed')
            ->greeting('Thanks, '.$order->customer_name.'!')
            ->line('We\'ve received your payment. Your shots are made fresh to order — please allow up to one week for preparation and delivery.')
            ->line('Order number: '.$order->uuid)
            ->line('Total paid: '.$currency.' '.$total)
            ->line('Delivering to:')
            ->line($order->shipping_line1)
            ->line(trim(implode(', ', array_filter([
                $order->shipping_city,
                $order->shipping_region,
            ]))));

        foreach ($order->items as $item) {
            $mail->line(sprintf(
                '%s × %d — %s %s',
                $item->product_name,
                $item->quantity,
                $currency,
                number_format((float) $item->line_total, 2),
            ));
        }

        return $mail
            ->action('View confirmation', route('checkout.confirmation', $order))
            ->line('If you have any questions about your preorder, just reply to this email.');
    }
}
