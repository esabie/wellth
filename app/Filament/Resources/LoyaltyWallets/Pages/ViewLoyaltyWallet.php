<?php

namespace App\Filament\Resources\LoyaltyWallets\Pages;

use App\Filament\Resources\LoyaltyWallets\LoyaltyWalletResource;
use App\Support\LoyaltyPoints;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use InvalidArgumentException;

class ViewLoyaltyWallet extends ViewRecord
{
    protected static string $resource = LoyaltyWalletResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('adjust')
                ->label('Adjust balance')
                ->icon('heroicon-o-plus-circle')
                ->form([
                    TextInput::make('delta')
                        ->label('Points change')
                        ->helperText('Use a negative number to subtract points.')
                        ->numeric()
                        ->integer()
                        ->required(),
                    Textarea::make('note')
                        ->label('Note')
                        ->rows(2)
                        ->maxLength(255),
                ])
                ->action(function (array $data): void {
                    try {
                        app(LoyaltyPoints::class)->adjustBalance(
                            $this->record,
                            (int) $data['delta'],
                            $data['note'] ?? null,
                        );
                    } catch (InvalidArgumentException $exception) {
                        Notification::make()
                            ->title($exception->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    $this->record->refresh();

                    Notification::make()
                        ->title('Wallet balance updated')
                        ->success()
                        ->send();
                }),
        ];
    }
}
