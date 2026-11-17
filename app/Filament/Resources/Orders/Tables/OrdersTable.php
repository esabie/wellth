<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query
                ->withSum('items', 'quantity')
                ->withExists('feedback as has_feedback'))
            ->columns([
                TextColumn::make('uuid')
                    ->label('Order')
                    ->searchable()
                    ->copyable()
                    ->limit(13),
                TextColumn::make('customer_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('items_sum_quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->sortable()
                    ->placeholder('0'),
                TextColumn::make('total')
                    ->money(config('shop.currency', 'GHS'))
                    ->sortable(),
                TextColumn::make('feedback_sms_status')
                    ->label('Feedback SMS')
                    ->state(function (Order $record): string {
                        if ((bool) ($record->has_feedback ?? false)) {
                            return 'Received';
                        }

                        if ($record->feedback_invited_at !== null) {
                            return 'Sent';
                        }

                        return 'Not sent';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Received' => 'success',
                        'Sent' => 'warning',
                        default => 'gray',
                    })
                    ->toggleable(),
                TextColumn::make('customer_phone')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('paid_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('shipping_city')
                    ->toggleable(),
                TextColumn::make('customer_email')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(OrderStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
