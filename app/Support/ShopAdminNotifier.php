<?php

namespace App\Support;

use App\Models\Feedback;
use App\Models\Order;
use App\Models\User;
use App\Notifications\FeedbackSubmittedAdminNotification;
use App\Notifications\OrderPaidAdminNotification;
use App\Notifications\OrderPaymentProofSubmittedNotification;
use App\Notifications\OrderPlacedAdminNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ShopAdminNotifier
{
    public function __construct(private ShopSettings $settings) {}

    public function orderPlaced(Order $order): void
    {
        $this->mail('order_placed', $order, new OrderPlacedAdminNotification($order));
        $this->database('order_placed', $order, new OrderPlacedAdminNotification($order));
    }

    public function paymentProofSubmitted(Order $order): void
    {
        $this->mail('payment_proof', $order, new OrderPaymentProofSubmittedNotification($order));
        $this->database('payment_proof', $order, new OrderPaymentProofSubmittedNotification($order));
    }

    public function orderPaid(Order $order): void
    {
        $this->mail('order_paid', $order, new OrderPaidAdminNotification($order));
        $this->database('order_paid', $order, new OrderPaidAdminNotification($order));
    }

    public function feedbackSubmitted(Feedback $feedback): void
    {
        $order = $feedback->order;
        $notification = new FeedbackSubmittedAdminNotification($feedback);

        $this->mail('feedback_submitted', $order, $notification);
        $this->database('feedback_submitted', $order, $notification);
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
}
