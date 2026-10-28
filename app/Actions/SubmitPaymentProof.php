<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Support\ShopAdminNotifier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class SubmitPaymentProof
{
    public function handle(Order $order, UploadedFile $proof): Order
    {
        if (! $order->canAcceptPaymentProof()) {
            Log::warning('checkout.payment_proof_rejected', [
                'order' => $order->uuid,
                'status' => $order->status->value,
                'reason' => 'order_not_accepting_proof',
            ]);

            throw new InvalidArgumentException('This order can no longer accept payment proof.');
        }

        $path = $proof->store('payment-proofs', 'local');

        if ($path === false) {
            Log::error('checkout.payment_proof_store_failed', [
                'order' => $order->uuid,
                'original_name' => $proof->getClientOriginalName(),
                'mime' => $proof->getMimeType(),
                'size' => $proof->getSize(),
            ]);

            throw new InvalidArgumentException('We could not save your payment screenshot. Please try again.');
        }

        $previousPath = $order->payment_proof_path;

        $order = DB::transaction(function () use ($order, $path): Order {
            $order->update([
                'status' => OrderStatus::AwaitingConfirmation,
                'payment_proof_path' => $path,
                'payment_submitted_at' => now(),
            ]);

            return $order->refresh()->load('items');
        });

        if (filled($previousPath) && $previousPath !== $path) {
            Storage::disk('local')->delete($previousPath);
        }

        Log::info('checkout.payment_proof_submitted', [
            'order' => $order->uuid,
            'status' => $order->status->value,
            'path' => $path,
            'replaced_previous' => filled($previousPath) && $previousPath !== $path,
            'phone' => $order->customer_phone_normalized,
            'total' => $order->total,
        ]);

        app(ShopAdminNotifier::class)->paymentProofSubmitted($order);

        return $order;
    }
}
