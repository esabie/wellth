<?php

use App\Actions\MarkOrderDelivered;
use App\Actions\SendOrderFeedbackInvite;
use App\Enums\OrderStatus;
use App\Filament\Resources\Feedback\FeedbackResource;
use App\Filament\Resources\Feedback\Pages\ListFeedback;
use App\Filament\Resources\Feedback\Pages\ViewFeedback;
use App\Models\Feedback;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Notifications\FeedbackSubmittedAdminNotification;
use App\Notifications\OrderDeliveredCustomerSmsNotification;
use App\Notifications\OrderFeedbackInviteSmsNotification;
use App\Support\PackingReport;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.mnotify.api_key' => 'test-key',
        'shop.admin_notification_email' => 'ops@wellth.test',
    ]);
});

function deliveredOrderWithItems(): Order
{
    $order = Order::factory()->delivered()->create([
        'customer_name' => 'Ama Mensah',
        'customer_phone' => '0244111222',
        'feedback_token' => 'feedbacktoken1234567890abcdefghijklmnop',
    ]);

    $ginja = Product::factory()->create(['name' => 'Ginja', 'slug' => 'ginja-feedback']);
    $glow = Product::factory()->create(['name' => 'Glow Getter', 'slug' => 'glow-feedback']);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $ginja->id,
        'product_name' => 'Ginja',
        'quantity' => 6,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $glow->id,
        'product_name' => 'Glow Getter',
        'quantity' => 4,
    ]);

    return $order->fresh(['items']);
}

test('delivered sms does not include a feedback url', function () {
    Notification::fake();

    $order = Order::factory()->shipped()->create([
        'customer_name' => 'Ama Mensah',
        'customer_phone' => '0244111222',
    ]);

    OrderItem::factory()->create(['order_id' => $order->id]);

    $order = app(MarkOrderDelivered::class)->handle($order);

    expect($order->feedback_token)->toBeNull()
        ->and($order->feedback_invited_at)->toBeNull()
        ->and($order->feedback_auto_invite_on?->toDateString())
        ->toBe(Order::feedbackAutoInviteOnFor($order->delivered_at));

    Notification::assertSentOnDemand(
        OrderDeliveredCustomerSmsNotification::class,
        function (OrderDeliveredCustomerSmsNotification $notification, array $channels, object $notifiable): bool {
            $message = $notification->toMnotify($notifiable);

            return $message === 'Hi Ama, your order has been delivered. Enjoy your Wellth shots and enjoy 5% off your next order.'
                && ! str_contains($message, '/feedback/');
        },
    );
});

test('delivered sms omits the loyalty perk when the next order would not qualify', function () {
    Notification::fake();

    config([
        'shop.second_purchase_discount_enabled' => true,
        'shop.second_purchase_discount_percent' => 5,
    ]);

    $phone = '0244111222';

    Order::factory()->delivered()->create([
        'customer_phone' => $phone,
        'customer_phone_normalized' => '233244111222',
    ]);

    $order = Order::factory()->shipped()->create([
        'customer_name' => 'Ama Mensah',
        'customer_phone' => $phone,
        'customer_phone_normalized' => '233244111222',
    ]);

    OrderItem::factory()->create(['order_id' => $order->id]);

    app(MarkOrderDelivered::class)->handle($order);

    Notification::assertSentOnDemand(
        OrderDeliveredCustomerSmsNotification::class,
        function (OrderDeliveredCustomerSmsNotification $notification, array $channels, object $notifiable): bool {
            return $notification->toMnotify($notifiable)
                === 'Hi Ama, your order has been delivered. Enjoy your Wellth shots.';
        },
    );
});

test('feedback form shows only blends from the order', function () {
    $order = deliveredOrderWithItems();

    $this->get(route('feedback.show', $order->feedback_token))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Feedback/Show')
            ->where('order.uuid', $order->uuid)
            ->where('order.first_name', 'Ama')
            ->has('products', 2)
            ->where('products.0.name', 'Ginja')
            ->where('products.1.name', 'Glow Getter')
            ->where('includes_bouquet', false)
            ->has('shopping_ratings', 5)
            ->has('improvement_tags', 8)
            ->where('submitted', false)
        );
});

