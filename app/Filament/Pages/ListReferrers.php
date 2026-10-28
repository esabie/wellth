<?php

namespace App\Filament\Pages;

use App\Filament\Resources\PromoCodes\PromoCodeResource;
use App\Support\ShopPeople;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ListReferrers extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserPlus;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationLabel = 'Referrers';

    protected static ?string $title = 'Referrers';

    protected static ?string $slug = 'users/referrers';

    protected ?string $subheading = 'People attached as referrers on promo codes.';

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

    public function table(Table $table): Table
    {
        return $table
            ->query(app(ShopPeople::class)->referrersQuery())
            ->columns([
                TextColumn::make('referrer_name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('referrer_phone')
                    ->label('Phone')
                    ->searchable()
                    ->copyable()
                    ->placeholder('—'),
                TextColumn::make('promo_codes_count')
                    ->label('Promo codes')
                    ->numeric()
                    ->alignEnd()
                    ->sortable(),
            ])
            ->defaultSort('referrer_name')
            ->recordUrl(fn ($record): string => PromoCodeResource::getUrl('index', [
                'tableSearch' => $record->referrer_phone,
            ]))
            ->paginated([10, 25, 50])
            ->emptyStateHeading('No referrers yet')
            ->emptyStateDescription('Add a referrer phone when creating a referral promo code.')
            ->emptyStateIcon(Heroicon::OutlinedUserPlus);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedTable::make(),
            ]);
    }
}
