<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Line items';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product_name')
            ->columns([
                TextColumn::make('product_name')
                    ->label('Product')
                    ->searchable(),
                TextColumn::make('lactose_intolerant')
                    ->label('Milk')
                    ->placeholder('—')
                    ->formatStateUsing(fn (?bool $state): ?string => match ($state) {
                        true => 'Lactose free milk',
                        false => 'Full Cream Milk',
                        default => null,
                    }),
                TextColumn::make('with_ginger')
                    ->label('Ginger')
                    ->placeholder('—')
                    ->formatStateUsing(fn (?bool $state): ?string => match ($state) {
                        true => 'With Ginger',
                        false => 'Without ginger',
                        default => null,
                    }),
                TextColumn::make('product_sku')
                    ->label('SKU')
                    ->placeholder('-'),
                TextColumn::make('quantity')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('unit_price')
                    ->money(config('shop.currency', 'GHS'))
                    ->alignEnd(),
                TextColumn::make('line_total')
                    ->money(config('shop.currency', 'GHS'))
                    ->alignEnd(),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
