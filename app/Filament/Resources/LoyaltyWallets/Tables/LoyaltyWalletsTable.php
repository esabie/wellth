<?php

namespace App\Filament\Resources\LoyaltyWallets\Tables;

use App\Support\LoyaltyPoints;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use InvalidArgumentException;

class LoyaltyWalletsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('customer_name')
                    ->label('Name')
                    ->getStateUsing(fn ($record): ?string => $record->customerName())
                    ->placeholder('—')
                    ->searchable(query: function ($query, string $search): void {
                        $query->where(function ($query) use ($search): void {
                            $query->whereHas('latestPaidOrder', function ($query) use ($search): void {
                                $query->where('customer_name', 'like', "%{$search}%");
                            })->orWhereHas('latestReferrerPromo', function ($query) use ($search): void {
                                $query->where('referrer_name', 'like', "%{$search}%");
                            });
                        });
                    }),
                TextColumn::make('phone_normalized')
                    ->label('Phone')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('balance')
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ViewAction::make(),
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
                    ->action(function ($record, array $data): void {
                        try {
                            app(LoyaltyPoints::class)->adjustBalance(
                                $record,
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

                        Notification::make()
                            ->title('Wallet balance updated')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([]);
    }
}
