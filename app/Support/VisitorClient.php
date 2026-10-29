<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class VisitorClient
{
    /**
     * @return array{ip: string, device: string, browser: string, location: string}
     */
    public function details(Request $request): array
    {
        $ip = $request->ip() ?: 'unknown';

        return [
            'ip' => $ip,
            'device' => $this->device($request->userAgent() ?? ''),
            'browser' => $this->browser($request->userAgent() ?? ''),
            'location' => $this->location($ip),
        ];
    }

    public function device(string $userAgent): string
    {
        return match (true) {
            $userAgent === '' => 'Unknown device',
            str_contains($userAgent, 'iPad') => 'Tablet',
            str_contains($userAgent, 'iPhone') => 'Phone',
            str_contains($userAgent, 'Android') && ! str_contains($userAgent, 'Mobile') => 'Tablet',
            str_contains($userAgent, 'Mobile') || str_contains($userAgent, 'Android') => 'Phone',
            default => 'Computer',
        };
    }

    public function browser(string $userAgent): string
    {
        return match (true) {
            $userAgent === '' => 'Unknown browser',
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'OPR/') || str_contains($userAgent, 'Opera') => 'Opera',
            str_contains($userAgent, 'CriOS/') || str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Safari/') => 'Safari',
            default => 'Unknown browser',
        };
    }

    public function location(string $ip): string
    {
        if (! $this->isPublicIp($ip)) {
            return 'Local network';
        }

        $key = 'storefront-location:'.$ip;
        $cached = Cache::get($key);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $location = $this->lookup($ip);

        Cache::put(
            $key,
            $location,
            $location === 'Unknown' ? now()->addMinutes(15) : now()->addHours(12),
        );

        return $location;
    }

    private function lookup(string $ip): string
    {
        try {
            $response = Http::connectTimeout(1)
                ->timeout(1)
                ->acceptJson()
                ->get('https://ipwho.is/'.$ip);
        } catch (Throwable) {
            return 'Unknown';
        }

        if (! $response->successful() || $response->json('success') !== true) {
            return 'Unknown';
        }

        $city = $this->placeName($response->json('city'));
        $country = $this->placeName($response->json('country'));
        $parts = array_values(array_filter([$city, $country]));

        return $parts === [] ? 'Unknown' : implode(', ', $parts);
    }

    private function isPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        ) !== false;
    }

    private function placeName(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '' || strlen($value) > 80 || preg_match('/^[\p{L}\p{N}\s.\'-]+$/u', $value) !== 1) {
            return null;
        }

        return $value;
    }
}
