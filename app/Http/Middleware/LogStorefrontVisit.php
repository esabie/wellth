<?php

namespace App\Http\Middleware;

use App\Support\StorefrontVisitLog;
use Closure;
use Illuminate\Http\Request;
use Inertia\Support\Header;
use Symfony\Component\HttpFoundation\Response;

class LogStorefrontVisit
{
    public function __construct(private StorefrontVisitLog $visits) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldLog($request, $response)) {
            $message = $this->message($request);

            if ($message !== null) {
                $this->visits->record($request, $message);
            }
        }

        return $response;
    }

    private function shouldLog(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET') || $response->getStatusCode() !== Response::HTTP_OK) {
            return false;
        }

        if ($request->prefetch()) {
            return false;
        }

        if ($request->headers->has(Header::PARTIAL_COMPONENT) || $request->headers->has(Header::PARTIAL_ONLY)) {
            return false;
        }

        return $request->route()?->getName() !== null;
    }

    private function message(Request $request): ?string
    {
        $route = $request->route();

        return match ($route?->getName()) {
            'home' => 'Visitor opened the home page.',
            'faqs' => 'Visitor opened the FAQs.',
            'shop.index' => $this->shopMessage($request),
            'shop.show' => $this->productMessage($request),
            'cart.index' => 'Visitor opened the cart.',
            'checkout.create' => 'Visitor opened checkout.',
            'checkout.payment' => $this->orderMessage($request, 'Visitor opened payment for order '),
            'checkout.confirmation' => $this->orderMessage($request, 'Visitor opened the confirmation for order '),
            default => null,
        };
    }

    private function shopMessage(Request $request): string
    {
        $category = $request->string('category')->trim()->toString();

        if ($category !== '' && preg_match('/^[a-z0-9-]{1,80}$/', $category) === 1) {
            return "Visitor opened the shop, filtered to {$category}.";
        }

        return 'Visitor opened the shop.';
    }

    private function productMessage(Request $request): ?string
    {
        $name = $request->route('product')?->name;

        if (! is_string($name) || $name === '') {
            return null;
        }

        return "Visitor opened {$name}.";
    }

    private function orderMessage(Request $request, string $prefix): ?string
    {
        $uuid = $request->route('order')?->uuid;

        if (! is_string($uuid) || $uuid === '') {
            return null;
        }

        return $prefix.$uuid.'.';
    }
}
