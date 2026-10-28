<?php

namespace App\Filament\Resources\LoyaltyWallets\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LoyaltyWalletInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Wallet')
                    ->schema([
                        TextEntry::make('customer_name')
                            ->label('Name')
                            ->state(fn ($record): ?string => $record->customerName())
                            ->placeholder('—'),
                        TextEntry::make('phone_normalized')
                            ->label('Phone')
                            ->copyable(),
                        TextEntry::make('balance')
                            ->label('Points balance'),
                        TextEntry::make('updated_at')
                            ->dateTime(),
                        TextEntry::make('created_at')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }
}
