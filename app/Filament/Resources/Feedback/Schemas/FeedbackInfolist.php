<?php

namespace App\Filament\Resources\Feedback\Schemas;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FeedbackInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Customer')
                    ->schema([
                        TextEntry::make('order.uuid')
                            ->label('Order')
                            ->url(fn ($record): string => OrderResource::getUrl('view', ['record' => $record->order]))
                            ->color('primary'),
                        TextEntry::make('order.customer_name')
                            ->label('Name'),
                        TextEntry::make('order.customer_phone')
                            ->label('Phone')
                            ->copyable(),
                        TextEntry::make('created_at')
                            ->label('Submitted')
                            ->dateTime(),
                    ])
                    ->columns(2),
                Section::make('Feedback')
                    ->schema([
                        TextEntry::make('product.name')
                            ->label('Favourite blend'),
                        TextEntry::make('shopping_rating')
                            ->label('Shopping experience')
                            ->formatStateUsing(fn ($state, $record): string => $state.'/5 · '.$record->shoppingLabel()),
                        TextEntry::make('improvement_tags')
                            ->label('Could improve')
                            ->formatStateUsing(fn ($state, $record): string => implode(', ', $record->improvementLabels()))
                            ->columnSpanFull(),
                        TextEntry::make('note')
                            ->label('Optional note')
                            ->placeholder('—')
                            ->columnSpanFull()
                            ->prose(),
                    ]),
            ]);
    }
}
