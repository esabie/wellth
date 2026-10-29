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

    public function __construct(private VisitorClient $clients) {}

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
     * @return array{visitor: string, ip: string, device: string, browser: string, location: string}
     */
    private function context(Request $request, string $visitor): array
    {
        return [
            'visitor' => $visitor,
            ...$this->clients->details($request),
        ];
    }
}
