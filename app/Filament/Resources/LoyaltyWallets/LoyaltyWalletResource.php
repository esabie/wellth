<?php

namespace App\Filament\Resources\LoyaltyWallets;

use App\Filament\Resources\LoyaltyWallets\Pages\ListLoyaltyWallets;
use App\Filament\Resources\LoyaltyWallets\Pages\ViewLoyaltyWallet;
use App\Filament\Resources\LoyaltyWallets\RelationManagers\TransactionsRelationManager;
use App\Filament\Resources\LoyaltyWallets\Schemas\LoyaltyWalletInfolist;
use App\Filament\Resources\LoyaltyWallets\Tables\LoyaltyWalletsTable;
use App\Models\LoyaltyWallet;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class LoyaltyWalletResource extends Resource
{
    protected static ?string $model = LoyaltyWallet::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    protected static string|UnitEnum|null $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'phone_normalized';

    protected static ?string $navigationLabel = 'Loyalty wallets';

    protected static ?string $modelLabel = 'loyalty wallet';

    protected static ?string $pluralModelLabel = 'loyalty wallets';

    public static function infolist(Schema $schema): Schema
    {
        return LoyaltyWalletInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LoyaltyWalletsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'latestPaidOrder',
            'latestReferrerPromo',
        ]);
    }

    public static function getRelations(): array
    {
        return [
            TransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLoyaltyWallets::route('/'),
            'view' => ViewLoyaltyWallet::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
