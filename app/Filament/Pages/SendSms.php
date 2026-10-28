<?php

namespace App\Filament\Pages;

use App\Actions\SendSmsCampaign;
use App\Enums\SmsCampaignAudience;
use App\Enums\SmsCampaignStatus;
use App\Models\SmsCampaign;
use App\Models\User;
use App\Support\SmsAudiences;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class SendSms extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Sales';

    protected static ?string $navigationLabel = 'Send SMS';

    protected static ?string $title = 'Send SMS';

    protected static ?int $navigationSort = 6;

    protected Width|string|null $maxContentWidth = Width::FiveExtraLarge;

    protected string $view = 'filament-panels::pages.page';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->mountInteractsWithTable();

        $this->form->fill([
            'audience' => SmsCampaignAudience::AllCustomers->value,
            'custom_phones' => '',
            'message' => '',
        ]);
    }

    public function getSubheading(): string|Htmlable|null
    {
        $sender = (string) config('services.mnotify.sender_id');

        if (! filled($sender)) {
            return 'Configure MNOTIFY_SENDER_ID before sending messages.';
        }

        return "Messages are sent with the sender ID “{$sender}”.";
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->operation('create')
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Compose message')
                    ->description('Bundle a message to all customers or a custom list of phone numbers.')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->iconColor('primary')
                    ->schema([
                        Select::make('audience')
                            ->label('Recipient')
                            ->options(collect(SmsCampaignAudience::composeOptions())
                                ->mapWithKeys(fn (SmsCampaignAudience $audience): array => [
                                    $audience->value => $audience->getLabel(),
                                ])
                                ->all())
                            ->required()
                            ->native(false)
                            ->live()
                            ->helperText(function (Get $get): string {
                                $value = $get('audience');

                                if (blank($value)) {
                                    return 'Choose who should receive this message.';
                                }

                                $audience = $value instanceof SmsCampaignAudience
                                    ? $value
                                    : SmsCampaignAudience::from((string) $value);

                                return app(SmsAudiences::class)->helperText($audience);
                            }),
                        Textarea::make('custom_phones')
                            ->label('Phone numbers')
                            ->placeholder("0241234567\n0209876543")
                            ->rows(4)
                            ->helperText('One number per line, or separated by commas. Ghana numbers preferred (024… or 233…).')
                            ->visible(function (Get $get): bool {
                                $value = $get('audience');

                                if ($value instanceof SmsCampaignAudience) {
                                    return $value === SmsCampaignAudience::CustomPhones;
                                }

                                return $value === SmsCampaignAudience::CustomPhones->value;
                            })
                            ->required(function (Get $get): bool {
                                $value = $get('audience');

                                if ($value instanceof SmsCampaignAudience) {
                                    return $value === SmsCampaignAudience::CustomPhones;
                                }

                                return $value === SmsCampaignAudience::CustomPhones->value;
                            }),
                        Textarea::make('message')
                            ->label('Message')
                            ->rows(6)
                            ->required()
                            ->maxLength(480)
                            ->live()
                            ->helperText(function (Get $get): string {
                                $length = mb_strlen((string) ($get('message') ?? ''));
                                $segments = max(1, (int) ceil($length / 160));

                                return "{$length} / 480 characters · about {$segments} SMS segment".($segments === 1 ? '' : 's').' per recipient.';
                            }),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(SmsCampaign::query()->with('user')->latest())
            ->heading('Recent campaigns')
            ->description('Latest outbound messages sent from the admin panel.')
            ->columns([
                TextColumn::make('sent_at')
                    ->label('When')
                    ->dateTime()
                    ->placeholder(fn (SmsCampaign $record): string => $record->created_at?->toDayDateTimeString() ?? '-')
                    ->sortable(),
                TextColumn::make('audience')
                    ->badge()
                    ->sortable(),
                TextColumn::make('message')
                    ->limit(60)
                    ->wrap()
                    ->searchable(),
                TextColumn::make('recipients_count')
                    ->label('Recipients')
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('sent_count')
                    ->label('Sent')
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('failed_count')
                    ->label('Failed')
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('By')
                    ->placeholder('—'),
            ])
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('No campaigns yet')
            ->emptyStateDescription('Send your first SMS above and it will show up here.')
            ->emptyStateIcon(Heroicon::OutlinedChatBubbleLeftRight);
    }

    public function send(SendSmsCampaign $sendSmsCampaign): void
    {
        $data = $this->form->getState();
        $user = auth()->user();

        $audience = $data['audience'] instanceof SmsCampaignAudience
            ? $data['audience']
            : SmsCampaignAudience::from((string) $data['audience']);

        $campaign = $sendSmsCampaign->handle(
            $audience,
            (string) $data['message'],
            $user instanceof User ? $user : null,
            isset($data['custom_phones']) ? (string) $data['custom_phones'] : null,
        );

        $this->form->fill([
            'audience' => $audience->value,
            'custom_phones' => $audience === SmsCampaignAudience::CustomPhones
                ? (string) ($data['custom_phones'] ?? '')
                : '',
            'message' => '',
        ]);

        $this->resetTable();

        $notification = Notification::make()
            ->title(match ($campaign->status) {
                SmsCampaignStatus::Completed => 'SMS campaign sent',
                SmsCampaignStatus::Partial => 'SMS campaign partially sent',
                default => 'SMS campaign failed',
            })
            ->body("{$campaign->sent_count} sent, {$campaign->failed_count} failed out of {$campaign->recipients_count} recipients.");

        match ($campaign->status) {
            SmsCampaignStatus::Completed => $notification->success(),
            SmsCampaignStatus::Partial => $notification->warning(),
            default => $notification->danger(),
        };

        $notification->send();
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->footer([
                        Actions::make($this->getFormActions())
                            ->alignment(Alignment::Start),
                    ]),
                EmbeddedTable::make(),
            ]);
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('send')
                ->label('Send SMS')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Send this SMS?')
                ->modalDescription(function (): string {
                    $audienceValue = $this->data['audience'] ?? null;
                    $message = trim((string) ($this->data['message'] ?? ''));

                    if (blank($audienceValue) || $message === '') {
                        return 'Choose recipients and write a message before sending.';
                    }

                    $audience = $audienceValue instanceof SmsCampaignAudience
                        ? $audienceValue
                        : SmsCampaignAudience::from((string) $audienceValue);

                    $customPhones = $audience === SmsCampaignAudience::CustomPhones
                        ? app(SmsAudiences::class)->parseCustomPhones((string) ($this->data['custom_phones'] ?? ''))
                        : null;

                    $count = app(SmsAudiences::class)->count($audience, $customPhones);

                    return "Send to {$count} {$audience->getLabel()} recipient".($count === 1 ? '' : 's').'?';
                })
                ->action(fn () => $this->send(app(SendSmsCampaign::class))),
        ];
    }
}
