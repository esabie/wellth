<?php

namespace App\Notifications;

use App\Filament\Resources\Feedback\FeedbackResource;
use App\Models\Feedback;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FeedbackSubmittedAdminNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Feedback $feedback)
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
        $feedback = $this->feedback->loadMissing(['order', 'product']);
        $order = $feedback->order;

        return (new MailMessage)
            ->subject('New customer feedback — '.$order->uuid)
            ->greeting('Customer feedback received')
            ->line(sprintf(
                '%s left feedback for order %s.',
                $order->customer_name,
                $order->uuid,
            ))
            ->line('Favorite blend: '.($feedback->product?->name ?? 'Unknown'))
            ->line('Shopping: '.$feedback->shopping_rating.'/5 · '.$feedback->shoppingLabel())
            ->line('Could improve: '.implode(', ', $feedback->improvementLabels()))
            ->when(filled($feedback->note), fn (MailMessage $mail): MailMessage => $mail->line('Note: '.$feedback->note))
            ->action('Open feedback', FeedbackResource::getUrl('view', ['record' => $feedback]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $feedback = $this->feedback->loadMissing(['order', 'product']);
        $order = $feedback->order;

        return FilamentNotification::make()
            ->title('Customer feedback')
            ->body(sprintf(
                '%s picked %s · shop %d/5 on order %s.',
                $order->customer_name,
                $feedback->product?->name ?? 'a blend',
                $feedback->shopping_rating,
                $order->uuid,
            ))
            ->icon('heroicon-o-chat-bubble-left-right')
            ->success()
            ->actions([
                Action::make('open')
                    ->button()
                    ->url(FeedbackResource::getUrl('view', ['record' => $feedback]))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
