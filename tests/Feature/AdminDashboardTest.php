<?php

use App\Enums\OrderStatus;
use App\Filament\Widgets\LatestOrders;
use App\Filament\Widgets\OrdersChart;
use App\Filament\Widgets\ShopStatsOverview;
use App\Models\LoyaltyWallet;
use App\Models\Order;
use App\Models\User;
use App\Support\PhoneNumber;
use Filament\Pages\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('the admin dashboard loads shop widgets instead of the filament defaults', function () {
    Order::factory()->paid()->create([
        'customer_name' => 'Ama Mensah',
        'total' => '200.00',
        'paid_at' => now(),
    ]);

    Order::factory()->create([
        'status' => OrderStatus::AwaitingConfirmation,
        'customer_name' => 'Kojo Asante',
        'total' => '150.00',
    ]);

    LoyaltyWallet::factory()->withBalance(40)->create([
        'phone_normalized' => PhoneNumber::normalize('0244111222'),
    ]);

    Livewire::test(Dashboard::class)
        ->assertOk()
        ->assertSee('Paid this week')
        ->assertSee('Awaiting confirmation')
        ->assertSee('Pending payment')
        ->assertSee('Loyalty points open')
        ->assertSee('Paid sales')
        ->assertSee('Latest orders')
        ->assertSee('Ama Mensah')
        ->assertDontSee('Documentation')
        ->assertDontSee('Welcome,');
});

test('shop stats overview reflects current order and wallet totals', function () {
    Order::factory()->paid()->create([
        'total' => '200.00',
        'paid_at' => now(),
    ]);

    Order::factory()->create([
        'status' => OrderStatus::AwaitingConfirmation,
    ]);

    LoyaltyWallet::factory()->withBalance(25)->create();

    Livewire::test(ShopStatsOverview::class)
        ->assertOk()
        ->assertSee('Awaiting confirmation')
        ->assertSee('1')
        ->assertSee('25');
});

test('orders chart and latest orders widgets render', function () {
    Order::factory()->paid()->create([
        'customer_name' => 'Ama Mensah',
        'paid_at' => now(),
    ]);

    Livewire::test(OrdersChart::class)->assertOk();

    Livewire::test(LatestOrders::class)
        ->assertOk()
        ->assertCanSeeTableRecords(Order::all());
});
