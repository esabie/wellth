<?php

namespace App\Filament\Pages;

use App\Filament\Resources\LoyaltyWallets\LoyaltyWalletResource;
use App\Filament\Resources\Users\UserResource;
use App\Support\ShopPeople;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class Users extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Users';

    protected static ?string $title = 'Users';

    protected static ?string $slug = 'users';

    protected static ?int $navigationSort = 90;

    protected ?string $subheading = 'Choose a group to view or manage people on Wellth.';

    protected Width|string|null $maxContentWidth = Width::FiveExtraLarge;

    protected string $view = 'filament-panels::pages.page';

    /**
     * @return list<array{title: string, description: string, count: int, icon: Heroicon, url: string}>
     */
    public function getSegments(): array
    {
        $people = app(ShopPeople::class);

        return [
            [
                'title' => 'Customers',
                'description' => 'Shoppers who have placed an order',
                'count' => $people->customerCount(),
                'icon' => Heroicon::OutlinedShoppingBag,
                'url' => ListCustomers::getUrl(),
            ],
            [
                'title' => 'Loyalty wallets',
                'description' => 'Phone-based loyalty balances',
                'count' => $people->loyaltyWalletCount(),
                'icon' => Heroicon::OutlinedWallet,
                'url' => LoyaltyWalletResource::getUrl('index'),
            ],
            [
                'title' => 'Referrers',
                'description' => 'People with referral promo codes',
                'count' => $people->referrerCount(),
                'icon' => Heroicon::OutlinedUserPlus,
                'url' => ListReferrers::getUrl(),
            ],
            [
                'title' => 'Admin users',
                'description' => 'Staff who can access this portal',
                'count' => $people->adminCount(),
                'icon' => Heroicon::OutlinedShieldCheck,
                'url' => UserResource::getUrl('index'),
            ],
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 3,
                        '2xl' => 4,
                    ])
                    ->schema(
                        collect($this->getSegments())
                            ->map(fn (array $segment): Section => Section::make($segment['title'])
                                ->description($segment['description'])
                                ->icon($segment['icon'])
                                ->iconColor('primary')
                                ->schema([
                                    Text::make(number_format($segment['count']))
                                        ->size(TextSize::Large)
                                        ->weight(FontWeight::SemiBold)
                                        ->color(null),
                                ])
                                ->footerActions([
                                    Action::make('open'.$segment['title'])
                                        ->label('Open list →')
                                        ->url($segment['url'])
                                        ->color('primary')
                                        ->link(),
                                ]))
                            ->all(),
                    ),
            ]);
    }
}
