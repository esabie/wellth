<?php

namespace App\Notifications;

use App\Enums\SmsTemplate;
use App\Models\Order;
use App\Notifications\Channels\MnotifyChannel;
use App\Support\SecondPurchaseDiscount;
use App\Support\SmsTemplates;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OrderPaidCustomerSmsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order)
    {
        $this->afterCommit();
    }

    /**
     * @return array<int, class-string>
     */
    public function via(object $notifiable): array
    {
        return [MnotifyChannel::class];
    }

    public function toMnotify(object $notifiable): string
    {
        $order = $this->order;
        $firstName = str($order->customer_name)->trim()->before(' ')->toString() ?: 'there';
        $loyalty = app(SecondPurchaseDiscount::class);
        $phone = (string) ($order->customer_phone_normalized ?: $order->customer_phone);

        return app(SmsTemplates::class)->render(SmsTemplate::OrderPaidCustomer, [
            'first_name' => $firstName,
            'order' => $order->uuid,
            'currency' => $order->currency ?: config('shop.currency', 'GHS'),
            'total' => number_format((float) $order->total, 2),
            'loyalty_suffix' => $this->loyaltySuffix($loyalty, $phone),
        ]);
    }

    private function loyaltySuffix(SecondPurchaseDiscount $loyalty, string $phone): string
    {
        if ($phone === '' || ! $loyalty->isEligible($phone)) {
            return '';
        }

        $percent = $loyalty->percent();
        $percentLabel = fmod($percent, 1.0) === 0.0
            ? (string) (int) $percent
            : rtrim(rtrim(number_format($percent, 2, '.', ''), '0'), '.');

        return ' Your next order gets '.$percentLabel.'% off.';
    }
}
