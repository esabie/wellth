<?php

namespace App\Actions;

use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EnsureOrderFeedbackToken
{
    public const TOKEN_LENGTH = 10;

    public function handle(Order $order): Order
    {
        if (filled($order->feedback_token)) {
            return $order;
        }

        do {
            $token = Str::lower(Str::random(self::TOKEN_LENGTH));
        } while (Order::query()->where('feedback_token', $token)->exists());

        $order->forceFill([
            'feedback_token' => $token,
        ])->save();

        Log::info("Created feedback link for order {$order->uuid}.");

        return $order->refresh();
    }
}
