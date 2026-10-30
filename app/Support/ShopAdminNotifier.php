<?php

namespace App\Support;

use App\Models\Order;
use App\Models\User;
use App\Notifications\Channels\MnotifyChannel;
use App\Notifications\OrderPaidAdminNotification;
use App\Notifications\OrderPaymentProofSubmittedNotification;
use App\Notifications\OrderPaymentProofSubmittedSmsNotification;
use App\Notifications\OrderPlacedAdminNotification;
use App\Notifications\OrderPlacedAdminSmsNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ShopAdminNotifier
{
    public function __construct(private ShopSettings $settings) {}

    public function orderPlaced(Order $order): void
    {
        $this->mail('order_placed', $order, new OrderPlacedAdminNotification($order));
        $this->database('order_placed', $order, new OrderPlacedAdminNotification($order));
        $this->sms('order_placed', $order, new OrderPlacedAdminSmsNotification($order));
    }

    public function paymentProofSubmitted(Order $order): void
    {
        $this->mail('payment_proof', $order, new OrderPaymentProofSubmittedNotification($order));
        $this->database('payment_proof', $order, new OrderPaymentProofSubmittedNotification($order));

        foreach ($this->paymentProofPhones() as $phone) {
            $this->sms(
                'payment_proof',
                $order,
                new OrderPaymentProofSubmittedSmsNotification($order),
                $phone,
            );
        }
    }

    public function orderPaid(Order $order): void
    {
        $this->mail('order_paid', $order, new OrderPaidAdminNotification($order));
        $this->database('order_paid', $order, new OrderPaidAdminNotification($order));
    }

    private function mail(string $event, Order $order, object $notification): void
    {
        $email = $this->settings->adminNotificationEmail();

        if (blank($email)) {
            Log::warning('admin.alert_email_skipped', [
                'event' => $event,
                'order' => $order->uuid,
                'reason' => 'admin_email_not_configured',
            ]);

            return;
        }

        Notification::route('mail', $email)->notify($notification);

        Log::info('admin.alert_email_queued', [
            'event' => $event,
            'order' => $order->uuid,
            'email' => $email,
        ]);
    }

    private function database(string $event, Order $order, object $notification): void
    {
        $admins = User::query()->get();

        if ($admins->isEmpty()) {
            Log::warning('admin.alert_database_skipped', [
                'event' => $event,
                'order' => $order->uuid,
                'reason' => 'no_admin_users',
            ]);

            return;
        }

        Notification::send($admins, $notification);

        Log::info('admin.alert_database_queued', [
            'event' => $event,
            'order' => $order->uuid,
            'recipients' => $admins->count(),
        ]);
    }

    /**
     * @return list<string>
     */
    private function paymentProofPhones(): array
    {
        $phones = [];
        $configured = $this->settings->adminNotificationPhone();

        if (filled($configured)) {
            $phones[PhoneNumber::normalize($configured)] = $configured;
        }

        foreach (config('shop.payment_proof_admin_phones', []) as $phone) {
            $phone = trim((string) $phone);

            if ($phone === '') {
                continue;
            }

            $phones[PhoneNumber::normalize($phone)] = $phone;
        }

        return array_values($phones);
    }

    private function sms(string $event, Order $order, object $notification, ?string $phone = null): void
    {
        if (! filled(config('services.mnotify.api_key'))) {
            Log::warning('admin.alert_sms_skipped', [
                'event' => $event,
                'order' => $order->uuid,
                'reason' => 'mnotify_not_configured',
            ]);

            return;
        }

        $phone ??= $this->settings->adminNotificationPhone();

        if (blank($phone)) {
            Log::warning('admin.alert_sms_skipped', [
                'event' => $event,
                'order' => $order->uuid,
                'reason' => 'admin_phone_not_configured',
            ]);

            return;
        }

        Notification::route(MnotifyChannel::class, PhoneNumber::forSms($phone))
            ->notify($notification);

        Log::info('admin.alert_sms_queued', [
            'event' => $event,
            'order' => $order->uuid,
            'phone' => PhoneNumber::normalize($phone),
        ]);
    }
}
