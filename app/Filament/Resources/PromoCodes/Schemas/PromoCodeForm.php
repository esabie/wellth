<?php

namespace App\Filament\Resources\PromoCodes\Schemas;

use App\Enums\PromoCodeType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PromoCodeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Discount')
                    ->description('Code customers enter at checkout and the discount it applies.')
                    ->schema([
                        TextInput::make('code')
                            ->required()
                            ->maxLength(40)
                            ->unique(ignoreRecord: true)
                            ->extraInputAttributes(['class' => 'uppercase'])
                            ->helperText('Stored in uppercase.'),
                        Select::make('type')
                            ->options(PromoCodeType::class)
                            ->required()
                            ->native(false)
                            ->live(),
                        TextInput::make('value')
                            ->required()
                            ->numeric()
                            ->minValue(0.01)
                            ->prefix(fn (Get $get): string => $get('type') === PromoCodeType::Fixed->value ? 'GHS' : '')
                            ->suffix(fn (Get $get): ?string => $get('type') === PromoCodeType::Percent->value ? '%' : null)
                            ->helperText('Percentage (e.g. 10) or fixed GHS amount.'),
                        TextInput::make('label')
                            ->maxLength(120)
                            ->placeholder('e.g. 10% off')
                            ->helperText('Optional label shown in the order summary.'),
                        TextInput::make('min_order_amount')
                            ->label('Minimum order')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('GHS')
                            ->helperText('Leave blank for no minimum.'),
                        TextInput::make('max_uses')
                            ->label('Max uses')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Leave blank for unlimited redemptions.'),
                        TextInput::make('used_count')
                            ->label('Times used')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated()
                            ->visibleOn('edit'),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->required()
                            ->inline(false),
                    ])
                    ->columns(2),
                Section::make('Referral')
                    ->description('Optional. Add a referrer to turn this into a referral code: the shopper gets the discount, and the referrer earns points from Shop settings when the order is paid. Self-referral is blocked.')
                    ->schema([
                        TextInput::make('referrer_name')
                            ->label('Referrer name')
                            ->maxLength(120)
                            ->placeholder('e.g. John Mensah')
                            ->helperText('Shown on the loyalty wallet.'),
                        TextInput::make('referrer_phone')
                            ->label('Referrer phone')
                            ->tel()
                            ->maxLength(40)
                            ->placeholder('e.g. 024 123 4567')
                            ->helperText('Leave blank for a regular (non-referral) promo.'),
                    ])
                    ->columns(2),
            ]);
    }
}
