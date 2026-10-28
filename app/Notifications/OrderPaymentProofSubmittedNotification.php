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

class OrderPaymentProofSubmittedNotification extends Notification implements ShouldQueue
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

        return (new MailMessage)
            ->subject('Payment proof uploaded — '.$order->uuid)
            ->greeting('Payment proof ready to review')
            ->line(sprintf(
                '%s uploaded MoMo proof for %s %s (%d shot(s)).',
                $order->customer_name,
                $currency,
                $total,
                $itemCount,
            ))
            ->line('Phone: '.$order->customer_phone)
            ->line('Email: '.$order->customer_email)
            ->line('Payment reference: '.$order->paymentReference())
            ->action('Review order', OrderResource::getUrl('view', ['record' => $order]))
            ->line('Confirm the transfer in MoMo, then mark the order as paid in admin.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $order = $this->order->loadMissing('items');
        $currency = $order->currency ?: config('shop.currency', 'GHS');
        $total = number_format((float) $order->total, 2);

        return FilamentNotification::make()
            ->title('Payment proof uploaded')
            ->body(sprintf(
                '%s uploaded MoMo proof for %s %s (%s).',
                $order->customer_name,
                $currency,
                $total,
                $order->uuid,
            ))
            ->icon('heroicon-o-photo')
            ->actions([
                Action::make('review')
                    ->button()
                    ->url(OrderResource::getUrl('view', ['record' => $order]))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
