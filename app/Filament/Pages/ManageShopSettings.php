<?php

namespace App\Filament\Pages;

use App\Support\ShopSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class ManageShopSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Sales';

    protected static ?string $navigationLabel = 'Shop settings';

    protected static ?string $title = 'Shop settings';

    protected ?string $subheading = 'Checkout rules, referral rewards, and how Wellth alerts your team.';

    protected static ?int $navigationSort = 5;

    protected Width|string|null $maxContentWidth = Width::FiveExtraLarge;

    protected string $view = 'filament-panels::pages.page';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(app(ShopSettings::class)->checkout());
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
                Callout::make('Changes apply immediately')
                    ->description('New carts and checkouts pick up these values right away. Keep a queue worker running so email and SMS alerts actually send.')
                    ->icon(Heroicon::OutlinedInformationCircle)
                    ->color('gray')
                    ->columnSpanFull(),
                Grid::make()
                    ->columns([
                        'default' => 1,
                        'lg' => 2,
                    ])
                    ->schema([
                        Section::make('Checkout rules')
                            ->description('Control what guests need before they can place an order on the shop.')
                            ->icon(Heroicon::OutlinedShoppingBag)
                            ->iconColor('primary')
                            ->schema([
                                TextInput::make(ShopSettings::MINIMUM_ORDER_QUANTITY)
                                    ->label('Minimum order quantity')
                                    ->helperText('Guests need at least this many bottles in the cart. Formulas can be mixed — the total quantity is what counts.')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(1)
                                    ->required()
                                    ->prefixIcon(Heroicon::OutlinedCube)
                                    ->suffix('bottles')
                                    ->placeholder('10'),
                            ])
                            ->columnSpan(1),
                        Section::make('Referral rewards')
                            ->description('Points credited when a paid order uses a referral promo code.')
                            ->icon(Heroicon::OutlinedUserPlus)
                            ->iconColor('success')
                            ->schema([
                                TextInput::make(ShopSettings::REFERRAL_POINTS_PER_ORDER)
                                    ->label('Points per paid referral')
                                    ->helperText('Awarded to the referrer’s wallet and confirmed by SMS. Set to 0 to pause rewards.')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(0)
                                    ->required()
                                    ->prefixIcon(Heroicon::OutlinedSparkles)
                                    ->suffix('points')
                                    ->placeholder('10'),
                            ])
                            ->columnSpan(1),
                    ]),
                Section::make('Admin alerts')
                    ->description('Get notified when a customer places an order or uploads proof of payment. Customer confirmation SMS still uses Mnotify separately.')
                    ->icon(Heroicon::OutlinedBellAlert)
                    ->iconColor('warning')
                    ->schema([
                        Callout::make(fn (Get $get): string => $this->alertStatusHeading($get))
                            ->description(fn (Get $get): string => $this->alertStatusDescription($get))
                            ->icon(fn (Get $get): Heroicon => $this->alertStatusIcon($get))
                            ->color(fn (Get $get): string => $this->alertStatusColor($get))
                            ->columnSpanFull(),
                        TextInput::make(ShopSettings::ADMIN_NOTIFICATION_EMAIL)
                            ->label('Alert email')
                            ->email()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->prefixIcon(Heroicon::OutlinedEnvelope)
                            ->placeholder('ops@wellth.com')
                            ->helperText('Leave blank to skip admin emails.'),
                        TextInput::make(ShopSettings::ADMIN_NOTIFICATION_PHONE)
                            ->label('Alert phone')
                            ->tel()
                            ->maxLength(40)
                            ->live(onBlur: true)
                            ->prefixIcon(Heroicon::OutlinedDevicePhoneMobile)
                            ->placeholder('024 123 4567')
                            ->helperText('Requires an approved Mnotify sender ID. Leave blank to skip admin SMS.'),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        app(ShopSettings::class)->putCheckout($data);

        Notification::make()
            ->title('Shop settings saved')
            ->body('Checkout rules and alert destinations are up to date.')
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
                        Actions::make($this->getFormActions())
                            ->alignment(Alignment::Start)
                            ->sticky(),
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
                ->label('Save settings')
                ->icon(Heroicon::OutlinedCheck)
                ->color('primary')
                ->submit('save')
                ->keyBindings(['mod+s']),
        ];
    }

    private function alertStatusHeading(Get $get): string
    {
        $hasEmail = filled($get(ShopSettings::ADMIN_NOTIFICATION_EMAIL));
        $hasPhone = filled($get(ShopSettings::ADMIN_NOTIFICATION_PHONE));

        return match (true) {
            $hasEmail && $hasPhone => 'Email and SMS alerts are ready',
            $hasEmail => 'Email alerts only',
            $hasPhone => 'SMS alerts only',
            default => 'No admin alerts configured',
        };
    }

    private function alertStatusDescription(Get $get): string
    {
        $hasEmail = filled($get(ShopSettings::ADMIN_NOTIFICATION_EMAIL));
        $hasPhone = filled($get(ShopSettings::ADMIN_NOTIFICATION_PHONE));

        return match (true) {
            $hasEmail && $hasPhone => 'Your team will get both channels when orders are placed or payment proof is uploaded.',
            $hasEmail => 'Add an alert phone to also receive MoMo and order updates by SMS.',
            $hasPhone => 'Add an alert email to receive the same updates in your inbox.',
            default => 'Add an email and/or phone below so new orders and payment proofs do not get missed.',
        };
    }

    private function alertStatusIcon(Get $get): Heroicon
    {
        $hasEmail = filled($get(ShopSettings::ADMIN_NOTIFICATION_EMAIL));
        $hasPhone = filled($get(ShopSettings::ADMIN_NOTIFICATION_PHONE));

        return ($hasEmail || $hasPhone)
            ? Heroicon::OutlinedCheckCircle
            : Heroicon::OutlinedExclamationTriangle;
    }

    private function alertStatusColor(Get $get): string
    {
        $hasEmail = filled($get(ShopSettings::ADMIN_NOTIFICATION_EMAIL));
        $hasPhone = filled($get(ShopSettings::ADMIN_NOTIFICATION_PHONE));

        return match (true) {
            $hasEmail && $hasPhone => 'success',
            $hasEmail || $hasPhone => 'warning',
            default => 'danger',
        };
    }
}
