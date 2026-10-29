<?php

use App\Models\User;
use Filament\Auth\Pages\EditProfile;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('guests are redirected to the admin login when opening the profile', function () {
    $this->get(EditProfile::getUrl())
        ->assertRedirect(Filament::getLoginUrl());
});

test('admins can change their password from the profile', function () {
    $user = User::factory()->create([
        'password' => 'password',
    ]);

    $this->actingAs($user);

    Livewire::test(EditProfile::class)
        ->fillForm([
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'new-password',
            'passwordConfirmation' => 'new-password',
            'currentPassword' => 'password',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
});

test('admins cannot change their password with the wrong current password', function () {
    $user = User::factory()->create([
        'password' => 'password',
    ]);

    $this->actingAs($user);

    Livewire::test(EditProfile::class)
        ->fillForm([
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'new-password',
            'passwordConfirmation' => 'new-password',
            'currentPassword' => 'wrong-password',
        ])
        ->call('save')
        ->assertHasFormErrors(['currentPassword' => 'The password is incorrect.']);

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
});
