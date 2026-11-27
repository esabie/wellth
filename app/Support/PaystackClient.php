<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class PaystackClient
{
    /**
     * @param  array{email: string, amount: int, reference: string, callback_url: string, currency?: string, metadata?: array<string, mixed>}  $payload
     * @return array{authorization_url: string, access_code: string, reference: string}
     */
    public function initialize(array $payload): array
    {
        Log::info('paystack.initialize_request', [
            'base_url' => config('services.paystack.base_url'),
            'key_mode' => $this->keyMode(),
            'email' => $payload['email'] ?? null,
            'amount' => $payload['amount'] ?? null,
            'currency' => $payload['currency'] ?? null,
            'reference' => $payload['reference'] ?? null,
            'callback_url' => $payload['callback_url'] ?? null,
            'metadata' => $payload['metadata'] ?? null,
        ]);

        try {
            $response = $this->client()
                ->post('/transaction/initialize', $payload)
                ->throw();
        } catch (ConnectionException $exception) {
            Log::error('paystack.initialize_connection_failed', [
                'reference' => $payload['reference'] ?? null,
                'base_url' => config('services.paystack.base_url'),
                'key_mode' => $this->keyMode(),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            throw new RuntimeException('Unable to reach Paystack.', previous: $exception);
        } catch (RequestException $exception) {
            Log::error('paystack.initialize_http_error', [
                'reference' => $payload['reference'] ?? null,
                'base_url' => config('services.paystack.base_url'),
                'key_mode' => $this->keyMode(),
                'status' => $exception->response?->status(),
                'response' => $this->responseBody($exception->response),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            throw new RuntimeException(
                $exception->response?->json('message') ?? 'Unable to initialize Paystack payment.',
                previous: $exception,
            );
        } catch (Throwable $exception) {
            Log::error('paystack.initialize_failed', [
                'reference' => $payload['reference'] ?? null,
                'base_url' => config('services.paystack.base_url'),
                'key_mode' => $this->keyMode(),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        /** @var array{status?: bool, message?: string, data?: array{authorization_url?: string, access_code?: string, reference?: string}} $body */
        $body = $response->json();

        if (($body['status'] ?? false) !== true || ! isset($body['data']['authorization_url'], $body['data']['access_code'], $body['data']['reference'])) {
            Log::error('paystack.initialize_rejected', [
                'reference' => $payload['reference'] ?? null,
                'key_mode' => $this->keyMode(),
                'message' => $body['message'] ?? null,
                'response' => $body,
            ]);

            throw new RuntimeException($body['message'] ?? 'Unable to initialize Paystack payment.');
        }

        Log::info('paystack.initialize_response', [
            'reference' => $body['data']['reference'],
            'message' => $body['message'] ?? null,
            'has_authorization_url' => filled($body['data']['authorization_url']),
        ]);

        return [
            'authorization_url' => $body['data']['authorization_url'],
            'access_code' => $body['data']['access_code'],
            'reference' => $body['data']['reference'],
        ];
    }

    /**
     * @return array{status: bool, amount: int, currency: string, reference: string, paid_at: string|null, message: string|null, transaction_status: string|null, gateway_response: string|null}
     */
    public function verify(string $reference): array
    {
        Log::info('paystack.verify_request', [
            'reference' => $reference,
            'base_url' => config('services.paystack.base_url'),
            'key_mode' => $this->keyMode(),
        ]);

        try {
            $response = $this->client()
                ->get('/transaction/verify/'.rawurlencode($reference))
                ->throw();
        } catch (ConnectionException $exception) {
            Log::error('paystack.verify_connection_failed', [
                'reference' => $reference,
                'base_url' => config('services.paystack.base_url'),
                'key_mode' => $this->keyMode(),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            throw new RuntimeException('Unable to reach Paystack.', previous: $exception);
        } catch (RequestException $exception) {
            Log::error('paystack.verify_http_error', [
                'reference' => $reference,
                'base_url' => config('services.paystack.base_url'),
                'key_mode' => $this->keyMode(),
                'status' => $exception->response?->status(),
                'response' => $this->responseBody($exception->response),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            throw new RuntimeException(
                $exception->response?->json('message') ?? 'Unable to verify Paystack payment.',
                previous: $exception,
            );
        } catch (Throwable $exception) {
            Log::error('paystack.verify_failed', [
                'reference' => $reference,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        /** @var array{status?: bool, message?: string, data?: array{status?: string, amount?: int, currency?: string, reference?: string, paid_at?: string|null, gateway_response?: string|null}} $body */
        $body = $response->json();

        $data = $body['data'] ?? [];
        $result = [
            'status' => ($body['status'] ?? false) === true && ($data['status'] ?? null) === 'success',
            'amount' => (int) ($data['amount'] ?? 0),
            'currency' => (string) ($data['currency'] ?? ''),
            'reference' => (string) ($data['reference'] ?? $reference),
            'paid_at' => $data['paid_at'] ?? null,
            'message' => isset($body['message']) ? (string) $body['message'] : null,
            'transaction_status' => isset($data['status']) ? (string) $data['status'] : null,
            'gateway_response' => isset($data['gateway_response']) ? (string) $data['gateway_response'] : null,
        ];

        Log::info('paystack.verify_response', [
            'reference' => $result['reference'],
            'http_ok' => $response->successful(),
            'paystack_status' => $body['status'] ?? null,
            'message' => $result['message'],
            'transaction_status' => $result['transaction_status'],
            'gateway_response' => $result['gateway_response'],
            'amount' => $result['amount'],
            'currency' => $result['currency'],
            'paid_at' => $result['paid_at'],
        ]);

        return $result;
    }

    public function isValidWebhookSignature(string $payload, ?string $signature): bool
    {
        if (! filled($signature)) {
            Log::warning('paystack.webhook_signature_missing', [
                'payload_bytes' => strlen($payload),
                'key_mode' => $this->keyMode(),
            ]);

            return false;
        }

        $secret = config('services.paystack.secret_key');

        if (! filled($secret)) {
            Log::error('paystack.webhook_secret_missing', [
                'payload_bytes' => strlen($payload),
            ]);

            return false;
        }

        $computed = hash_hmac('sha512', $payload, (string) $secret);
        $valid = hash_equals($computed, $signature);

        if (! $valid) {
            Log::warning('paystack.webhook_signature_mismatch', [
                'payload_bytes' => strlen($payload),
                'key_mode' => $this->keyMode(),
                'signature_length' => strlen($signature),
            ]);
        }

        return $valid;
    }

    private function client(): PendingRequest
    {
        $secret = config('services.paystack.secret_key');

        if (! filled($secret)) {
            Log::error('paystack.missing_secret_key', [
                'base_url' => config('services.paystack.base_url'),
            ]);

            throw new RuntimeException('Paystack secret key is not configured.');
        }

        return Http::baseUrl((string) config('services.paystack.base_url'))
            ->withToken((string) $secret)
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(15);
    }

    private function keyMode(): string
    {
        $secret = (string) config('services.paystack.secret_key');

        if ($secret === '') {
            return 'missing';
        }

        if (str_starts_with($secret, 'sk_live_')) {
            return 'live';
        }

        if (str_starts_with($secret, 'sk_test_')) {
            return 'test';
        }

        return 'unrecognized';
    }

    private function responseBody(?Response $response): mixed
    {
        if ($response === null) {
            return null;
        }

        $json = $response->json();

        if (is_array($json)) {
            return $json;
        }

        return mb_substr($response->body(), 0, 2000);
    }
}
