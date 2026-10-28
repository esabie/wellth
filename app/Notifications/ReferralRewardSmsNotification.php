<?php

namespace App\Notifications;

use App\Models\Order;
use App\Notifications\Channels\MnotifyChannel;
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
        return "Someone used your code {$this->promoCode}. You earned {$this->pointsEarned} points. Your balance is now {$this->balance} points. Thanks for sharing!";
    }
}
