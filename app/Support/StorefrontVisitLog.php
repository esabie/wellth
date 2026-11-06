<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class StorefrontVisitLog
{
    /**
     * Repeat views of the same page by the same browser are collapsed for this long.
     */
    private const REPEAT_WINDOW_MINUTES = 30;

    private const UTM_SESSION_KEY = 'storefront_utm';

    /**
     * @var list<string>
     */
    private const UTM_KEYS = [
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
    ];

    /**
     * @var list<string>
     */
    private const CART_ROUTES = [
        'cart.index',
        'checkout.create',
        'checkout.payment',
        'checkout.confirmation',
    ];

    /**
     * @var array<string, string>
     */
    private const REFERRER_LABELS = [
        'instagram.com' => 'Instagram',
        'www.instagram.com' => 'Instagram',
        'l.instagram.com' => 'Instagram',
        'google.com' => 'Google',
        'www.google.com' => 'Google',
        'google.com.gh' => 'Google',
        'www.google.com.gh' => 'Google',
        'facebook.com' => 'Facebook',
        'www.facebook.com' => 'Facebook',
        'm.facebook.com' => 'Facebook',
        'l.facebook.com' => 'Facebook',
        'wa.me' => 'WhatsApp',
        'api.whatsapp.com' => 'WhatsApp',
        'web.whatsapp.com' => 'WhatsApp',
        't.co' => 'X',
        'twitter.com' => 'X',
        'x.com' => 'X',
        'tiktok.com' => 'TikTok',
        'www.tiktok.com' => 'TikTok',
    ];

    public function __construct(
        private VisitorClient $clients,
        private Cart $cart,
    ) {}

    public function record(Request $request, string $message): void
    {
        $visitor = $this->visitor($request);
        $key = 'storefront-visit:'.$visitor.':'.hash('sha256', $message);

        if (! Cache::add($key, true, now()->addMinutes(self::REPEAT_WINDOW_MINUTES))) {
            return;
        }

        Log::info($message, $this->context($request, $visitor));
    }

    public function note(Request $request, string $message): void
    {
        Log::info($message, $this->context($request, $this->visitor($request)));
    }

    public function visitor(Request $request): string
    {
        $sessionId = $request->hasSession() ? $request->session()->getId() : 'guest';

        return substr(hash('sha256', $sessionId), 0, 8);
    }

    /**
     * @return array{
     *     visitor: string,
     *     ip: string,
     *     device: string,
     *     browser: string,
     *     location: string,
     *     from?: string,
     *     campaign?: string,
     *     cart?: string
     * }
     */
    private function context(Request $request, string $visitor): array
    {
        $this->rememberCampaign($request);

        $context = [
            'visitor' => $visitor,
            ...$this->clients->details($request),
        ];

        $from = $this->referrer($request);

        if ($from !== null) {
            $context['from'] = $from;
        }

        $campaign = $this->formatCampaign($this->campaign($request));

        if ($campaign !== null) {
            $context['campaign'] = $campaign;
        }

        if ($this->shouldIncludeCart($request)) {
            $context['cart'] = $this->formatCart();
        }

        return $context;
    }

    private function shouldIncludeCart(Request $request): bool
    {
        return in_array($request->route()?->getName(), self::CART_ROUTES, true);
    }

    private function formatCart(): string
    {
        $lines = $this->cart->detailedItems()
            ->map(fn (array $item): string => $item['product']->name.' × '.(int) $item['quantity'])
            ->values()
            ->all();

        $bottles = $this->cart->count();
        $subtotal = $this->cart->subtotal();

        if ($lines === []) {
            return 'empty';
        }

        return $bottles.' bottles · '.implode(', ', $lines).' · GH₵'.$subtotal;
    }

    private function rememberCampaign(Request $request): void
    {
        if (! $request->hasSession()) {
            return;
        }

        $utm = $this->utmFromQuery($request);

        if ($utm === []) {
            return;
        }

        $request->session()->put(self::UTM_SESSION_KEY, $utm);
    }

    /**
     * @return array<string, string>
     */
    private function campaign(Request $request): array
    {
        if ($request->hasSession()) {
            $stored = $request->session()->get(self::UTM_SESSION_KEY);

            if (is_array($stored) && $stored !== []) {
                return $this->sanitizeUtm($stored);
            }
        }

        return $this->utmFromQuery($request);
    }

    /**
     * @param  array<string, string>  $utm
     */
    private function formatCampaign(array $utm): ?string
    {
        if ($utm === []) {
            return null;
        }

        $parts = [];

        foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'] as $key) {
            if (isset($utm[$key])) {
                $parts[] = $utm[$key];
            }
        }

        return $parts === [] ? null : implode(' · ', $parts);
    }

    /**
     * @return array<string, string>
     */
    private function utmFromQuery(Request $request): array
    {
        $utm = [];

        foreach (self::UTM_KEYS as $key) {
            $value = $request->query($key);

            if (! is_string($value)) {
                continue;
            }

            $clean = $this->utmValue($value);

            if ($clean !== null) {
                $utm[$key] = $clean;
            }
        }

        return $utm;
    }

    /**
     * @param  array<mixed>  $utm
     * @return array<string, string>
     */
    private function sanitizeUtm(array $utm): array
    {
        $clean = [];

        foreach (self::UTM_KEYS as $key) {
            if (! isset($utm[$key]) || ! is_string($utm[$key])) {
                continue;
            }

            $value = $this->utmValue($utm[$key]);

            if ($value !== null) {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }

    private function utmValue(string $value): ?string
    {
        $value = trim($value);

        if ($value === '' || strlen($value) > 80) {
            return null;
        }

        if (preg_match('/^[\p{L}\p{N}\s._\-]+$/u', $value) !== 1) {
            return null;
        }

        return $value;
    }

    private function referrer(Request $request): ?string
    {
        $referer = trim((string) $request->headers->get('referer', ''));

        if ($referer === '') {
            return null;
        }

        $parts = parse_url($referer);

        if (! is_array($parts) || ! isset($parts['host']) || ! is_string($parts['host']) || $parts['host'] === '') {
            return null;
        }

        $host = strtolower($parts['host']);

        if ($this->isOwnHost($host)) {
            $path = $parts['path'] ?? '/';

            if (! is_string($path) || $path === '') {
                $path = '/';
            }

            return 'site '.substr($path, 0, 120);
        }

        if (isset(self::REFERRER_LABELS[$host])) {
            return self::REFERRER_LABELS[$host];
        }

        foreach (self::REFERRER_LABELS as $knownHost => $label) {
            if (str_ends_with($host, '.'.$knownHost)) {
                return $label;
            }
        }

        $path = is_string($parts['path'] ?? null) ? $parts['path'] : '';
        $summary = $host.($path === '' || $path === '/' ? '' : $path);

        return substr($summary, 0, 160);
    }

    private function isOwnHost(string $host): bool
    {
        $appHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));

        if ($appHost !== '' && ($host === $appHost || str_ends_with($host, '.'.$appHost))) {
            return true;
        }

        $localHosts = ['localhost', '127.0.0.1', '::1'];

        return in_array($host, $localHosts, true) && in_array($appHost, $localHosts, true);
    }
}
