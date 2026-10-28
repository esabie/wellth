<?php

use App\Filament\Pages\Auth\Login;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('admins are redirected to the dashboard after login', function () {
    $user = User::factory()->create([
        'email' => 'admin@wellth.test',
        'password' => 'password',
    ]);

    // Simulate a prior intended URL that should be ignored.
    session()->put('url.intended', url('/admin/orders'));

    Livewire::test(Login::class)
        ->fillForm([
            'email' => $user->email,
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors()
        ->assertRedirect(Dashboard::getUrl());
});

test('already authenticated admins visiting login go to the dashboard', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(Login::class)
        ->assertRedirect(Filament::getUrl());
});
