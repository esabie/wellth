<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Actions\CompletePaidOrder;
use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

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
                ->modalHeading('Confirm MoMo payment?')
                ->modalDescription('Mark this order as paid after verifying the Mobile Money transfer. The customer will receive a confirmation email and SMS.')
                ->visible(fn (): bool => $this->record->status === OrderStatus::AwaitingConfirmation)
                ->action(function (CompletePaidOrder $completePaidOrder): void {
                    $completePaidOrder->handle($this->record);

                    Notification::make()
                        ->title('Payment confirmed')
                        ->body('The order is marked paid and the customer has been notified.')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status', 'paid_at']);
                }),
            EditAction::make()
                ->label('Update fulfilment'),
        ];
    }
}
