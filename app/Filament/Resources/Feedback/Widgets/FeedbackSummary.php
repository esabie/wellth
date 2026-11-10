<?php

namespace App\Filament\Resources\Feedback\Widgets;

use App\Models\Feedback;
use App\Support\FeedbackFormOptions;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class FeedbackSummary extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected ?string $pollingInterval = '60s';

    /**
     * @return array<string, int>
     */
    protected function getColumns(): array
    {
        return [
            'default' => 1,
            'md' => 3,
        ];
    }

    protected function getStats(): array
    {
        $total = Feedback::query()->count();
        $average = $total > 0
            ? round((float) Feedback::query()->avg('shopping_rating'), 1)
            : null;

        $topFavourites = Feedback::query()
            ->selectRaw('product_id, count(*) as picks')
            ->groupBy('product_id')
            ->orderByDesc('picks')
            ->with('product')
            ->limit(3)
            ->get();

        $topFavourite = $topFavourites->first();
        $favouriteLabel = $topFavourite?->product?->name ?? 'No favourites yet';
        $favouriteDescription = $topFavourites->isEmpty()
            ? 'Waiting on the first response'
            : $topFavourites
                ->map(fn (Feedback $row): string => ($row->product?->name ?? 'Unknown').' · '.$row->picks)
                ->implode(' · ');

        $chipCounts = Feedback::query()
            ->whereNotNull('improvement_tags')
            ->pluck('improvement_tags')
            ->flatten()
            ->filter(fn (mixed $tag): bool => is_string($tag) && $tag !== '')
            ->countBy()
            ->sortDesc();

        $topChips = $chipCounts->take(3);
        $topChipKey = $topChips->keys()->first();
        $chipOptions = FeedbackFormOptions::improvementTags();
        $chipLabel = $topChipKey
            ? ($chipOptions[$topChipKey] ?? $topChipKey)
            : 'No chips yet';
        $chipDescription = $topChips->isEmpty()
            ? 'Waiting on the first response'
            : $topChips
                ->map(fn (int $count, string $tag): string => ($chipOptions[$tag] ?? $tag).' · '.$count)
                ->implode(' · ');

        return [
            Stat::make(
                'Average shopping / website score',
                $average === null ? '—' : Number::format($average, maxPrecision: 1).' / 5',
            )
                ->description($total === 0 ? 'No responses yet' : Number::format($total).' '.str('response')->plural($total))
                ->descriptionIcon('heroicon-m-star')
                ->color($average === null ? 'gray' : ($average >= 4 ? 'success' : 'warning')),
            Stat::make('Top favourite blend', $favouriteLabel)
                ->description($favouriteDescription)
                ->descriptionIcon('heroicon-m-heart')
                ->color($topFavourite ? 'success' : 'gray'),
            Stat::make('Top improvement chip', $chipLabel)
                ->description($chipDescription)
                ->descriptionIcon('heroicon-m-hand-thumb-up')
                ->color($topChipKey ? 'primary' : 'gray'),
        ];
    }
}
