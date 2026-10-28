<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Order')
                    ->schema([
                        TextEntry::make('uuid')
                            ->label('Order number')
                            ->copyable(),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('total')
                            ->money(config('shop.currency', 'GHS')),
                        TextEntry::make('subtotal')
                            ->money(config('shop.currency', 'GHS')),
                        TextEntry::make('discount_amount')
                            ->label('Total discount')
                            ->money(config('shop.currency', 'GHS')),
                        TextEntry::make('loyalty_discount_amount')
                            ->label('Loyalty discount')
                            ->money(config('shop.currency', 'GHS')),
                        TextEntry::make('promo_code')
                            ->placeholder('-'),
                        TextEntry::make('points_redeemed')
                            ->label('Points redeemed')
                            ->placeholder('0'),
                        TextEntry::make('points_discount_amount')
                            ->label('Points discount')
                            ->money(config('shop.currency', 'GHS')),
                        TextEntry::make('points_earned')
                            ->label('Points earned')
                            ->placeholder('-'),
                        TextEntry::make('points_redeem_status')
                            ->label('Points redeem status')
                            ->badge(),
                        TextEntry::make('shipping_amount')
                            ->label('Delivery')
                            ->money(config('shop.currency', 'GHS')),
                        TextEntry::make('paystack_reference')
                            ->label('Payment reference')
                            ->copyable(),
                        TextEntry::make('paid_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('prepared_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('shipped_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('created_at')
                            ->dateTime(),
                    ])
                    ->columns(2),
                Section::make('Payment proof')
                    ->description(fn ($record): string => filled($record?->payment_proof_path)
                        ? 'MoMo screenshot uploaded by the customer.'
                        : 'No screenshot yet. It appears here after the customer uploads proof on the payment page.')
                    ->schema([
                        TextEntry::make('payment_submitted_at')
                            ->label('Uploaded at')
                            ->dateTime()
                            ->placeholder('Not uploaded'),
                        ImageEntry::make('payment_proof_path')
                            ->label('Screenshot')
                            ->disk('local')
                            ->visibility('private')
                            ->height(320)
                            ->placeholder('No screenshot uploaded yet')
                            ->visible(fn ($record): bool => filled($record?->payment_proof_path)),
                    ])
                    ->columns(1),
                Section::make('Customer')
                    ->schema([
                        TextEntry::make('customer_name'),
                        TextEntry::make('customer_email')
                            ->copyable(),
                        TextEntry::make('customer_phone')
                            ->copyable(),
                    ])
                    ->columns(2),
                Section::make('Delivery')
                    ->schema([
                        TextEntry::make('shipping_line1'),
                        TextEntry::make('shipping_line2')
                            ->placeholder('-'),
                        TextEntry::make('shipping_city'),
                        TextEntry::make('shipping_region')
                            ->placeholder('-'),
                        TextEntry::make('shipping_notes')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
