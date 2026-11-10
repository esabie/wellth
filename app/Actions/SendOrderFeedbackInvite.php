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
            throw new InvalidArgumentException('Only delivered orders can receive a feedback invite.');
        }

        if ($order->feedback()->exists()) {
            throw new InvalidArgumentException('This order already has feedback.');
        }

        if (blank($order->customer_phone)) {
            throw new InvalidArgumentException('This order has no customer phone number.');
        }

        if (! filled(config('services.mnotify.api_key'))) {
            Log::warning('feedback.invite_sms_skipped', [
                'order' => $order->uuid,
                'reason' => 'mnotify_not_configured',
            ]);

            throw new InvalidArgumentException('SMS is not configured, so the feedback invite could not be sent.');
        }

        $order = $this->ensureToken->handle($order);

        Notification::route(MnotifyChannel::class, PhoneNumber::forSms($order->customer_phone))
            ->notify(new OrderFeedbackInviteSmsNotification($order));

        $order->forceFill([
            'feedback_invited_at' => now(),
        ])->save();

        Log::info('feedback.invite_sms_queued', [
            'order' => $order->uuid,
            'phone' => PhoneNumber::normalize((string) $order->customer_phone),
            'feedback_url' => $order->feedbackUrl(),
        ]);

        return $order->refresh();
    }
}
