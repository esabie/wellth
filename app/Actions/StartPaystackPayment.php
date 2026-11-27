<?php

namespace App\Actions;

use App\Models\Order;
use App\Support\PaystackClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class StartPaystackPayment
{
    public function __construct(private PaystackClient $paystack) {}

    /**
     * Initialize Paystack Checkout and return the hosted authorization URL.
     */
    public function handle(Order $order): string
    {
        if ($order->isPaid()) {
            throw new InvalidArgumentException('This order is already paid.');
        }

        if (! $order->canConfirmPayment()) {
            throw new InvalidArgumentException('This order cannot accept payment right now.');
        }

        $email = filled($order->customer_email)
            ? (string) $order->customer_email
            : strtolower($order->uuid).'@customers.wellth.app';

        $amount = (int) round((float) $order->total * 100);
        $reference = $this->freshReference($order);

        $order->update([
            'paystack_reference' => $reference,
            'paystack_access_code' => null,
        ]);

        try {
            $result = $this->paystack->initialize([
                'email' => 'admin@wellthgh.com',
                'amount' => $amount,
                'currency' => (string) $order->currency,
                'reference' => $reference,
                'callback_url' => route('checkout.paystack.callback'),
                'metadata' => [
                    'order_uuid' => $order->uuid,
                    'customer_phone' => $order->customer_phone,
                ],
            ]);
        } catch (Throwable $exception) {
            Log::error('checkout.paystack_initialize_failed', [
                'order' => $order->uuid,
                'reference' => $reference,
                'amount' => $amount,
                'currency' => $order->currency,
                'callback_url' => route('checkout.paystack.callback'),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'previous' => $exception->getPrevious()?->getMessage(),
            ]);

            throw new RuntimeException(
                'We could not start Paystack checkout. Please try again.',
                previous: $exception,
            );
        }

        $order->update([
            'paystack_access_code' => $result['access_code'],
            'paystack_reference' => $result['reference'],
        ]);

        Log::info('checkout.paystack_initialized', [
            'order' => $order->uuid,
            'reference' => $result['reference'],
            'amount' => $amount,
            'currency' => $order->currency,
        ]);

        return $result['authorization_url'];
    }

    /**
     * Paystack rejects reused references, so every initialize attempt gets a new one.
     */
    private function freshReference(Order $order): string
    {
        do {
            $reference = $order->uuid.'-'.Str::upper(Str::random(8));
        } while (Order::query()->where('paystack_reference', $reference)->exists());

        return $reference;
    }
}
