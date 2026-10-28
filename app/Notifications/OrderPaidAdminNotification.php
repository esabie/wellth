<?php

namespace App\Notifications;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderPaidAdminNotification extends Notification implements ShouldQueue
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
        return $notifiable instanceof User ? ['database'] : ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order->loadMissing('items');
        $currency = $order->currency ?: config('shop.currency', 'GHS');
        $total = number_format((float) $order->total, 2);
        $itemCount = $order->items->sum('quantity');

        $mail = (new MailMessage)
            ->subject('New paid Wellth order — '.$order->uuid)
            ->greeting('New paid order')
            ->line(sprintf('%s just paid %s %s for %d shot(s).', $order->customer_name, $currency, $total, $itemCount))
            ->line('Phone: '.$order->customer_phone)
            ->line('Email: '.$order->customer_email)
            ->line('Deliver to: '.$order->shipping_line1.', '.trim(implode(', ', array_filter([
                $order->shipping_city,
                $order->shipping_region,
            ]))));

        foreach ($order->items as $item) {
            $mail->line(sprintf('%s × %d', $item->product_name, $item->quantity));
        }

        return $mail
            ->action('Open in admin', OrderResource::getUrl('edit', ['record' => $order]))
            ->line('Paystack reference: '.$order->paystack_reference);
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $order = $this->order->loadMissing('items');
        $currency = $order->currency ?: config('shop.currency', 'GHS');
        $total = number_format((float) $order->total, 2);
        $itemCount = $order->items->sum('quantity');

        return FilamentNotification::make()
            ->title('Order paid')
            ->body(sprintf(
                '%s paid %s %s for %d shot(s) (%s).',
                $order->customer_name,
                $currency,
                $total,
                $itemCount,
                $order->uuid,
            ))
            ->icon('heroicon-o-check-circle')
            ->success()
            ->actions([
                Action::make('open')
                    ->button()
                    ->url(OrderResource::getUrl('edit', ['record' => $order]))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
