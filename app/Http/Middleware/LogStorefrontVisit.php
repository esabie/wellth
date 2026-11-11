<?php

namespace App\Http\Middleware;

use App\Models\Order;
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
                // Feedback invite opens should always appear in the log — do not
                // collapse repeats the way ordinary storefront page views do.
                if ($request->route()?->getName() === 'feedback.show') {
                    $this->visits->note($request, $message);
                } else {
                    $this->visits->record($request, $message);
                }
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
            'privacy' => 'Visitor opened the privacy policy.',
            'shop.index' => $this->shopMessage($request),
            'shop.show' => $this->productMessage($request),
            'cart.index' => 'Visitor opened the cart.',
            'checkout.create' => 'Visitor opened checkout.',
            'checkout.payment' => $this->orderMessage($request, 'Visitor opened payment for order '),
            'checkout.confirmation' => $this->orderMessage($request, 'Visitor opened the confirmation for order '),
            'feedback.show' => $this->feedbackMessage($request),
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

    private function feedbackMessage(Request $request): ?string
    {
        $token = $request->route('token');

        if (! is_string($token) || $token === '') {
            return null;
        }

        $order = Order::query()
            ->where('feedback_token', $token)
            ->withExists('feedback as has_feedback')
            ->first(['id', 'uuid']);

        if ($order === null || ! is_string($order->uuid) || $order->uuid === '') {
            return null;
        }

        if ($order->has_feedback) {
            return "Customer opened the feedback page for order {$order->uuid} (already submitted).";
        }

        return "Customer opened the feedback invite link for order {$order->uuid}.";
    }
}
