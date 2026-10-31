<?php

use App\Enums\OrderStatus;
use App\Filament\Widgets\LatestOrders;
use App\Filament\Widgets\OrdersChart;
use App\Filament\Widgets\ShopStatsOverview;
use App\Models\LoyaltyWallet;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Support\PackingReport;
use App\Support\PhoneNumber;
use Filament\Pages\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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
        ->assertSee('Today')
        ->assertSee('This week')
        ->assertSee('This month')
        ->assertSee('justify-content:flex-end', false)
        ->assertSee('max-width: 639px', false)
        ->assertSee('Paid this week')
        ->assertSee('Awaiting confirmation')
        ->assertSee('Products purchased')
        ->assertDontSee('Pending payment')
        ->assertSee('Loyalty points open')
        ->assertSee('Paid sales')
        ->assertSee('Latest orders')
        ->assertSee('Ama Mensah')
        ->assertDontSee('Documentation')
        ->assertDontSee('Welcome,');
});

test('shop stats overview counts products from paid orders only', function () {
    $this->travelTo(Carbon::parse('2026-10-14 15:00:00'));

    $paid = Order::factory()->paid()->create([
        'total' => '200.00',
        'paid_at' => now(),
    ]);
    OrderItem::factory()->create([
        'order_id' => $paid->id,
        'quantity' => 10,
    ]);

    $shipped = Order::factory()->shipped()->create();
    OrderItem::factory()->create([
        'order_id' => $shipped->id,
        'quantity' => 2,
    ]);

    $pending = Order::factory()->create([
        'status' => OrderStatus::Pending,
    ]);
    OrderItem::factory()->create([
        'order_id' => $pending->id,
        'quantity' => 7,
    ]);

    Order::factory()->create([
        'status' => OrderStatus::AwaitingConfirmation,
    ]);

    LoyaltyWallet::factory()->withBalance(25)->create();

    Livewire::test(ShopStatsOverview::class)
        ->assertOk()
        ->assertSee('Products purchased')
        ->assertSee('12')
        ->assertSee('Awaiting confirmation')
        ->assertSee('25')
        ->assertDontSee('Pending payment');
});

test('paid revenue and products purchased follow the same period', function () {
    $this->travelTo(Carbon::parse('2026-10-14 15:00:00'));

    $today = Order::factory()->paid()->create([
        'total' => '100.00',
        'paid_at' => now(),
    ]);
    OrderItem::factory()->create([
        'order_id' => $today->id,
        'quantity' => 11,
    ]);

    $earlierThisWeek = Order::factory()->paid()->create([
        'total' => '50.00',
        'paid_at' => now()->startOfWeek()->addDay(),
    ]);
    OrderItem::factory()->create([
        'order_id' => $earlierThisWeek->id,
        'quantity' => 22,
    ]);

    $earlierThisMonth = Order::factory()->paid()->create([
        'total' => '80.00',
        'paid_at' => now()->startOfMonth(),
    ]);
    OrderItem::factory()->create([
        'order_id' => $earlierThisMonth->id,
        'quantity' => 33,
    ]);

    $lastMonth = Order::factory()->paid()->create([
        'total' => '90.00',
        'paid_at' => now()->subMonth(),
    ]);
    OrderItem::factory()->create([
        'order_id' => $lastMonth->id,
        'quantity' => 44,
    ]);

    Livewire::test(ShopStatsOverview::class)
        ->assertSee('Paid this week')
        ->assertSee('150.00')
        ->assertSee('33')
        ->call('setPeriod', 'today')
        ->assertSee('Paid today')
        ->assertSee('100.00')
        ->assertSee('11')
        ->assertDontSee('Paid this week')
        ->call('setPeriod', 'month')
        ->assertSee('Paid this month')
        ->assertSee('230.00')
        ->assertSee('66');
});

test('the packing list download breaks paid orders down by formula', function () {
    $this->travelTo(Carbon::parse('2026-10-14 15:00:00'));

    $paid = Order::factory()->paid()->create([
        'uuid' => 'WLPACK001',
        'customer_name' => 'Ama Mensah',
        'customer_phone' => '0244111222',
        'shipping_city' => 'Accra',
        'paid_at' => now(),
    ]);
    OrderItem::factory()->create([
        'order_id' => $paid->id,
        'product_name' => 'Ginja',
        'product_slug' => 'ginja',
        'quantity' => 4,
    ]);
    OrderItem::factory()->create([
        'order_id' => $paid->id,
        'product_name' => 'Wellth Bouquet',
        'product_slug' => PackingReport::BOUQUET_SLUG,
        'quantity' => 1,
    ]);

    $pending = Order::factory()->create([
        'status' => OrderStatus::Pending,
        'customer_name' => 'Kojo Asante',
    ]);
    OrderItem::factory()->create([
        'order_id' => $pending->id,
        'product_name' => 'Ruby Rush',
        'product_slug' => 'ruby-rush',
        'quantity' => 9,
    ]);

    $earlierThisMonth = Order::factory()->paid()->create([
        'uuid' => 'WLPACK002',
        'customer_name' => 'Efua Boateng',
        'paid_at' => now()->startOfMonth(),
    ]);
    OrderItem::factory()->create([
        'order_id' => $earlierThisMonth->id,
        'product_name' => 'Belly Reset',
        'product_slug' => 'belly-reset',
        'quantity' => 8,
    ]);

    $component = Livewire::test(ShopStatsOverview::class)
        ->assertSee('Packing list')
        ->assertDontSee('By formula');

    $response = $component->instance()->downloadPackingReport(app(PackingReport::class));

    ob_start();
    $response->sendContent();
    $csv = ob_get_clean();

    expect($response->headers->get('content-disposition'))
        ->toContain('wellth-packing-week-2026-10-14.csv')
        ->and($csv)->toContain('WLPACK001')
        ->and($csv)->toContain('Ama Mensah')
        ->and($csv)->toContain('4 × Ginja')
        ->and($csv)->toContain('2 × Ginja, 2 × Glow Getter, 2 × Ruby Rush, 2 × Crave Control, 2 × Belly Reset')
        ->and($csv)->toContain('Ginja,6')
        ->and($csv)->not->toContain('Kojo Asante')
        ->and($csv)->not->toContain('WLPACK002');
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