test('bouquet orders expand into the individual bouquet blends', function () {
    $bouquet = Product::factory()->create([
        'name' => 'Wellth Bouquet',
        'slug' => PackingReport::BOUQUET_SLUG,
    ]);

    $blendNames = [
        'ginja' => 'Ginja',
        'glow-getter' => 'Glow Getter',
        'ruby-rush' => 'Ruby Rush',
        'crave-control' => 'Crave Control',
        'tropic-cool' => 'Belly Reset',
        'abc' => 'ABC',
    ];

    foreach ($blendNames as $slug => $name) {
        Product::factory()->create([
            'slug' => $slug,
            'name' => $name,
        ]);
    }

    $order = Order::factory()->delivered()->create([
        'customer_name' => 'Ama Mensah',
        'customer_phone' => '0244111222',
        'feedback_token' => 'bouquetfeedbacktoken1234567890abcdef',
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $bouquet->id,
        'product_name' => 'Wellth Bouquet',
        'product_slug' => PackingReport::BOUQUET_SLUG,
        'quantity' => 1,
    ]);

    $this->get(route('feedback.show', $order->feedback_token))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Feedback/Show')
            ->where('includes_bouquet', true)
            ->has('products', 6)
            ->where('products.0.name', 'Ginja')
            ->where('products.0.from_bouquet', true)
            ->where('products.5.name', 'ABC')
            ->where('products', fn ($products): bool => collect($products)->every(
                fn ($product): bool => data_get($product, 'name') !== 'Wellth Bouquet',
            ))
        );

    $favourite = Product::query()->where('slug', 'ginja')->firstOrFail();

    $this->post(route('feedback.store', $order->feedback_token), [
        'product_id' => $favourite->id,
        'shopping_rating' => 5,
        'improvement_tags' => ['all_good'],
    ])->assertRedirect(route('feedback.show', $order->feedback_token));

    expect(Feedback::query()->where('order_id', $order->id)->value('product_id'))->toBe($favourite->id);
});

test('customers can submit feedback for a delivered order', function () {
    Notification::fake();

    $logged = [];
    Log::listen(function (object $event) use (&$logged): void {
        if (str_contains($event->message, 'feedback for order')
            || str_contains($event->message, 'Saved feedback for order')
            || str_contains($event->message, 'Visitor submitted feedback')) {
            $logged[] = $event->message;
        }
    });

    $order = deliveredOrderWithItems();
    $favoriteId = (int) $order->items->first()->product_id;

    $this->post(route('feedback.store', $order->feedback_token), [
        'product_id' => $favoriteId,
        'shopping_rating' => 5,
        'improvement_tags' => ['taste', 'delivery'],
        'note' => 'More ginger heat please.',
    ])
        ->assertRedirect(route('feedback.show', $order->feedback_token))
        ->assertSessionHas('feedback_received', true);

    $feedback = Feedback::query()->where('order_id', $order->id)->first();

    expect($feedback)->not->toBeNull()
        ->and($feedback->product_id)->toBe($favoriteId)
        ->and($feedback->shopping_rating)->toBe(5)
        ->and($feedback->improvement_tags)->toBe(['taste', 'delivery'])
        ->and($feedback->note)->toBe('More ginger heat please.')
        ->and($logged)->toContain("Saved feedback for order {$order->uuid}: favourite Ginja · shopping Loved it · Taste / flavour · Delivery timing · with a note.")
        ->and(collect($logged)->first(
            fn (string $message): bool => str_starts_with($message, "Visitor submitted feedback for order {$order->uuid}"),
        ))->toContain('favourite Ginja')
        ->and(collect($logged)->first(
            fn (string $message): bool => str_starts_with($message, "Visitor submitted feedback for order {$order->uuid}"),
        ))->toContain('with a note');

    Notification::assertSentOnDemand(FeedbackSubmittedAdminNotification::class);

    $this->get(route('feedback.show', $order->feedback_token))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('submitted', true)
            ->where('order.first_name', 'Ama')
            ->where('feedback.favorite_product', 'Ginja')
            ->where('feedback.shopping_label', 'Loved it')
            ->where('feedback.improvement_labels', ['Taste / flavour', 'Delivery timing'])
        );
});

