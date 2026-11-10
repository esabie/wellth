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

class OrderDeliveredCustomerSmsNotification extends Notification implements ShouldQueue
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
        $firstName = str($this->order->customer_name)->trim()->before(' ')->toString() ?: 'there';
        $loyalty = app(SecondPurchaseDiscount::class);
        $phone = (string) ($this->order->customer_phone_normalized ?: $this->order->customer_phone);

        return app(SmsTemplates::class)->render(SmsTemplate::OrderDeliveredCustomer, [
            'first_name' => $firstName,
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

        return ' and enjoy '.$percentLabel.'% off your next order';
    }
}
