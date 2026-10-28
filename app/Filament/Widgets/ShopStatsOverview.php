<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Filament\Resources\LoyaltyWallets\LoyaltyWalletResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\LoyaltyWallet;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class ShopStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $currency = config('shop.currency', 'GHS');
        $paidStatuses = [
            OrderStatus::Paid,
            OrderStatus::Preparing,
            OrderStatus::Shipped,
        ];

        $weekRevenue = (float) Order::query()
            ->whereIn('status', $paidStatuses)
            ->where('paid_at', '>=', now()->startOfWeek())
            ->sum('total');

        $awaitingConfirmation = Order::query()
            ->where('status', OrderStatus::AwaitingConfirmation)
            ->count();

        $pendingPayment = Order::query()
            ->where('status', OrderStatus::Pending)
            ->count();

        $loyaltyPoints = (int) LoyaltyWallet::query()->sum('balance');

        return [
            Stat::make('Paid this week', Number::currency($weekRevenue, $currency))
                ->description('Since Monday')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->url(OrderResource::getUrl('index')),
            Stat::make('Awaiting confirmation', (string) $awaitingConfirmation)
                ->description('Payment proof ready to review')
                ->descriptionIcon('heroicon-m-clock')
                ->color($awaitingConfirmation > 0 ? 'warning' : 'gray')
                ->url(OrderResource::getUrl('index', [
                    'tableFilters' => [
                        'status' => [
                            'value' => OrderStatus::AwaitingConfirmation->value,
                        ],
                    ],
                ])),
            Stat::make('Pending payment', (string) $pendingPayment)
                ->description('Waiting for customer proof')
                ->descriptionIcon('heroicon-m-queue-list')
                ->color($pendingPayment > 0 ? 'info' : 'gray')
                ->url(OrderResource::getUrl('index', [
                    'tableFilters' => [
                        'status' => [
                            'value' => OrderStatus::Pending->value,
                        ],
                    ],
                ])),
            Stat::make('Loyalty points open', Number::format($loyaltyPoints))
                ->description('Across all wallets')
                ->descriptionIcon('heroicon-m-gift')
                ->color('primary')
                ->url(LoyaltyWalletResource::getUrl('index')),
        ];
    }
}
