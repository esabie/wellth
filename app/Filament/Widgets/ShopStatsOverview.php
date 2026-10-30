<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Filament\Resources\LoyaltyWallets\LoyaltyWalletResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\LoyaltyWallet;
use App\Models\Order;
use App\Models\OrderItem;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Number;

class ShopStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected ?string $pollingInterval = '60s';

    public string $period = 'week';

    public function setPeriod(string $period): void
    {
        if (! in_array($period, ['today', 'week', 'month'], true)) {
            return;
        }

        $this->period = $period;
        $this->cachedStats = null;
    }

    public function getSectionContentComponent(): Component
    {
        return Section::make()
            ->heading('Sales')
            ->afterHeader($this->periodButtons())
            ->schema($this->getCachedStats())
            ->columns($this->getColumns())
            ->contained(false)
            ->gridContainer();
    }

    /**
     * One card per row on a phone, two once there is room, four on a wide screen.
     *
     * @return array<string, int>
     */
    protected function getColumns(): array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 4,
        ];
    }

    protected function getStats(): array
    {
        $currency = config('shop.currency', 'GHS');

        $paidRevenue = (float) $this->paidOrders()->sum('total');

        $awaitingConfirmation = Order::query()
            ->where('status', OrderStatus::AwaitingConfirmation)
            ->count();

        $productsPurchased = (int) OrderItem::query()
            ->whereIn('order_id', $this->paidOrders()->select('orders.id'))
            ->sum('quantity');

        $loyaltyPoints = (int) LoyaltyWallet::query()->sum('balance');

        return [
            Stat::make($this->paidLabel(), Number::currency($paidRevenue, $currency))
                ->description($this->paidDescription())
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
            Stat::make('Products purchased', Number::format($productsPurchased))
                ->description('On those paid orders')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('success')
                ->url(OrderResource::getUrl('index')),
            Stat::make('Loyalty points open', Number::format($loyaltyPoints))
                ->description('Across all wallets')
                ->descriptionIcon('heroicon-m-gift')
                ->color('primary')
                ->url(LoyaltyWalletResource::getUrl('index')),
        ];
    }

    /**
     * Paid, preparing, and shipped orders in the selected period.
     *
     * @return Builder<Order>
     */
    private function paidOrders(): Builder
    {
        return Order::query()
            ->whereIn('status', [
                OrderStatus::Paid,
                OrderStatus::Preparing,
                OrderStatus::Shipped,
            ])
            ->where('paid_at', '>=', $this->periodStart());
    }

    private function periodStart(): Carbon
    {
        return match ($this->period) {
            'today' => now()->startOfDay(),
            'month' => now()->startOfMonth(),
            default => now()->startOfWeek(),
        };
    }

    private function paidLabel(): string
    {
        return match ($this->period) {
            'today' => 'Paid today',
            'month' => 'Paid this month',
            default => 'Paid this week',
        };
    }

    private function paidDescription(): string
    {
        return match ($this->period) {
            'today' => 'Since midnight',
            'month' => 'Since the 1st',
            default => 'Since Monday',
        };
    }

    private function periodButtons(): HtmlString
    {
        $buttons = collect([
            'today' => 'Today',
            'week' => 'This week',
            'month' => 'This month',
        ])->map(function (string $label, string $period): string {
            $active = $this->period === $period;
            $class = $active
                ? 'fi-btn fi-size-sm fi-color fi-color-primary fi-bg-color-400 dark:fi-bg-color-600 fi-text-color-900 dark:fi-text-color-950'
                : 'fi-btn fi-outlined fi-size-sm';

            return '<button type="button" wire:click="setPeriod(\''.e($period).'\')" class="'.$class.'" aria-pressed="'.($active ? 'true' : 'false').'" style="white-space:nowrap;">'.e($label).'</button>';
        })->implode('');

        return new HtmlString(<<<'HTML'
<style>
    .fi-wi-stats-overview .fi-section-header-content { width: 100%; }
    .fi-wi-stats-overview .fi-section-header-text-ctn { margin-right: auto; }
    .fi-wi-stats-overview .fi-section-header-after-ctn { margin-left: auto; }
    @media (max-width: 639px) {
        .sales-period { gap: 0.35rem; }
        .sales-period .fi-btn.fi-size-sm {
            padding: 0.15rem 0.45rem;
            font-size: 0.6875rem;
            line-height: 1.2;
            border-radius: 0.375rem;
        }
    }
</style>
HTML.'<div role="group" aria-label="Sales period" class="sales-period" style="display:flex; flex-wrap:wrap; justify-content:flex-end; gap:0.5rem;">'.$buttons.'</div>');
    }
}
