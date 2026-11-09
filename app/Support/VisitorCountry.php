<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class VisitorCountry
{
    /**
     * Resolve a visitor's ISO 3166-1 alpha-2 country code, or null when unknown.
     */
    public function code(Request $request): ?string
    {
        $fromHeader = $this->fromTrustedHeaders($request);

        if ($fromHeader !== null) {
            return $fromHeader;
        }

        $ip = $request->ip();

        if ($ip === null || $this->isNonPublicIp($ip)) {
            return null;
        }

        return $this->lookup($ip);
    }

    private function fromTrustedHeaders(Request $request): ?string
    {
        foreach (['CF-IPCountry', 'CloudFront-Viewer-Country', 'X-AppEngine-Country'] as $header) {
            $value = strtoupper(trim((string) $request->headers->get($header, '')));

            if ($value !== '' && $value !== 'XX' && preg_match('/^[A-Z]{2}$/', $value) === 1) {
                return $value;
            }
        }

        return null;
    }

    private function lookup(string $ip): ?string
    {
        $cacheKey = 'visitor_country:'.sha1($ip);

        /** @var string|null $cached */
        $cached = Cache::get($cacheKey);

        if (is_string($cached) && preg_match('/^[A-Z]{2}$/', $cached) === 1) {
            return $cached;
        }

        if ($cached === '') {
            return null;
        }

        try {
            $response = Http::timeout(2)
                ->acceptJson()
                ->get('http://ip-api.com/json/'.$ip, [
                    'fields' => 'status,countryCode',
                ]);

            if (! $response->successful()) {
                Cache::put($cacheKey, '', now()->addMinutes(15));

                return null;
            }

            $payload = $response->json();
            $code = strtoupper(trim((string) ($payload['countryCode'] ?? '')));

            if (($payload['status'] ?? null) !== 'success' || preg_match('/^[A-Z]{2}$/', $code) !== 1) {
                Cache::put($cacheKey, '', now()->addHour());

                return null;
            }

            Cache::put($cacheKey, $code, now()->addDays(30));

            return $code;
        } catch (Throwable $exception) {
            Log::warning('visitor_country.lookup_failed', [
                'ip' => $ip,
                'message' => $exception->getMessage(),
            ]);

            Cache::put($cacheKey, '', now()->addMinutes(15));

            return null;
        }
    }

    private function isNonPublicIp(string $ip): bool
    {
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return true;
        }

        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        ) === false;
    }
}
