<?php

use App\Enums\PromoCodeType;
use App\Enums\SmsTemplate;
use App\Filament\Resources\PromoCodes\Pages\CreatePromoCode;
use App\Filament\Resources\PromoCodes\Pages\EditPromoCode;
use App\Filament\Resources\PromoCodes\Pages\ListPromoCodes;
use App\Models\PromoCode;
use App\Models\User;
use App\Notifications\Channels\MnotifyChannel;
use App\Notifications\ReferralCodeCreatedSmsNotification;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('admins can list promo codes in filament', function () {
    $promo = PromoCode::factory()->create(['code' => 'WELCOME10']);

    Livewire::test(ListPromoCodes::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$promo]);
});

test('admins can create a promo code', function () {
    Livewire::test(CreatePromoCode::class)
        ->assertOk()
        ->fillForm([
            'code' => 'welcome10',
            'type' => PromoCodeType::Percent->value,
            'value' => 10,
            'label' => '10% off',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect(ListPromoCodes::getUrl());

    expect(PromoCode::query()->where('code', 'WELCOME10')->exists())->toBeTrue();
});

test('admins can create a referral promo with an optional referrer name', function () {
    Notification::fake();

    config(['services.mnotify.api_key' => 'test-key']);

    Livewire::test(CreatePromoCode::class)
        ->assertOk()
        ->fillForm([
            'code' => 'ama10',
            'type' => PromoCodeType::Percent->value,
            'value' => 10,
            'is_active' => true,
            'referrer_phone' => '0244111222',
            'referrer_name' => 'Ama Mensah',
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect(ListPromoCodes::getUrl());

    $promo = PromoCode::query()->where('code', 'AMA10')->first();

    expect($promo)->not->toBeNull()
        ->and($promo->referrer_name)->toBe('Ama Mensah')
        ->and($promo->referrer_phone_normalized)->not->toBeNull();

    Notification::assertSentOnDemand(
        ReferralCodeCreatedSmsNotification::class,
        function (ReferralCodeCreatedSmsNotification $notification, array $channels, object $notifiable) use ($promo): bool {
            $message = $notification->toMnotify($notifiable);

            return $notifiable->routes[MnotifyChannel::class] === PhoneNumber::forSms('0244111222')
                && $notification->promoCode->is($promo)
                && str_contains($message, 'AMA10')
                && str_contains($message, 'Hi Ama')
                && str_contains($message, '10% off');
        },
    );
});

test('creating a promo without a referrer does not send a referral code sms', function () {
    Notification::fake();

    config(['services.mnotify.api_key' => 'test-key']);

    Livewire::test(CreatePromoCode::class)
        ->fillForm([
            'code' => 'plain10',
            'type' => PromoCodeType::Percent->value,
            'value' => 10,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    Notification::assertNothingSent();
});

test('referral code created sms uses the editable template defaults', function () {
    $promo = PromoCode::factory()->forReferrer('0244111222', 'Kojo Boateng')->create([
        'code' => 'KOJO10',
        'type' => PromoCodeType::Percent,
        'value' => 10,
    ]);

    $message = (new ReferralCodeCreatedSmsNotification($promo))->toMnotify(
        Notification::route(MnotifyChannel::class, '0244111222'),
    );

    expect($message)->toBe(str_replace(
        ['{first_name}', '{promo_code}', '{discount_label}'],
        ['Kojo', 'KOJO10', '10% off'],
        SmsTemplate::ReferralCodeCreated->defaultMessage(),
    ));
});

test('admins can disable a promo code from the table', function () {
    $promo = PromoCode::factory()->create([
        'code' => 'PAUSEME',
        'is_active' => true,
    ]);

    Livewire::test(ListPromoCodes::class)
        ->callTableAction('disable', $promo)
        ->assertHasNoTableActionErrors();

    expect($promo->fresh()->is_active)->toBeFalse();
});

test('admins can edit a promo code', function () {
    $promo = PromoCode::factory()->create([
        'code' => 'EDITME',
        'value' => 10,
    ]);

    Livewire::test(EditPromoCode::class, ['record' => $promo->getKey()])
        ->assertOk()
        ->fillForm([
            'value' => 15,
            'label' => '15% off',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $promo->refresh();

    expect($promo->value)->toBe('15.00')
        ->and($promo->label)->toBe('15% off');
});
