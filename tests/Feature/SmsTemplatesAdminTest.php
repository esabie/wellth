<?php

use App\Filament\Pages\ManageSmsTemplates;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('sms templates are hidden from the admin panel for now', function () {
    expect(ManageSmsTemplates::shouldRegisterNavigation())->toBeFalse()
        ->and(ManageSmsTemplates::canAccess())->toBeFalse();

    $this->actingAs(User::factory()->create());

    Livewire::test(ManageSmsTemplates::class)->assertForbidden();
});
