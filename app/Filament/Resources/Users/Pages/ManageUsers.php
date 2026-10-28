<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Pages\Users;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    public function getTitle(): string
    {
        return 'Admin users';
    }

    public function getSubheading(): ?string
    {
        return 'Staff accounts that can sign in to the Wellth admin portal.';
    }

    public function getBreadcrumbs(): array
    {
        return [
            Users::getUrl() => 'Users',
            '' => 'Admin users',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
