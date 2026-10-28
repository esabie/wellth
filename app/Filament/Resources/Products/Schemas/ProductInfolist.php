<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('category.name')
                    ->label('Category')
                    ->placeholder('-'),
                TextEntry::make('name'),
                TextEntry::make('slug'),
                TextEntry::make('sku')
                    ->label('SKU')
                    ->placeholder('-'),
                TextEntry::make('short_description')
                    ->label('Blurb')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('bottle_size')
                    ->placeholder('-'),
                TextEntry::make('description')
                    ->label('About this shot')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('taste')
                    ->label('Taste')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('ritual')
                    ->label('Daily ritual')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('highlights')
                    ->label('Why you\'ll love it')
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->placeholder('-')
                    ->columnSpanFull(),
                RepeatableEntry::make('ingredients')
                    ->schema([
                        TextEntry::make('name'),
                        ImageEntry::make('image')
                            ->placeholder('-'),
                    ])
                    ->columns(2)
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('price')
                    ->money(config('shop.currency', 'GHS')),
                TextEntry::make('compare_at_price')
                    ->money(config('shop.currency', 'GHS'))
                    ->placeholder('-'),
                ImageEntry::make('image_path')
                    ->placeholder('-'),
                TextEntry::make('stock_quantity')
                    ->numeric(),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('sort_order')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
