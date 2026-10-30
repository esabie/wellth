<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Support\ShopPeople;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListCustomers extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationLabel = 'Customers';

    protected static ?string $title = 'Customers';

    protected static ?string $slug = 'users/customers';

    protected ?string $subheading = 'Everyone who has ordered, and those who have successfully paid.';

    public string $customerList = 'customers';

    protected Width|string|null $maxContentWidth = Width::FiveExtraLarge;

    protected string $view = 'filament-panels::pages.page';

    public function mount(): void
    {
        $this->mountInteractsWithTable();
    }

    public function getBreadcrumbs(): array
    {
        return [
            Users::getUrl() => 'Users',
            ...(filled($this->getTitle()) ? ['' => $this->getTitle()] : []),
        ];
    }

    public function updatedCustomerList(): void
    {
        $this->resetPage();
        $this->flushCachedTableRecords();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->customerList === 'paid'
                ? app(ShopPeople::class)->paidCustomersQuery()
                : app(ShopPeople::class)->customersQuery())
            ->columns([
                TextColumn::make('customer_name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('customer_phone')
                    ->label('Phone')
                    ->searchable()
                    ->copyable()
                    ->placeholder('—'),
                TextColumn::make('customer_email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable()
                    ->visibleFrom('lg')
                    ->placeholder('—'),
                TextColumn::make('orders_count')
                    ->label('Orders')
                    ->numeric()
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('lifetime_total')
                    ->label('Lifetime total')
                    ->money(config('shop.currency', 'GHS'))
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('last_ordered_at')
                    ->label('Last order')
                    ->dateTime()
                    ->sortable()
                    ->visibleFrom('md'),
            ])
            ->defaultSort('last_ordered_at', 'desc')
            ->recordUrl(fn ($record): string => OrderResource::getUrl('index', [
                'tableSearch' => $record->customer_phone_normalized,
            ]))
            ->paginated([10, 25, 50])
            ->emptyStateHeading(fn (): string => $this->customerList === 'paid' ? 'No paid customers yet' : 'No customers yet')
            ->emptyStateDescription(fn (): string => $this->customerList === 'paid'
                ? 'Customers appear here after a payment is confirmed.'
                : 'Customers appear here after the first checkout order.')
            ->emptyStateIcon(Heroicon::OutlinedShoppingBag);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make()
                    ->livewireProperty('customerList')
                    ->contained(false)
                    ->tabPanels(false)
                    ->tabs([
                        'customers' => Tab::make('Customers')
                            ->icon(Heroicon::OutlinedShoppingBag)
                            ->badge(fn (): int => app(ShopPeople::class)->customerCount()),
                        'paid' => Tab::make('Paid customers')
                            ->icon(Heroicon::OutlinedBanknotes)
                            ->badge(fn (): int => app(ShopPeople::class)->paidCustomerCount()),
                    ]),
                EmbeddedTable::make(),
            ]);
    }
}
