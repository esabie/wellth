<?php

namespace App\Support;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class MnotifyClient
{
    /**
     * @param  list<string>  $recipients
     * @return array{status: string, code: string, message: string, summary: array<string, mixed>}
     */
    public function send(array $recipients, string $message): array
    {
        $recipients = array_values(array_filter($recipients, fn (string $number): bool => filled($number)));

        if ($recipients === []) {
            throw new RuntimeException('At least one SMS recipient is required.');
        }

        if (! filled($message)) {
            throw new RuntimeException('SMS message content is required.');
        }

        $sender = (string) config('services.mnotify.sender_id');

        if (! filled($sender)) {
            throw new RuntimeException('mNotify sender ID is not configured.');
        }

        try {
            /** @var array{status?: string, code?: string|int, message?: string, summary?: array<string, mixed>} $body */
            $body = $this->client()
                ->post('/sms/quick', [
                    'recipient' => $recipients,
                    'sender' => $sender,
                    'message' => $message,
                    'is_schedule' => false,
                    'schedule_date' => '',
                ])
                ->throw()
                ->json();
        } catch (Throwable $exception) {
            Log::error('mnotify.send_failed', [
                'recipients' => $recipients,
                'sender' => $sender,
                'message_length' => strlen($message),
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        if (($body['status'] ?? null) !== 'success') {
            Log::error('mnotify.send_rejected', [
                'recipients' => $recipients,
                'sender' => $sender,
                'status' => $body['status'] ?? null,
                'code' => $body['code'] ?? null,
                'message' => $body['message'] ?? null,
            ]);

            throw new RuntimeException($body['message'] ?? 'Unable to send SMS via mNotify.');
        }

        Log::info('mnotify.send_succeeded', [
            'recipients' => $recipients,
            'sender' => $sender,
            'code' => $body['code'] ?? null,
            'campaign_id' => $body['summary']['_id'] ?? null,
            'message_length' => strlen($message),
        ]);

        return [
            'status' => (string) ($body['status'] ?? 'success'),
            'code' => (string) ($body['code'] ?? ''),
            'message' => (string) ($body['message'] ?? ''),
            'summary' => $body['summary'] ?? [],
        ];
    }

    private function client(): PendingRequest
    {
        $apiKey = config('services.mnotify.api_key');

        if (! filled($apiKey)) {
            throw new RuntimeException('mNotify API key is not configured.');
        }

        return Http::baseUrl((string) config('services.mnotify.base_url'))
            ->withQueryParameters(['key' => (string) $apiKey])
            ->acceptJson()
            ->asJson()
            ->connectTimeout(5)
            ->timeout(15);
    }
}
