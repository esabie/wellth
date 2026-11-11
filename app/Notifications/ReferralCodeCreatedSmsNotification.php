<?php

namespace App\Notifications;

use App\Enums\SmsTemplate;
use App\Models\PromoCode;
use App\Notifications\Channels\MnotifyChannel;
use App\Support\SmsTemplates;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ReferralCodeCreatedSmsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public PromoCode $promoCode)
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
        $firstName = filled($this->promoCode->referrer_name)
            ? str($this->promoCode->referrer_name)->trim()->before(' ')->toString()
            : 'there';

        return app(SmsTemplates::class)->render(SmsTemplate::ReferralCodeCreated, [
            'first_name' => $firstName !== '' ? $firstName : 'there',
            'promo_code' => $this->promoCode->code,
            'discount_label' => $this->promoCode->discountLabel(),
        ]);
    }
}