test('feedback rejects products that were not on the order', function () {
    $order = deliveredOrderWithItems();
    $other = Product::factory()->create(['name' => 'Outsider']);

    $this->from(route('feedback.show', $order->feedback_token))
        ->post(route('feedback.store', $order->feedback_token), [
            'product_id' => $other->id,
            'shopping_rating' => 4,
            'improvement_tags' => ['all_good'],
        ])
        ->assertRedirect(route('feedback.show', $order->feedback_token))
        ->assertSessionHasErrors('feedback');

    expect(Feedback::query()->count())->toBe(0);
});

test('unknown or non-delivered feedback tokens return 404', function () {
    $this->get(route('feedback.show', 'missingtoken1234567890abcdefghijklmnop'))
        ->assertNotFound();

    $pending = Order::factory()->create([
        'status' => OrderStatus::Pending,
        'feedback_token' => 'pendingtoken1234567890abcdefghijklmnop',
    ]);

    $this->get(route('feedback.show', $pending->feedback_token))
        ->assertNotFound();
});

test('second feedback submission is blocked', function () {
    $order = deliveredOrderWithItems();
    $favoriteId = (int) $order->items->first()->product_id;

    Feedback::factory()->forOrder($order)->create();

    $this->from(route('feedback.show', $order->feedback_token))
        ->post(route('feedback.store', $order->feedback_token), [
            'product_id' => $favoriteId,
            'shopping_rating' => 3,
            'improvement_tags' => ['website'],
        ])
        ->assertRedirect(route('feedback.show', $order->feedback_token))
        ->assertSessionHasErrors('feedback');

    expect(Feedback::query()->count())->toBe(1);
});

test('artisan command dry-run lists delivered orders without feedback', function () {
    Notification::fake();

    $order = deliveredOrderWithItems();
    $order->update(['feedback_invited_at' => null]);

    $this->artisan('orders:send-feedback-invites', ['--dry-run' => true])
        ->expectsOutputToContain($order->uuid)
        ->assertSuccessful();

    Notification::assertNothingSent();
});

test('artisan command sends feedback invites for past deliveries', function () {
    Notification::fake();

    $order = deliveredOrderWithItems();
    $order->update([
        'feedback_token' => null,
        'feedback_invited_at' => null,
    ]);

    $this->artisan('orders:send-feedback-invites', ['--limit' => 10])
        ->assertSuccessful();

    $order->refresh();

    expect($order->feedback_token)->not->toBeNull()
        ->and($order->feedback_invited_at)->not->toBeNull();

    Notification::assertSentOnDemand(OrderFeedbackInviteSmsNotification::class);
});

test('scheduled due invites skip existing deliveries without an auto-invite date', function () {
    Notification::fake();

    $makeOrder = function (): Order {
        $order = Order::factory()->delivered()->create([
            'customer_phone' => '0244111222',
            'feedback_token' => null,
            'feedback_invited_at' => null,
            'feedback_auto_invite_on' => null,
        ]);
        OrderItem::factory()->create(['order_id' => $order->id]);

        return $order->fresh();
    };

    $existing = $makeOrder();

    $due = $makeOrder();
    $due->update([
        'delivered_at' => now('GMT')->subDays(3)->setTime(15, 0),
        'feedback_auto_invite_on' => now('GMT')->toDateString(),
    ]);

    $future = $makeOrder();
    $future->update([
        'delivered_at' => now('GMT'),
        'feedback_auto_invite_on' => now('GMT')->addDays(3)->toDateString(),
    ]);

    $this->artisan('orders:send-feedback-invites', ['--due' => true])
        ->expectsOutputToContain($due->uuid)
        ->assertSuccessful();

    expect($existing->fresh()->feedback_invited_at)->toBeNull()
        ->and($future->fresh()->feedback_invited_at)->toBeNull()
        ->and($due->fresh()->feedback_invited_at)->not->toBeNull();

    Notification::assertSentOnDemandTimes(OrderFeedbackInviteSmsNotification::class, 1);
});

