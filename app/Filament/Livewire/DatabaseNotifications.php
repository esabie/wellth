<?php

namespace App\Filament\Livewire;

use Filament\Livewire\DatabaseNotifications as BaseDatabaseNotifications;
use Filament\Support\Components\Attributes\Unexposed;
use Illuminate\Contracts\View\View;

class DatabaseNotifications extends BaseDatabaseNotifications
{
    #[Unexposed]
    public function render(): View
    {
        return view('filament.notifications.database-notifications');
    }
}
