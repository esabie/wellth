<?php

namespace App\Actions;

use App\Models\Feedback;
use App\Models\Order;
use App\Support\FeedbackFormOptions;
use App\Support\OrderFeedbackChoices;
use App\Support\ShopAdminNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class SubmitOrderFeedback
{
    public function __construct(private OrderFeedbackChoices $choices) {}

    /**
     * @param  array{product_id: int, shopping_rating: int, improvement_tags: list<string>, note?: string|null}  $data
     */
    public function handle(Order $order, array $data): Feedback
    {
        if (! $order->canAcceptFeedback()) {
            $reason = $order->feedback()->exists()
                ? 'feedback was already submitted'
                : 'the order is not accepting feedback';

            Log::warning("Skipped feedback for order {$order->uuid}: {$reason}.");

            throw new InvalidArgumentException('This order can no longer accept feedback.');
        }

        $allowedProductIds = $this->choices->allowedProductIds($order)->all();

        if (! in_array((int) $data['product_id'], $allowedProductIds, true)) {
            Log::warning("Skipped feedback for order {$order->uuid}: chosen blend is not on this order.");

            throw new InvalidArgumentException('Please choose a blend from this order.');
        }

        $tags = array_values(array_unique($data['improvement_tags']));

        if (in_array('all_good', $tags, true) && count($tags) > 1) {
            $tags = array_values(array_filter($tags, fn (string $tag): bool => $tag !== 'all_good'));
        }

        $allowedTags = array_keys(FeedbackFormOptions::improvementTags());
        $tags = array_values(array_filter($tags, fn (string $tag): bool => in_array($tag, $allowedTags, true)));

        if ($tags === []) {
            Log::warning("Skipped feedback for order {$order->uuid}: no improvement chips selected.");

            throw new InvalidArgumentException('Please tap anything we could improve, or “All good”.');
        }

        $feedback = DB::transaction(function () use ($order, $data, $tags): Feedback {
            return Feedback::query()->create([
                'order_id' => $order->id,
                'product_id' => (int) $data['product_id'],
                'shopping_rating' => (int) $data['shopping_rating'],
                'improvement_tags' => $tags,
                'note' => filled($data['note'] ?? null) ? trim((string) $data['note']) : null,
            ]);
        });

        $feedback->load(['order', 'product']);

        $parts = [
            'favourite '.($feedback->product?->name ?? 'unknown blend'),
            'shopping '.$feedback->shoppingLabel(),
            implode(' · ', $feedback->improvementLabels()),
        ];

        if (filled($feedback->note)) {
            $parts[] = 'with a note';
        }

        Log::info('Saved feedback for order '.$order->uuid.': '.implode(' · ', $parts).'.');

        app(ShopAdminNotifier::class)->feedbackSubmitted($feedback);

        return $feedback;
    }
}
