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
            Log::warning('feedback.submit_rejected', [
                'order' => $order->uuid,
                'status' => $order->status->value,
                'reason' => $order->feedback()->exists() ? 'already_submitted' : 'order_not_accepting',
            ]);

            throw new InvalidArgumentException('This order can no longer accept feedback.');
        }

        $allowedProductIds = $this->choices->allowedProductIds($order)->all();

        if (! in_array((int) $data['product_id'], $allowedProductIds, true)) {
            Log::warning('feedback.submit_rejected', [
                'order' => $order->uuid,
                'product_id' => $data['product_id'],
                'reason' => 'product_not_on_order',
            ]);

            throw new InvalidArgumentException('Please choose a blend from this order.');
        }

        $tags = array_values(array_unique($data['improvement_tags']));

        if (in_array('all_good', $tags, true) && count($tags) > 1) {
            $tags = array_values(array_filter($tags, fn (string $tag): bool => $tag !== 'all_good'));
        }

        $allowedTags = array_keys(FeedbackFormOptions::improvementTags());
        $tags = array_values(array_filter($tags, fn (string $tag): bool => in_array($tag, $allowedTags, true)));

        if ($tags === []) {
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

        Log::info('feedback.submitted', [
            'order' => $order->uuid,
            'product_id' => $feedback->product_id,
            'shopping_rating' => $feedback->shopping_rating,
            'improvement_tags' => $feedback->improvement_tags,
            'feedback_id' => $feedback->id,
        ]);

        app(ShopAdminNotifier::class)->feedbackSubmitted($feedback);

        return $feedback;
    }
}
