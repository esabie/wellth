<?php

namespace App\Filament\Pages;

use App\Support\ShopSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class ManageLoyaltySettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static string|UnitEnum|null $navigationGroup = 'Sales';

    protected static ?string $navigationLabel = 'Loyalty';

    protected static ?string $title = 'Loyalty';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament-panels::pages.page';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(app(ShopSettings::class)->loyalty());
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->operation('edit')
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->columns([
                        'default' => 1,
                        'lg' => 2,
                    ])
                    ->schema([
                        Section::make('Second purchase offer')
                            ->description('Exactly the second paid order for a phone number gets this discount. Pending checkouts do not count.')
                            ->icon(Heroicon::OutlinedReceiptPercent)
                            ->schema([
                                Toggle::make(ShopSettings::SECOND_PURCHASE_ENABLED)
                                    ->label('Enable second-purchase discount')
                                    ->helperText('When enabled, customers with exactly one paid order get a percent off their next checkout.')
                                    ->inline(false)
                                    ->live()
                                    ->columnSpanFull(),
                                TextInput::make(ShopSettings::SECOND_PURCHASE_PERCENT)
                                    ->label('Discount percent')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%')
                                    ->required()
                                    ->dehydratedWhenHidden()
                                    ->visible(fn (Get $get): bool => (bool) $get(ShopSettings::SECOND_PURCHASE_ENABLED)),
                                TextInput::make(ShopSettings::SECOND_PURCHASE_LABEL)
                                    ->label('Checkout label')
                                    ->maxLength(120)
                                    ->required()
                                    ->helperText('Shown on the checkout summary when the offer applies.')
                                    ->dehydratedWhenHidden()
                                    ->visible(fn (Get $get): bool => (bool) $get(ShopSettings::SECOND_PURCHASE_ENABLED)),
                            ])
                            ->columns(2)
                            ->columnSpan(1),
                        Section::make('Loyalty points')
                            ->description('Customers earn a flat points amount on every paid order. Redeeming points at checkout blocks the second-purchase discount and promo codes.')
                            ->icon(Heroicon::OutlinedGift)
                            ->schema([
                                Toggle::make(ShopSettings::POINTS_ENABLED)
                                    ->label('Enable loyalty points')
                                    ->helperText('When enabled, paid orders credit points to the customer phone wallet.')
                                    ->inline(false)
                                    ->live()
                                    ->columnSpanFull(),
                                TextInput::make(ShopSettings::POINTS_PER_PAID_ORDER)
                                    ->label('Points per paid order')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(0)
                                    ->required()
                                    ->dehydratedWhenHidden()
                                    ->visible(fn (Get $get): bool => (bool) $get(ShopSettings::POINTS_ENABLED)),
                                TextInput::make(ShopSettings::POINT_VALUE_GHS)
                                    ->label('Value per point')
                                    ->numeric()
                                    ->minValue(0.01)
                                    ->step(0.01)
                                    ->prefix('GHS')
                                    ->required()
                                    ->helperText('Used when redeeming points at checkout.')
                                    ->dehydratedWhenHidden()
                                    ->visible(fn (Get $get): bool => (bool) $get(ShopSettings::POINTS_ENABLED)),
                            ])
                            ->columns(2)
                            ->columnSpan(1),
                    ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        app(ShopSettings::class)->putLoyalty($data);

        Notification::make()
            ->title('Loyalty settings saved')
            ->success()
            ->send();
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make($this->getFormActions()),
                    ]),
            ]);
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save changes')
                ->submit('save')
                ->keyBindings(['mod+s']),
        ];
    }
}
