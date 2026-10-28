<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Fulfilment')
                    ->schema([
                        Select::make('status')
                            ->options(OrderStatus::class)
                            ->required()
                            ->native(false),
                        Textarea::make('shipping_notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Customer')
                    ->schema([
                        TextInput::make('customer_name')
                            ->disabled(),
                        TextInput::make('customer_email')
                            ->disabled(),
                        TextInput::make('customer_phone')
                            ->disabled(),
                    ])
                    ->columns(2),
                Section::make('Delivery')
                    ->schema([
                        TextInput::make('shipping_line1')
                            ->disabled(),
                        TextInput::make('shipping_line2')
                            ->disabled(),
                        TextInput::make('shipping_city')
                            ->disabled(),
                        TextInput::make('shipping_region')
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }
}
