<?php

namespace App\Actions;

use App\Models\PromoCode;
use App\Notifications\Channels\MnotifyChannel;
use App\Notifications\ReferralCodeCreatedSmsNotification;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendReferralCodeCreatedSms
{
    public function handle(PromoCode $promoCode): bool
    {
        if (! $promoCode->hasReferrer()) {
            return false;
        }

        $phone = (string) ($promoCode->referrer_phone_normalized ?: $promoCode->referrer_phone);

        if (blank($phone)) {
            Log::warning("Skipped referral code SMS for {$promoCode->code}: no referrer phone.");

            return false;
        }

        if (! filled(config('services.mnotify.api_key'))) {
            Log::warning("Skipped referral code SMS for {$promoCode->code}: SMS is not configured.");

            return false;
        }

        Notification::route(MnotifyChannel::class, PhoneNumber::forSms($phone))
            ->notify(new ReferralCodeCreatedSmsNotification($promoCode));

        $normalized = PhoneNumber::normalize($phone);

        Log::info("Queued referral code SMS for {$promoCode->code} to {$normalized}.");

        return true;
    }
}
