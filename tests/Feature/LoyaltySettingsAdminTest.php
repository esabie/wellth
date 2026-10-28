<?php

use App\Filament\Pages\ManageLoyaltySettings;
use App\Models\User;
use App\Support\ShopSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('admins can open the loyalty settings page', function () {
    Livewire::test(ManageLoyaltySettings::class)
        ->assertOk()
        ->assertFormSet([
            ShopSettings::SECOND_PURCHASE_ENABLED => true,
            ShopSettings::SECOND_PURCHASE_PERCENT => 5.0,
            ShopSettings::POINTS_ENABLED => false,
            ShopSettings::POINTS_PER_PAID_ORDER => 10,
            ShopSettings::POINT_VALUE_GHS => 0.1,
        ]);
});

test('admins can save loyalty discount settings', function () {
    Livewire::test(ManageLoyaltySettings::class)
        ->fillForm([
            ShopSettings::SECOND_PURCHASE_ENABLED => true,
            ShopSettings::SECOND_PURCHASE_PERCENT => 7.5,
            ShopSettings::SECOND_PURCHASE_LABEL => 'Returner perk 7.5% off',
            ShopSettings::POINTS_ENABLED => true,
            ShopSettings::POINTS_PER_PAID_ORDER => 15,
            ShopSettings::POINT_VALUE_GHS => 0.25,
        ])
        ->call('save')
        ->assertNotified();

    $loyalty = app(ShopSettings::class)->loyalty();

    expect($loyalty[ShopSettings::SECOND_PURCHASE_ENABLED])->toBeTrue()
        ->and($loyalty[ShopSettings::SECOND_PURCHASE_PERCENT])->toBe(7.5)
        ->and($loyalty[ShopSettings::SECOND_PURCHASE_LABEL])->toBe('Returner perk 7.5% off')
        ->and($loyalty[ShopSettings::POINTS_ENABLED])->toBeTrue()
        ->and($loyalty[ShopSettings::POINTS_PER_PAID_ORDER])->toBe(15)
        ->and($loyalty[ShopSettings::POINT_VALUE_GHS])->toBe(0.25);
});

test('admins can disable loyalty without wiping percent or label', function () {
    app(ShopSettings::class)->putLoyalty([
        ShopSettings::SECOND_PURCHASE_ENABLED => true,
        ShopSettings::SECOND_PURCHASE_PERCENT => 8,
        ShopSettings::SECOND_PURCHASE_LABEL => 'Comeback 8% off',
        ShopSettings::POINTS_ENABLED => true,
        ShopSettings::POINTS_PER_PAID_ORDER => 10,
        ShopSettings::POINT_VALUE_GHS => 0.10,
    ]);

    Livewire::test(ManageLoyaltySettings::class)
        ->fillForm([
            ShopSettings::SECOND_PURCHASE_ENABLED => false,
            ShopSettings::POINTS_ENABLED => false,
        ])
        ->call('save')
        ->assertNotified();

    $loyalty = app(ShopSettings::class)->loyalty();

    expect($loyalty[ShopSettings::SECOND_PURCHASE_ENABLED])->toBeFalse()
        ->and($loyalty[ShopSettings::SECOND_PURCHASE_PERCENT])->toBe(8.0)
        ->and($loyalty[ShopSettings::SECOND_PURCHASE_LABEL])->toBe('Comeback 8% off')
        ->and($loyalty[ShopSettings::POINTS_ENABLED])->toBeFalse()
        ->and($loyalty[ShopSettings::POINTS_PER_PAID_ORDER])->toBe(10)
        ->and($loyalty[ShopSettings::POINT_VALUE_GHS])->toBe(0.1);
});
