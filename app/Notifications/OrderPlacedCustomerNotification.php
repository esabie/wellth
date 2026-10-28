<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderPlacedCustomerNotification extends Notification implements ShouldQueue
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
            ->subject('We received your Wellth order — '.$order->uuid)
            ->greeting('Thanks, '.$order->customer_name.'!')
            ->line('Your order is in. Next step: send your MoMo payment using the details on the payment page, then upload your screenshot.')
            ->line('Order number: '.$order->uuid)
            ->line('Amount due: '.$currency.' '.$total)
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
            ->action('Complete payment', route('checkout.payment', $order))
            ->line('Shipping details will be shared when your order is about to be dispatched. Keep this email for your order number.');
    }
}
