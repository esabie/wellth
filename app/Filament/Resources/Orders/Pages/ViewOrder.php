<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Actions\CompletePaidOrder;
use App\Actions\MarkOrderDelivered;
use App\Actions\MarkOrderShipped;
use App\Actions\SendOrderFeedbackInvite;
use App\Actions\StartPreparingOrder;
use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use InvalidArgumentException;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('confirmPayment')
                ->label('Confirm payment')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Confirm payment received?')
                ->modalDescription(fn (): string => $this->record->status === OrderStatus::Pending
                    ? 'Use this for cash or other offline payment. The order will be marked paid and the customer will get a confirmation email and SMS.'
                    : 'Mark this order as paid after verifying the Mobile Money transfer. The customer will receive a confirmation email and SMS.')
                ->visible(fn (): bool => $this->record->canConfirmPayment())
                ->action(function (CompletePaidOrder $completePaidOrder): void {
                    $completePaidOrder->handle($this->record);
                    $this->record->refresh();

                    Notification::make()
                        ->title('Payment confirmed')
                        ->body('The order is marked paid and the customer has been notified.')
                        ->success()
                        ->send();

                    $this->refreshFormData([
                        'status',
                        'paid_at',
                        'prepared_at',
                        'shipped_at',
                        'stock_decremented_at',
                    ]);
                }),
            Action::make('startPreparing')
                ->label('Start preparing')
                ->icon('heroicon-o-beaker')
                ->color('info')
                ->visible(fn (): bool => $this->record->status === OrderStatus::Paid)
                ->action(function (StartPreparingOrder $startPreparingOrder): void {
                    $startPreparingOrder->handle($this->record);
                    $this->record->refresh();

                    Notification::make()
                        ->title('Preparing started')
                        ->body('The order is now preparing and stock has been updated.')
                        ->success()
                        ->send();

                    $this->refreshFormData([
                        'status',
                        'paid_at',
                        'prepared_at',
                        'shipped_at',
                        'stock_decremented_at',
                    ]);
                }),
            Action::make('markShipped')
                ->label('Mark shipped')
                ->icon('heroicon-o-truck')
                ->color('primary')
                ->visible(fn (): bool => $this->record->status === OrderStatus::Preparing)
                ->action(function (MarkOrderShipped $markOrderShipped): void {
                    $markOrderShipped->handle($this->record);
                    $this->record->refresh();

                    Notification::make()
                        ->title('Order shipped')
                        ->body('The order is marked shipped.')
                        ->success()
                        ->send();

                    $this->refreshFormData([
                        'status',
                        'paid_at',
                        'prepared_at',
                        'shipped_at',
                        'delivered_at',
                        'stock_decremented_at',
                    ]);
                }),
            Action::make('markDelivered')
                ->label('Mark delivered')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Mark this order as delivered?')
                ->modalDescription('The customer will get a text that their shots have arrived. Send the feedback invite a few days later.')
                ->visible(fn (): bool => $this->record->status === OrderStatus::Shipped)
                ->action(function (MarkOrderDelivered $markOrderDelivered): void {
                    $markOrderDelivered->handle($this->record);
                    $this->record->refresh();

                    Notification::make()
                        ->title('Order delivered')
                        ->body('The customer has been texted that the order arrived.')
                        ->success()
                        ->send();

                    $this->refreshFormData([
                        'status',
                        'paid_at',
                        'prepared_at',
                        'shipped_at',
                        'delivered_at',
                        'stock_decremented_at',
                    ]);
                }),
            Action::make('sendFeedbackInvite')
                ->label(fn (): string => $this->record->feedback_invited_at
                    ? 'Resend feedback link'
                    : 'Send feedback link')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color(fn (): string => $this->record->feedback_invited_at ? 'warning' : 'gray')
                ->requiresConfirmation()
                ->modalHeading(fn (): string => $this->record->feedback_invited_at
                    ? 'Resend feedback SMS?'
                    : 'Send feedback SMS?')
                ->modalDescription(function (): string {
                    if ($this->record->feedback_invited_at) {
                        $sentAt = $this->record->feedback_invited_at
                            ->timezone(config('app.timezone'))
                            ->toDayDateTimeString();

                        return "This customer was already texted on {$sentAt}. Send again only if they did not receive it.";
                    }

                    return 'Texts the customer a unique feedback link. Best a few days after delivery, once they have tried the shots.';
                })
                ->visible(fn (): bool => $this->record->status === OrderStatus::Delivered
                    && $this->record->feedback()->doesntExist())
                ->action(function (SendOrderFeedbackInvite $sendInvite): void {
                    $wasPreviouslyInvited = $this->record->feedback_invited_at !== null;

                    try {
                        $sendInvite->handle($this->record);
                    } catch (InvalidArgumentException $exception) {
                        Notification::make()
                            ->title($exception->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    $this->record->refresh();

                    Notification::make()
                        ->title($wasPreviouslyInvited ? 'Feedback link resent' : 'Feedback link sent')
                        ->body('The customer has been texted their unique feedback URL.')
                        ->success()
                        ->send();

                    $this->refreshFormData([
                        'feedback_token',
                        'feedback_invited_at',
                    ]);
                }),
            EditAction::make()
                ->label('Update fulfilment'),
        ];
    }
}
