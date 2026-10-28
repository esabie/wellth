<?php

namespace App\Filament\Resources\PromoCodes\Tables;

use App\Enums\PromoCodeType;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PromoCodesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('value')
                    ->label('Discount')
                    ->formatStateUsing(function ($state, $record): string {
                        return $record->type === PromoCodeType::Fixed
                            ? 'GHS '.number_format((float) $state, 2)
                            : rtrim(rtrim(number_format((float) $state, 2), '0'), '.').'% off';
                    }),
                TextColumn::make('min_order_amount')
                    ->label('Min. order')
                    ->money(config('shop.currency', 'GHS'))
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('used_count')
                    ->label('Used')
                    ->formatStateUsing(function ($state, $record): string {
                        if ($record->max_uses === null) {
                            return (string) $state;
                        }

                        return $state.'/'.$record->max_uses;
                    })
                    ->sortable(),
                TextColumn::make('referrer_name')
                    ->label('Referrer name')
                    ->placeholder('—')
                    ->toggleable()
                    ->searchable(),
                TextColumn::make('referrer_phone')
                    ->label('Referrer phone')
                    ->placeholder('—')
                    ->toggleable()
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('disable')
                    ->label('Disable')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record): bool => (bool) $record->is_active)
                    ->action(fn ($record) => $record->update(['is_active' => false])),
                Action::make('enable')
                    ->label('Enable')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record): bool => ! $record->is_active)
                    ->action(fn ($record) => $record->update(['is_active' => true])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
