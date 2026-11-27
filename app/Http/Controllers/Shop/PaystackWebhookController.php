<?php

namespace App\Http\Controllers\Shop;

use App\Actions\VerifyPaystackPayment;
use App\Http\Controllers\Controller;
use App\Support\PaystackClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class PaystackWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        PaystackClient $paystack,
        VerifyPaystackPayment $verifyPaystackPayment,
    ): Response {
        $payload = $request->getContent();
        $data = $request->input('data');
        $transaction = $paystack->transactionContext(is_array($data) ? $data : []);

        Log::info('checkout.paystack_webhook_received', [
            'event' => $request->input('event'),
            'signature_present' => filled($request->header('x-paystack-signature')),
            'payload_bytes' => strlen($payload),
            ...$transaction,
        ]);

        if (! $paystack->isValidWebhookSignature($payload, $request->header('x-paystack-signature'))) {
            Log::warning('checkout.paystack_webhook_rejected', [
                'reason' => 'invalid_signature',
                'event' => $request->input('event'),
                'reference' => $request->input('data.reference'),
                'signature_present' => filled($request->header('x-paystack-signature')),
            ]);

            return response('Invalid signature', 401);
        }

        $event = (string) $request->input('event');

        if ($event !== 'charge.success') {
            $declined = $event === 'charge.failed' || ($transaction['transaction_status'] ?? null) === 'failed';

            Log::log($declined ? 'warning' : 'info', 'checkout.paystack_webhook_ignored', [
                'event' => $event,
                'reason' => 'unhandled_event',
                ...$transaction,
            ]);

            return response()->json(['status' => 'ignored']);
        }

        $reference = (string) $request->input('data.reference', '');

        if ($reference === '') {
            Log::warning('checkout.paystack_webhook_rejected', [
                'reason' => 'missing_reference',
                'event' => $event,
            ]);

            return response()->json(['status' => 'missing_reference'], 422);
        }

        try {
            $order = $verifyPaystackPayment->handle($reference);
        } catch (InvalidArgumentException $exception) {
            Log::notice('checkout.paystack_webhook_ignored', [
                'reference' => $reference,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'status' => 'ignored',
                'message' => $exception->getMessage(),
            ]);
        } catch (Throwable $exception) {
            Log::error('checkout.paystack_webhook_failed', [
                'reference' => $reference,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'previous' => $exception->getPrevious()?->getMessage(),
            ]);

            return response()->json(['status' => 'failed'], 500);
        }

        Log::info('checkout.paystack_webhook_processed', [
            'order' => $order->uuid,
            'reference' => $reference,
            'status' => $order->status->value,
        ]);

        return response()->json(['status' => 'ok']);
    }
}
