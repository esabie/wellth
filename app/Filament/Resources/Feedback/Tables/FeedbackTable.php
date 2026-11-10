<?php

namespace App\Filament\Resources\Feedback\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FeedbackTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('order.uuid')
                    ->label('Order')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('order.customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('order.customer_phone')
                    ->label('Phone')
                    ->toggleable(),
                TextColumn::make('product.name')
                    ->label('Favourite blend')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('shopping_rating')
                    ->label('Shop')
                    ->formatStateUsing(fn ($state, $record): string => $state.'/5 · '.$record->shoppingLabel())
                    ->sortable(),
                TextColumn::make('improvement_tags')
                    ->label('Improve')
                    ->formatStateUsing(fn ($state, $record): string => implode(' · ', $record->improvementLabels()))
                    ->wrap(),
                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
