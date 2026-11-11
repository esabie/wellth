<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Notifications\Channels\MnotifyChannel;
use App\Notifications\OrderFeedbackInviteSmsNotification;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;

class SendOrderFeedbackInvite
{
    public function __construct(private EnsureOrderFeedbackToken $ensureToken) {}

    public function handle(Order $order): Order
    {
        if ($order->status !== OrderStatus::Delivered) {
            Log::warning("Skipped feedback invite for order {$order->uuid}: order is {$order->status->value}, not delivered.");

            throw new InvalidArgumentException('Only delivered orders can receive a feedback invite.');
        }

        if ($order->feedback()->exists()) {
            Log::warning("Skipped feedback invite for order {$order->uuid}: feedback was already submitted.");

            throw new InvalidArgumentException('This order already has feedback.');
        }

        if (blank($order->customer_phone)) {
            Log::warning("Skipped feedback invite for order {$order->uuid}: no customer phone number.");

            throw new InvalidArgumentException('This order has no customer phone number.');
        }

        if (! filled(config('services.mnotify.api_key'))) {
            Log::warning("Skipped feedback invite for order {$order->uuid}: SMS is not configured.");

            throw new InvalidArgumentException('SMS is not configured, so the feedback invite could not be sent.');
        }

        $wasPreviouslyInvited = $order->feedback_invited_at !== null;
        $order = $this->ensureToken->handle($order);

        Notification::route(MnotifyChannel::class, PhoneNumber::forSms($order->customer_phone))
            ->notify(new OrderFeedbackInviteSmsNotification($order));

        $order->forceFill([
            'feedback_invited_at' => now(),
        ])->save();

        $phone = PhoneNumber::normalize((string) $order->customer_phone);
        $url = $order->feedbackUrl() ?? '';
        $suffix = $wasPreviouslyInvited ? ' (resend)' : '';

        Log::info("Queued feedback invite SMS for order {$order->uuid}{$suffix} to {$phone}: {$url}");

        return $order->refresh();
    }
}
