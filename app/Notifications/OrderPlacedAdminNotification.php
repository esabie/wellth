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

class OrderPlacedAdminNotification extends Notification implements ShouldQueue
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
            ->subject('New Wellth order placed — '.$order->uuid)
            ->greeting('New order placed')
            ->line(sprintf(
                '%s placed an order for %s %s (%d shot(s)).',
                $order->customer_name,
                $currency,
                $total,
                $itemCount,
            ))
            ->line('Phone: '.$order->customer_phone)
            ->line('Email: '.$order->customer_email)
            ->line('Payment reference: '.$order->paymentReference())
            ->line('Status: awaiting MoMo payment proof.')
            ->action('Open order', OrderResource::getUrl('view', ['record' => $order]))
            ->line('You will get another alert when the customer uploads payment proof.');
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
            ->title('New order placed')
            ->body(sprintf(
                '%s ordered %d shot(s) for %s %s. Awaiting MoMo proof.',
                $order->customer_name,
                $itemCount,
                $currency,
                $total,
            ))
            ->icon('heroicon-o-shopping-bag')
            ->actions([
                Action::make('view')
                    ->button()
                    ->url(OrderResource::getUrl('view', ['record' => $order]))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
