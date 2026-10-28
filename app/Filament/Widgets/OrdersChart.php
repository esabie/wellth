<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class OrdersChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Paid sales';

    protected ?string $description = 'Last 7 days';

    protected string $color = 'warning';

    protected ?string $maxHeight = '280px';

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '60s';

    protected function getData(): array
    {
        $days = collect(range(6, 0))->map(
            fn (int $daysAgo): Carbon => now()->subDays($daysAgo)->startOfDay(),
        );

        $totals = $days->map(function (Carbon $day): float {
            return (float) Order::query()
                ->whereIn('status', [
                    OrderStatus::Paid,
                    OrderStatus::Preparing,
                    OrderStatus::Shipped,
                ])
                ->whereDate('paid_at', $day)
                ->sum('total');
        });

        return [
            'datasets' => [
                [
                    'label' => 'Revenue ('.config('shop.currency', 'GHS').')',
                    'data' => $totals->all(),
                    'fill' => 'start',
                ],
            ],
            'labels' => $days->map(fn (Carbon $day): string => $day->format('D j'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
