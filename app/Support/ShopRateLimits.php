<?php

namespace App\Support;

use App\Http\Responses\ShopThrottleResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ShopRateLimits
{
    public static function register(): void
    {
        self::define('shop-cart', 'cart');
        self::define('shop-checkout', 'checkout');
        self::define('shop-promo', 'promo');
        self::define('shop-loyalty', 'loyalty');
        self::define('shop-points', 'points');
        self::define('shop-payment', 'payment');
    }

    private static function define(string $name, string $configKey): void
    {
        RateLimiter::for($name, function (Request $request) use ($configKey): Limit {
            $maxAttempts = max(1, (int) config("shop.rate_limits.{$configKey}", 60));

            return Limit::perMinute($maxAttempts)
                ->by($request->ip())
                ->response(fn (Request $request, array $headers): Response => ShopThrottleResponse::make($request, $headers));
        });
    }
}
