<?php

namespace App\Actions;

use App\Models\Order;
use App\Support\PaystackClient;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

class VerifyPaystackPayment
{
    public function __construct(
        private PaystackClient $paystack,
        private CompletePaidOrder $completePaidOrder,
    ) {}

    public function handle(string $reference): Order
    {
        $reference = trim($reference);

        if ($reference === '') {
            throw new InvalidArgumentException('Missing payment reference.');
        }

        $order = Order::query()
            ->where('paystack_reference', $reference)
            ->first();

        if ($order === null) {
            throw new InvalidArgumentException('No order matches that payment reference.');
        }

        if ($order->isPaid()) {
            Log::info('checkout.paystack_verify_skipped', [
                'order' => $order->uuid,
                'reference' => $reference,
                'reason' => 'already_paid',
            ]);

            return $order;
        }

        if (! $order->canConfirmPayment()) {
            throw new InvalidArgumentException('This order cannot accept payment right now.');
        }

        try {
            $result = $this->paystack->verify($reference);
        } catch (RuntimeException $exception) {
            Log::error('checkout.paystack_verify_failed', [
                'order' => $order->uuid,
                'reference' => $reference,
                'message' => $exception->getMessage(),
            ]);

            throw new RuntimeException(
                'We could not verify your payment. Please try again.',
                previous: $exception,
            );
        }

        if (! $result['status']) {
            Log::notice('checkout.paystack_verify_unsuccessful', [
                'order' => $order->uuid,
                'reference' => $reference,
            ]);

            throw new InvalidArgumentException('Payment was not successful. You can try again.');
        }

        $expectedAmount = (int) round((float) $order->total * 100);

        if ($result['amount'] !== $expectedAmount) {
            Log::warning('checkout.paystack_amount_mismatch', [
                'order' => $order->uuid,
                'reference' => $reference,
                'expected' => $expectedAmount,
                'actual' => $result['amount'],
            ]);

            throw new InvalidArgumentException('Payment amount does not match the order total.');
        }

        if (strcasecmp($result['currency'], (string) $order->currency) !== 0) {
            Log::warning('checkout.paystack_currency_mismatch', [
                'order' => $order->uuid,
                'reference' => $reference,
                'expected' => $order->currency,
                'actual' => $result['currency'],
            ]);

            throw new InvalidArgumentException('Payment currency does not match the order.');
        }

        Log::info('checkout.paystack_verified', [
            'order' => $order->uuid,
            'reference' => $reference,
            'amount' => $result['amount'],
            'currency' => $result['currency'],
            'paid_at' => $result['paid_at'],
        ]);

        return $this->completePaidOrder->handle($order, $result['paid_at']);
    }
}
