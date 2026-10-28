<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestOrders extends TableWidget
{
    protected static ?int $sort = 3;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Latest orders';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (): Builder => Order::query()->latest('created_at')->limit(8),
            )
            ->columns([
                TextColumn::make('uuid')
                    ->label('Order')
                    ->copyable()
                    ->limit(13),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable(false),
                TextColumn::make('total')
                    ->money(config('shop.currency', 'GHS')),
                TextColumn::make('created_at')
                    ->label('Placed')
                    ->since()
                    ->dateTimeTooltip(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (Order $record): string => OrderResource::getUrl('view', ['record' => $record])),
            ])
            ->paginated(false);
    }
}
