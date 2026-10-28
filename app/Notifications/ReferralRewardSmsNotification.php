<?php

namespace App\Notifications;

use App\Enums\SmsTemplate;
use App\Models\Order;
use App\Notifications\Channels\MnotifyChannel;
use App\Support\SmsTemplates;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ReferralRewardSmsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public string $promoCode,
        public int $pointsEarned,
        public int $balance,
    ) {
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
        return app(SmsTemplates::class)->render(SmsTemplate::ReferralReward, [
            'promo_code' => $this->promoCode,
            'points' => $this->pointsEarned,
            'balance' => $this->balance,
        ]);
    }
}
