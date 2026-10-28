<?php

namespace App\Filament\Resources\LoyaltyWallets\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    protected static ?string $title = 'Ledger';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->recordTitleAttribute('type')
            ->columns([
                TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('points')
                    ->alignEnd(),
                TextColumn::make('balance_after')
                    ->label('Balance after')
                    ->alignEnd(),
                TextColumn::make('order.uuid')
                    ->label('Order')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('note')
                    ->placeholder('-')
                    ->wrap()
                    ->toggleable(),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
