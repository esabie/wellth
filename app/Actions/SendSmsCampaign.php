<?php

namespace App\Actions;

use App\Enums\SmsCampaignAudience;
use App\Enums\SmsCampaignStatus;
use App\Models\SmsCampaign;
use App\Models\User;
use App\Support\MnotifyClient;
use App\Support\SmsAudiences;
use Illuminate\Validation\ValidationException;
use Throwable;

class SendSmsCampaign
{
    public const CHUNK_SIZE = 100;

    public function __construct(
        private MnotifyClient $mnotify,
        private SmsAudiences $audiences,
    ) {}

    public function handle(
        SmsCampaignAudience $audience,
        string $message,
        ?User $user = null,
        ?string $customPhonesInput = null,
    ): SmsCampaign {
        $message = trim($message);

        if ($message === '') {
            throw ValidationException::withMessages([
                'message' => 'Enter a message to send.',
            ]);
        }

        if (mb_strlen($message) > 480) {
            throw ValidationException::withMessages([
                'message' => 'Messages can be at most 480 characters.',
            ]);
        }

        $customPhones = $audience === SmsCampaignAudience::CustomPhones
            ? $this->audiences->parseCustomPhones((string) $customPhonesInput)
            : null;

        if ($audience === SmsCampaignAudience::CustomPhones && ($customPhones ?? []) === []) {
            throw ValidationException::withMessages([
                'custom_phones' => 'Paste at least one valid Ghana phone number (024… or 233…).',
            ]);
        }

        $phones = $this->audiences->phones($audience, $customPhones);

        if ($phones === []) {
            throw ValidationException::withMessages([
                'audience' => 'No recipients found for this audience.',
            ]);
        }

        $senderId = (string) config('services.mnotify.sender_id');

        if (! filled($senderId)) {
            throw ValidationException::withMessages([
                'message' => 'mNotify sender ID is not configured.',
            ]);
        }

        if (! filled(config('services.mnotify.api_key'))) {
            throw ValidationException::withMessages([
                'message' => 'mNotify API key is not configured.',
            ]);
        }

        $campaign = SmsCampaign::query()->create([
            'user_id' => $user?->id,
            'audience' => $audience,
            'message' => $message,
            'sender_id' => $senderId,
            'status' => SmsCampaignStatus::Sending,
            'recipients_count' => count($phones),
            'sent_count' => 0,
            'failed_count' => 0,
            'recipient_phones' => $phones,
            'meta' => ['batches' => []],
        ]);

        $sentCount = 0;
        $failedCount = 0;
        $errors = [];
        $batches = [];

        foreach (array_chunk($phones, self::CHUNK_SIZE) as $chunk) {
            try {
                $result = $this->mnotify->send($chunk, $message);
                $sentCount += count($chunk);
                $batches[] = [
                    'recipients' => count($chunk),
                    'status' => 'success',
                    'campaign_id' => $result['summary']['_id'] ?? null,
                    'code' => $result['code'] ?? null,
                ];
            } catch (Throwable $exception) {
                $failedCount += count($chunk);
                $errors[] = $exception->getMessage();
                $batches[] = [
                    'recipients' => count($chunk),
                    'status' => 'failed',
                    'error' => $exception->getMessage(),
                ];
            }
        }

        $status = match (true) {
            $failedCount === 0 => SmsCampaignStatus::Completed,
            $sentCount === 0 => SmsCampaignStatus::Failed,
            default => SmsCampaignStatus::Partial,
        };

        $campaign->update([
            'status' => $status,
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'meta' => ['batches' => $batches],
            'error_message' => $errors === [] ? null : implode(' | ', array_unique($errors)),
            'sent_at' => now(),
        ]);

        return $campaign->refresh();
    }
}
