<?php

use App\Filament\Resources\LoyaltyWallets\Pages\ListLoyaltyWallets;
use App\Filament\Resources\LoyaltyWallets\Pages\ViewLoyaltyWallet;
use App\Models\LoyaltyWallet;
use App\Models\Order;
use App\Models\PromoCode;
use App\Models\User;
use App\Support\LoyaltyPoints;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('admins can list loyalty wallets', function () {
    $wallet = LoyaltyWallet::factory()->withBalance(40)->create();

    Livewire::test(ListLoyaltyWallets::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$wallet]);
});

test('admins see the referrer name from a promo when the phone has no paid order', function () {
    $phone = '0244111222';

    $wallet = LoyaltyWallet::factory()->withBalance(20)->create([
        'phone_normalized' => PhoneNumber::normalize($phone),
    ]);

    PromoCode::factory()->forReferrer($phone, 'Kwame Boateng')->create();

    Livewire::test(ListLoyaltyWallets::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$wallet])
        ->assertSee('Kwame Boateng');
});

test('admins prefer the paid order customer name over the promo referrer name', function () {
    $phone = '0244111222';

    $wallet = LoyaltyWallet::factory()->withBalance(20)->create([
        'phone_normalized' => PhoneNumber::normalize($phone),
    ]);

    PromoCode::factory()->forReferrer($phone, 'Promo Name')->create();

    Order::factory()->paid()->create([
        'customer_name' => 'Ama Mensah',
        'customer_phone' => $phone,
        'customer_phone_normalized' => PhoneNumber::normalize($phone),
    ]);

    Livewire::test(ListLoyaltyWallets::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$wallet])
        ->assertSee('Ama Mensah')
        ->assertDontSee('Promo Name');
});

test('admins can adjust a wallet balance from the table', function () {
    $wallet = LoyaltyWallet::factory()->withBalance(10)->create();

    Livewire::test(ListLoyaltyWallets::class)
        ->callTableAction('adjust', $wallet, [
            'delta' => 5,
            'note' => 'Manual top-up',
        ])
        ->assertNotified();

    expect($wallet->fresh()->balance)->toBe(15)
        ->and($wallet->transactions()->where('note', 'Manual top-up')->exists())->toBeTrue();
});

test('admins can view a loyalty wallet ledger', function () {
    $wallet = LoyaltyWallet::factory()->withBalance(10)->create();
    app(LoyaltyPoints::class)->adjustBalance($wallet, 5, 'Seed ledger');

    Livewire::test(ViewLoyaltyWallet::class, ['record' => $wallet->getKey()])
        ->assertOk();
});
