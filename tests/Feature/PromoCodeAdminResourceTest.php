<?php

use App\Enums\PromoCodeType;
use App\Filament\Resources\PromoCodes\Pages\CreatePromoCode;
use App\Filament\Resources\PromoCodes\Pages\EditPromoCode;
use App\Filament\Resources\PromoCodes\Pages\ListPromoCodes;
use App\Models\PromoCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('admins can list promo codes in filament', function () {
    $promo = PromoCode::factory()->create(['code' => 'WELCOME10']);

    Livewire::test(ListPromoCodes::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$promo]);
});

test('admins can create a promo code', function () {
    Livewire::test(CreatePromoCode::class)
        ->assertOk()
        ->fillForm([
            'code' => 'welcome10',
            'type' => PromoCodeType::Percent->value,
            'value' => 10,
            'label' => '10% off',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect(ListPromoCodes::getUrl());

    expect(PromoCode::query()->where('code', 'WELCOME10')->exists())->toBeTrue();
});

test('admins can create a referral promo with an optional referrer name', function () {
    Livewire::test(CreatePromoCode::class)
        ->assertOk()
        ->fillForm([
            'code' => 'ama10',
            'type' => PromoCodeType::Percent->value,
            'value' => 10,
            'is_active' => true,
            'referrer_phone' => '0244111222',
            'referrer_name' => 'Ama Mensah',
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect(ListPromoCodes::getUrl());

    $promo = PromoCode::query()->where('code', 'AMA10')->first();

    expect($promo)->not->toBeNull()
        ->and($promo->referrer_name)->toBe('Ama Mensah')
        ->and($promo->referrer_phone_normalized)->not->toBeNull();
});

test('admins can disable a promo code from the table', function () {
    $promo = PromoCode::factory()->create([
        'code' => 'PAUSEME',
        'is_active' => true,
    ]);

    Livewire::test(ListPromoCodes::class)
        ->callTableAction('disable', $promo)
        ->assertHasNoTableActionErrors();

    expect($promo->fresh()->is_active)->toBeFalse();
});

test('admins can edit a promo code', function () {
    $promo = PromoCode::factory()->create([
        'code' => 'EDITME',
        'value' => 10,
    ]);

    Livewire::test(EditPromoCode::class, ['record' => $promo->getKey()])
        ->assertOk()
        ->fillForm([
            'value' => 15,
            'label' => '15% off',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $promo->refresh();

    expect($promo->value)->toBe('15.00')
        ->and($promo->label)->toBe('15% off');
});
