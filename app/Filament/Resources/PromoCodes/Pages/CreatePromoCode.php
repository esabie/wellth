<?php

namespace App\Filament\Resources\PromoCodes\Pages;

use App\Actions\SendReferralCodeCreatedSms;
use App\Filament\Resources\PromoCodes\PromoCodeResource;
use App\Models\PromoCode;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreatePromoCode extends CreateRecord
{
    protected static string $resource = PromoCodeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        /** @var PromoCode $promo */
        $promo = $this->record;

        if (! $promo->hasReferrer()) {
            return;
        }

        $sent = app(SendReferralCodeCreatedSms::class)->handle($promo);

        Notification::make()
            ->title($sent ? 'Referral code SMS queued' : 'Referral code saved without SMS')
            ->body($sent
                ? "Texted {$promo->referrer_phone} their code {$promo->code}."
                : 'The code was saved, but the referrer SMS could not be queued. Check SMS settings or logs.')
            ->{$sent ? 'success' : 'warning'}()
            ->send();
    }
}
