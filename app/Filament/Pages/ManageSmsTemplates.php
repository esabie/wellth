<?php

namespace App\Filament\Pages;

use App\Enums\SmsTemplate;
use App\Support\SmsTemplates;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class ManageSmsTemplates extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleBottomCenterText;

    protected static string|UnitEnum|null $navigationGroup = 'Sales';

    protected static ?string $navigationLabel = 'SMS templates';

    protected static ?string $title = 'SMS templates';

    protected ?string $subheading = 'Edit the automatic texts Wellth sends for orders, payments, and referrals.';

    protected static ?int $navigationSort = 7;

    protected Width|string|null $maxContentWidth = Width::FiveExtraLarge;

    protected string $view = 'filament-panels::pages.page';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return false;
    }

    public function mount(): void
    {
        $this->form->fill(app(SmsTemplates::class)->all());
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
                Callout::make('Placeholders are replaced when the SMS sends')
                    ->description('Leave a field blank and save to restore that template’s default. Keep messages short — longer texts use more Mnotify segments.')
                    ->icon(Heroicon::OutlinedInformationCircle)
                    ->color('gray')
                    ->columnSpanFull(),
                ...collect(SmsTemplate::cases())
                    ->map(fn (SmsTemplate $template): Section => Section::make($template->getLabel())
                        ->description($template->getDescription())
                        ->schema([
                            Textarea::make($template->value)
                                ->hiddenLabel()
                                ->rows(3)
                                ->maxLength(480)
                                ->helperText($template->placeholderHelp().' Leave empty and save to restore the default.'),
                        ]))
                    ->all(),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        app(SmsTemplates::class)->put($data);

        Notification::make()
            ->title('SMS templates saved')
            ->body('New automatic messages will use these texts right away.')
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
                ->label('Save templates')
                ->icon(Heroicon::OutlinedCheck)
                ->color('primary')
                ->submit('save')
                ->keyBindings(['mod+s']),
        ];
    }
}
