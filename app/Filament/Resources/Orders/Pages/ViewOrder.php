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
                ->requiresConfirmation()
                ->modalHeading('Start preparing this order?')
                ->modalDescription('Moves the order to Preparing, reduces product stock, and notifies the customer by email and SMS.')
                ->visible(fn (): bool => $this->record->status === OrderStatus::Paid)
                ->action(function (StartPreparingOrder $startPreparingOrder): void {
                    $startPreparingOrder->handle($this->record);

                    Notification::make()
                        ->title('Preparing started')
                        ->body('Stock has been updated and the customer has been notified.')
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
                ->requiresConfirmation()
                ->modalHeading('Mark this order as shipped?')
                ->modalDescription('The order leaves the kitchen. Customers with an email are told it is on the way. The delivery text is sent when you mark it delivered.')
                ->visible(fn (): bool => $this->record->status === OrderStatus::Preparing)
                ->action(function (MarkOrderShipped $markOrderShipped): void {
                    $markOrderShipped->handle($this->record);

                    Notification::make()
                        ->title('Order shipped')
                        ->body('The order is on the way. Mark it delivered to text the customer.')
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
                ->label('Send feedback link')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Send feedback SMS?')
                ->modalDescription('Texts the customer a unique feedback link. Best a few days after delivery, once they have tried the shots.')
                ->visible(fn (): bool => $this->record->status === OrderStatus::Delivered
                    && $this->record->feedback()->doesntExist())
                ->action(function (SendOrderFeedbackInvite $sendInvite): void {
                    try {
                        $sendInvite->handle($this->record);
                    } catch (InvalidArgumentException $exception) {
                        Notification::make()
                            ->title($exception->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Feedback link sent')
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
