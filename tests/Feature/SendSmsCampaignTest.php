<?php

use App\Actions\SendSmsCampaign;
use App\Enums\OrderStatus;
use App\Enums\SmsCampaignAudience;
use App\Enums\SmsCampaignStatus;
use App\Filament\Pages\SendSms;
use App\Models\Order;
use App\Models\SmsCampaign;
use App\Models\User;
use App\Support\PhoneNumber;
use App\Support\SmsAudiences;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.mnotify.api_key' => 'test-mnotify-key',
        'services.mnotify.sender_id' => 'Wellth',
        'services.mnotify.base_url' => 'https://api.mnotify.com/api',
    ]);
});

test('all customers audience collects unique phones from every order', function () {
    Order::factory()->create([
        'status' => OrderStatus::Paid,
        'customer_phone' => '0244111222',
        'customer_phone_normalized' => PhoneNumber::normalize('0244111222'),
    ]);
    Order::factory()->create([
        'status' => OrderStatus::Preparing,
        'customer_phone' => '0244111222',
        'customer_phone_normalized' => PhoneNumber::normalize('0244111222'),
    ]);
    Order::factory()->create([
        'status' => OrderStatus::Pending,
        'customer_phone' => '0200000111',
        'customer_phone_normalized' => PhoneNumber::normalize('0200000111'),
    ]);

    $audiences = app(SmsAudiences::class);

    expect($audiences->count(SmsCampaignAudience::AllCustomers))->toBe(2)
        ->and($audiences->phones(SmsCampaignAudience::AllCustomers))
        ->toEqualCanonicalizing(['0244111222', '0200000111']);
});

test('custom phones are parsed from pasted ghana numbers', function () {
    $phones = app(SmsAudiences::class)->parseCustomPhones("0241234567\n0209876543, 233244111222");

    expect($phones)->toEqualCanonicalizing(['0241234567', '0209876543', '0244111222']);
});

test('send sms campaign posts to mnotify for all customers', function () {
    Http::preventStrayRequests();
    Http::fake([
        'api.mnotify.com/api/sms/quick*' => Http::response([
            'status' => 'success',
            'code' => '2000',
            'message' => 'messages sent successfully',
            'summary' => [
                '_id' => 'campaign-bulk-1',
                'total_sent' => 2,
            ],
        ]),
    ]);

    Order::factory()->create([
        'status' => OrderStatus::Paid,
        'customer_phone' => '0244111222',
    ]);
    Order::factory()->create([
        'status' => OrderStatus::Shipped,
        'customer_phone' => '0200000111',
    ]);

    $admin = User::factory()->create(['name' => 'Ops Admin']);

    $campaign = app(SendSmsCampaign::class)->handle(
        SmsCampaignAudience::AllCustomers,
        'Fresh shots are ready this week. Reply STOP to opt out.',
        $admin,
    );

    expect($campaign->status)->toBe(SmsCampaignStatus::Completed)
        ->and($campaign->audience)->toBe(SmsCampaignAudience::AllCustomers)
        ->and($campaign->recipients_count)->toBe(2)
        ->and($campaign->sent_count)->toBe(2)
        ->and($campaign->failed_count)->toBe(0)
        ->and($campaign->user_id)->toBe($admin->id)
        ->and($campaign->sender_id)->toBe('Wellth')
        ->and($campaign->recipient_phones)->toEqualCanonicalizing(['0244111222', '0200000111']);

    Http::assertSent(function (Request $request): bool {
        return str_contains($request->url(), 'sms/quick')
            && $request['sender'] === 'Wellth'
            && $request['message'] === 'Fresh shots are ready this week. Reply STOP to opt out.'
            && count($request['recipient']) === 2;
    });
});

test('send sms campaign accepts custom pasted phone numbers', function () {
    Http::preventStrayRequests();
    Http::fake([
        'api.mnotify.com/api/sms/quick*' => Http::response([
            'status' => 'success',
            'code' => '2000',
            'message' => 'messages sent successfully',
            'summary' => ['_id' => 'campaign-custom-1'],
        ]),
    ]);

    $campaign = app(SendSmsCampaign::class)->handle(
        SmsCampaignAudience::CustomPhones,
        'Hello from Wellth.',
        null,
        "0241234567\n0209876543",
    );

    expect($campaign->status)->toBe(SmsCampaignStatus::Completed)
        ->and($campaign->audience)->toBe(SmsCampaignAudience::CustomPhones)
        ->and($campaign->recipients_count)->toBe(2)
        ->and($campaign->recipient_phones)->toEqualCanonicalizing(['0241234567', '0209876543']);

    Http::assertSent(function (Request $request): bool {
        return $request['recipient'] === ['0241234567', '0209876543']
            || $request['recipient'] === ['0209876543', '0241234567'];
    });
});

test('send sms campaign rejects empty audiences', function () {
    expect(fn () => app(SendSmsCampaign::class)->handle(
        SmsCampaignAudience::AllCustomers,
        'Please complete your payment.',
    ))->toThrow(ValidationException::class);

    expect(fn () => app(SendSmsCampaign::class)->handle(
        SmsCampaignAudience::CustomPhones,
        'Hello',
        null,
        'not-a-phone',
    ))->toThrow(ValidationException::class);
});

test('send sms campaign marks failed when mnotify rejects the request', function () {
    Http::preventStrayRequests();
    Http::fake([
        'api.mnotify.com/api/sms/quick*' => Http::response([
            'status' => 'error',
            'code' => '4004',
            'message' => 'credit is not enough to send message to recipients',
        ], 200),
    ]);

    Order::factory()->paid()->create([
        'customer_phone' => '0244111222',
    ]);

    $campaign = app(SendSmsCampaign::class)->handle(
        SmsCampaignAudience::AllCustomers,
        'Update from Wellth.',
    );

    expect($campaign->status)->toBe(SmsCampaignStatus::Failed)
        ->and($campaign->sent_count)->toBe(0)
        ->and($campaign->failed_count)->toBe(1)
        ->and($campaign->error_message)->toContain('credit is not enough');
});

test('admins can open the send sms page and dispatch a custom phone campaign', function () {
    Http::preventStrayRequests();
    Http::fake([
        'api.mnotify.com/api/sms/quick*' => Http::response([
            'status' => 'success',
            'code' => '2000',
            'message' => 'messages sent successfully',
            'summary' => ['_id' => 'campaign-ui-1'],
        ]),
    ]);

    $this->actingAs(User::factory()->create());

    Livewire::test(SendSms::class)
        ->assertOk()
        ->assertSee('Compose message')
        ->assertSee('Recent campaigns')
        ->assertDontSee('Phone numbers')
        ->fillForm([
            'audience' => SmsCampaignAudience::CustomPhones->value,
            'custom_phones' => "0241234567\n0209876543",
            'message' => 'Your Wellth order update is here.',
        ])
        ->assertSee('Phone numbers')
        ->call('send')
        ->assertNotified()
        ->assertHasNoFormErrors();

    expect(SmsCampaign::query()->count())->toBe(1)
        ->and(SmsCampaign::query()->first()->status)->toBe(SmsCampaignStatus::Completed)
        ->and(SmsCampaign::query()->first()->audience)->toBe(SmsCampaignAudience::CustomPhones);
});

test('admins can see recent campaigns on the send sms page', function () {
    $this->actingAs(User::factory()->create());

    $campaign = SmsCampaign::factory()->create([
        'message' => 'Partner update for all customers.',
        'audience' => SmsCampaignAudience::AllCustomers,
    ]);

    Livewire::test(SendSms::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$campaign])
        ->assertSee('Partner update for all customers.');
});
