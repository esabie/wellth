<?php

use App\Filament\Pages\Auth\Login;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('the admin login page shows a split layout with Wellth welcome copy', function () {
    $this->get('/hq/login')
        ->assertOk()
        ->assertSee('wellth-login', false)
        ->assertSee('wellth-login-visual', false)
        ->assertSee('Welcome back')
        ->assertSee('Fresh wellness shots, made in Ghana.');
});

test('admins are redirected to the dashboard after login', function () {
    $user = User::factory()->create([
        'email' => 'admin@wellth.test',
        'password' => 'password',
    ]);

    // Simulate a prior intended URL that should be ignored.
    session()->put('url.intended', url('/hq/orders'));

    Livewire::test(Login::class)
        ->fillForm([
            'email' => $user->email,
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors()
        ->assertRedirect(Dashboard::getUrl());
});

test('the old admin address opens the home page', function () {
    $this->get('/admin')->assertRedirect(route('home'));
    $this->get('/admin/login')->assertRedirect(route('home'));
});

test('already authenticated admins visiting login go to the dashboard', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(Login::class)
        ->assertRedirect(Filament::getUrl());
});
