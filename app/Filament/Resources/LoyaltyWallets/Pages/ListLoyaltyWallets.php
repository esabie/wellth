<?php

namespace App\Filament\Resources\LoyaltyWallets\Pages;

use App\Filament\Resources\LoyaltyWallets\LoyaltyWalletResource;
use Filament\Resources\Pages\ListRecords;

class ListLoyaltyWallets extends ListRecords
{
    protected static string $resource = LoyaltyWalletResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
