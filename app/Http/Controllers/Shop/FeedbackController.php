<?php

namespace App\Http\Controllers\Shop;

use App\Actions\SubmitOrderFeedback;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFeedbackRequest;
use App\Models\Order;
use App\Support\FeedbackFormOptions;
use App\Support\OrderFeedbackChoices;
use App\Support\StorefrontVisitLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class FeedbackController extends Controller
{
    public function show(string $token, OrderFeedbackChoices $choices): Response
    {
        $order = $this->orderForToken($token);

        return Inertia::render('Feedback/Show', $this->pagePayload($order, $choices));
    }

    public function store(
        StoreFeedbackRequest $request,
        string $token,
        SubmitOrderFeedback $submitOrderFeedback,
    ): RedirectResponse {
        $order = $this->orderForToken($token);

        try {
            $feedback = $submitOrderFeedback->handle($order, $request->validated());
        } catch (InvalidArgumentException $exception) {
            Log::notice("Feedback for order {$order->uuid} was not saved: {$exception->getMessage()}");

            return redirect()
                ->route('feedback.show', $token)
                ->withErrors(['feedback' => $exception->getMessage()]);
        }

        $summary = collect([
            $feedback->product?->name ? 'favourite '.$feedback->product->name : null,
            'shopping '.$feedback->shoppingLabel(),
            $feedback->improvementLabels() !== [] ? implode(' · ', $feedback->improvementLabels()) : null,
            filled($feedback->note) ? 'with a note' : null,
        ])->filter()->implode(' · ');

        app(StorefrontVisitLog::class)->note(
            $request,
            "Visitor submitted feedback for order {$order->uuid}".($summary !== '' ? ": {$summary}." : '.'),
        );

        return redirect()
            ->route('feedback.show', $token)
            ->with('feedback_received', true);
    }

    private function orderForToken(string $token): Order
    {
        return Order::query()
            ->where('feedback_token', $token)
            ->where('status', OrderStatus::Delivered)
            ->with(['items.product', 'feedback.product'])
            ->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function pagePayload(Order $order, OrderFeedbackChoices $choices): array
    {
        $products = $choices->forOrder($order);
        $includesBouquet = collect($products)->contains(fn (array $product): bool => $product['from_bouquet'] === true);

        $feedback = $order->feedback;
        $firstName = str($order->customer_name)->trim()->before(' ')->toString() ?: 'there';

        return [
            'token' => (string) $order->feedback_token,
            'order' => [
                'uuid' => $order->uuid,
                'customer_name' => $order->customer_name,
                'first_name' => $firstName,
            ],
            'products' => $products,
            'includes_bouquet' => $includesBouquet,
            'shopping_ratings' => collect(FeedbackFormOptions::shoppingRatings())
                ->map(fn (string $label, int $value): array => [
                    'value' => $value,
                    'label' => $label,
                ])
                ->values()
                ->all(),
            'improvement_tags' => collect(FeedbackFormOptions::improvementTags())
                ->map(fn (string $label, string $value): array => [
                    'value' => $value,
                    'label' => $label,
                ])
                ->values()
                ->all(),
            'submitted' => $feedback !== null,
            'feedback' => $feedback === null ? null : [
                'favorite_product' => $feedback->product?->name,
                'shopping_rating' => $feedback->shopping_rating,
                'shopping_label' => $feedback->shoppingLabel(),
                'improvement_labels' => $feedback->improvementLabels(),
                'note' => $feedback->note,
            ],
        ];
    }
}
