<?php

namespace App\Support;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PaystackClient
{
    /**
     * @param  array{email: string, amount: int, reference: string, callback_url: string, currency?: string, metadata?: array<string, mixed>}  $payload
     * @return array{authorization_url: string, access_code: string, reference: string}
     */
    public function initialize(array $payload): array
    {
        $response = $this->client()
            ->post('/transaction/initialize', $payload)
            ->throw();

        /** @var array{status?: bool, message?: string, data?: array{authorization_url?: string, access_code?: string, reference?: string}} $body */
        $body = $response->json();

        if (($body['status'] ?? false) !== true || ! isset($body['data']['authorization_url'], $body['data']['access_code'], $body['data']['reference'])) {
            throw new RuntimeException($body['message'] ?? 'Unable to initialize Paystack payment.');
        }

        return [
            'authorization_url' => $body['data']['authorization_url'],
            'access_code' => $body['data']['access_code'],
            'reference' => $body['data']['reference'],
        ];
    }

    /**
     * @return array{status: bool, amount: int, currency: string, reference: string, paid_at: string|null}
     */
    public function verify(string $reference): array
    {
        try {
            $response = $this->client()
                ->get('/transaction/verify/'.urlencode($reference))
                ->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException('Unable to verify Paystack payment.', previous: $exception);
        }

        /** @var array{status?: bool, message?: string, data?: array{status?: string, amount?: int, currency?: string, reference?: string, paid_at?: string|null}} $body */
        $body = $response->json();

        $data = $body['data'] ?? [];

        return [
            'status' => ($body['status'] ?? false) === true && ($data['status'] ?? null) === 'success',
            'amount' => (int) ($data['amount'] ?? 0),
            'currency' => (string) ($data['currency'] ?? ''),
            'reference' => (string) ($data['reference'] ?? $reference),
            'paid_at' => $data['paid_at'] ?? null,
        ];
    }

    private function client(): PendingRequest
    {
        $secret = config('services.paystack.secret_key');

        if (! filled($secret)) {
            throw new RuntimeException('Paystack secret key is not configured.');
        }

        return Http::baseUrl((string) config('services.paystack.base_url'))
            ->withToken((string) $secret)
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(15);
    }
}
