<?php

use App\Filament\Pages\ListCustomers;
use App\Filament\Pages\ListReferrers;
use App\Filament\Pages\Users;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\Order;
use App\Models\PromoCode;
use App\Models\User;
use App\Support\PhoneNumber;
use App\Support\ShopPeople;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['name' => 'Ops Admin']));
});

test('the users hub shows wellth people segments with counts', function () {
    Order::factory()->create([
        'customer_name' => 'Ama Mensah',
        'customer_phone' => '0244111222',
        'customer_phone_normalized' => PhoneNumber::normalize('0244111222'),
    ]);
    Order::factory()->create([
        'customer_name' => 'Ama Mensah',
        'customer_phone' => '0244111222',
        'customer_phone_normalized' => PhoneNumber::normalize('0244111222'),
    ]);
    Order::factory()->create([
        'customer_name' => 'Kojo Asante',
        'customer_phone' => '0200000111',
        'customer_phone_normalized' => PhoneNumber::normalize('0200000111'),
    ]);

    PromoCode::factory()->create([
        'referrer_phone' => '0555000111',
        'referrer_name' => 'Efua Referrer',
    ]);

    Livewire::test(Users::class)
        ->assertOk()
        ->assertSee('Customers')
        ->assertSee('Loyalty wallets')
        ->assertSee('Referrers')
        ->assertSee('Admin users')
        ->assertSee('Open list →');

    expect(app(ShopPeople::class)->customerCount())->toBe(2)
        ->and(app(ShopPeople::class)->referrerCount())->toBe(1)
        ->and(app(ShopPeople::class)->adminCount())->toBe(1);
});

test('admins can list unique customers from orders', function () {
    Order::factory()->create([
        'customer_name' => 'Ama Mensah',
        'customer_phone' => '0244111222',
        'customer_phone_normalized' => PhoneNumber::normalize('0244111222'),
        'total' => '100.00',
    ]);
    Order::factory()->create([
        'customer_name' => 'Ama Mensah',
        'customer_phone' => '0244111222',
        'customer_phone_normalized' => PhoneNumber::normalize('0244111222'),
        'total' => '50.00',
    ]);

    Livewire::test(ListCustomers::class)
        ->assertOk()
        ->assertSee('Ama Mensah')
        ->assertSee('0244111222')
        ->assertSee('Paid customers')
        ->assertCountTableRecords(1);
});

test('paid customers are shoppers with a confirmed payment', function () {
    Order::factory()->paid()->create([
        'customer_name' => 'Ama Mensah',
        'customer_phone' => '0244111222',
        'customer_phone_normalized' => PhoneNumber::normalize('0244111222'),
    ]);
    Order::factory()->shipped()->create([
        'customer_name' => 'Efua Boateng',
        'customer_phone' => '0555121212',
        'customer_phone_normalized' => PhoneNumber::normalize('0555121212'),
    ]);
    Order::factory()->create([
        'customer_name' => 'Kojo Asante',
        'customer_phone' => '0200000111',
        'customer_phone_normalized' => PhoneNumber::normalize('0200000111'),
    ]);

    Livewire::test(ListCustomers::class)
        ->assertOk()
        ->assertCountTableRecords(3)
        ->set('customerList', 'paid')
        ->assertCountTableRecords(2)
        ->assertSee('Ama Mensah')
        ->assertSee('Efua Boateng')
        ->assertDontSee('Kojo Asante');
});

test('admins can list unique referrers from promo codes', function () {
    PromoCode::factory()->create([
        'code' => 'AMA10',
        'referrer_phone' => '0555000111',
        'referrer_name' => 'Efua Referrer',
    ]);
    PromoCode::factory()->create([
        'code' => 'AMA20',
        'referrer_phone' => '0555000111',
        'referrer_name' => 'Efua Referrer',
    ]);

    Livewire::test(ListReferrers::class)
        ->assertOk()
        ->assertSee('Efua Referrer')
        ->assertSee('0555000111')
        ->assertCountTableRecords(1);
});

test('admins can manage staff accounts from the users hub', function () {
    Livewire::test(ManageUsers::class)
        ->assertOk()
        ->assertSee('Ops Admin')
        ->callAction('create', [
            'name' => 'New Admin',
            'email' => 'new-admin@wellth.test',
            'password' => 'password',
        ])
        ->assertHasNoActionErrors();

    expect(User::query()->where('email', 'new-admin@wellth.test')->exists())->toBeTrue();
});
