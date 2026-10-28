<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Support\Components\Attributes\Unexposed;

class Login extends BaseLogin
{
    #[Unexposed]
    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->to(Filament::getUrl());

            return;
        }

        $this->form->fill();
    }
}