test('feedback schedule runs daily at 09:00 GMT for due invites', function () {
    $events = collect(app(Schedule::class)->events())
        ->filter(fn ($event) => str_contains((string) $event->command, 'orders:send-feedback-invites'));

    expect($events)->toHaveCount(1);

    $event = $events->first();

    expect((string) $event->timezone)->toBe('GMT')
        ->and($event->expression)->toBe('0 9 * * *')
        ->and((string) $event->command)->toContain('--due');
});

test('feedback list shows summary stats for favourites scores and chips', function () {
    $admin = User::factory()->create();
    $orderA = deliveredOrderWithItems();
    $ginja = Product::query()->where('slug', 'ginja-feedback')->firstOrFail();
    $glow = Product::query()->where('slug', 'glow-feedback')->firstOrFail();

    $orderB = Order::factory()->delivered()->create([
        'customer_phone' => '0244333444',
    ]);
    OrderItem::factory()->create([
        'order_id' => $orderB->id,
        'product_id' => $ginja->id,
        'product_name' => 'Ginja',
    ]);

    Feedback::factory()->forOrder($orderA)->create([
        'product_id' => $ginja->id,
        'shopping_rating' => 5,
        'improvement_tags' => ['taste', 'freshness'],
    ]);
    Feedback::factory()->forOrder($orderB)->create([
        'product_id' => $ginja->id,
        'shopping_rating' => 3,
        'improvement_tags' => ['taste'],
    ]);
    Feedback::factory()->create([
        'order_id' => Order::factory()->delivered()->create()->id,
        'product_id' => $glow->id,
        'shopping_rating' => 4,
        'improvement_tags' => ['packaging'],
    ]);

    $this->actingAs($admin);

    Livewire::test(ListFeedback::class)
        ->assertSee('Average shopping / website score')
        ->assertSee('4 / 5')
        ->assertSee('Top favourite blend')
        ->assertSee('Ginja')
        ->assertSee('Top improvement chip')
        ->assertSee('Taste / flavour');
});

test('send feedback invite action texts a dedicated invite message', function () {
    Notification::fake();

    $order = deliveredOrderWithItems();
    $order->update(['feedback_invited_at' => null]);

    app(SendOrderFeedbackInvite::class)->handle($order);

    Notification::assertSentOnDemand(
        OrderFeedbackInviteSmsNotification::class,
        function (OrderFeedbackInviteSmsNotification $notification, array $channels, object $notifiable): bool {
            $message = $notification->toMnotify($notifiable);

            return str_contains($message, 'feedback')
                && str_contains($message, route('feedback.show', $notification->order->feedback_token));
        },
    );
});

test('admins can list and view feedback', function () {
    $admin = User::factory()->create();
    $order = deliveredOrderWithItems();
    $feedback = Feedback::factory()->forOrder($order)->create([
        'shopping_rating' => 5,
        'improvement_tags' => ['all_good'],
        'note' => 'Keep the celery.',
    ]);

    $this->actingAs($admin);

    Livewire::test(ListFeedback::class)
        ->assertCanSeeTableRecords([$feedback]);

    Livewire::test(ViewFeedback::class, [
        'record' => $feedback->getRouteKey(),
    ])->assertOk();

    expect(FeedbackResource::getUrl('view', ['record' => $feedback]))->toContain('/hq/');
});
