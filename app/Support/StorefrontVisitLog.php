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
     *     referrer: string,
     *     utm: array<string, string>,
     *     cart?: array{
     *         bottles: int,
     *         subtotal: string,
     *         lines: list<array{name: string, quantity: int}>
     *     }
     * }
     */
    private function context(Request $request, string $visitor): array
    {
        $this->rememberCampaign($request);

        $context = [
            'visitor' => $visitor,
            ...$this->clients->details($request),
            'referrer' => $this->referrer($request),
            'utm' => $this->campaign($request),
        ];

        if ($this->shouldIncludeCart($request)) {
            $context['cart'] = $this->cartSnapshot();
        }

        return $context;
    }

    private function shouldIncludeCart(Request $request): bool
    {
        return in_array($request->route()?->getName(), self::CART_ROUTES, true);
    }

    /**
     * @return array{bottles: int, subtotal: string, lines: list<array{name: string, quantity: int}>}
     */
    private function cartSnapshot(): array
    {
        return [
            'bottles' => $this->cart->count(),
            'subtotal' => $this->cart->subtotal(),
            'lines' => $this->cart->detailedItems()
                ->map(fn (array $item): array => [
                    'name' => $item['product']->name,
                    'quantity' => (int) $item['quantity'],
                ])
                ->values()
                ->all(),
        ];
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

    private function referrer(Request $request): string
    {
        $referer = trim((string) $request->headers->get('referer', ''));

        if ($referer === '') {
            return 'direct';
        }

        $parts = parse_url($referer);

        if (! is_array($parts) || ! isset($parts['host']) || ! is_string($parts['host']) || $parts['host'] === '') {
            return 'direct';
        }

        $host = strtolower($parts['host']);
        $appHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));

        if ($appHost !== '' && ($host === $appHost || str_ends_with($host, '.'.$appHost))) {
            $path = $parts['path'] ?? '/';

            if (! is_string($path) || $path === '') {
                $path = '/';
            }

            return 'site:'.substr($path, 0, 120);
        }

        $path = is_string($parts['path'] ?? null) ? $parts['path'] : '';
        $summary = $host.($path === '' || $path === '/' ? '' : $path);

        return substr($summary, 0, 160);
    }
}
