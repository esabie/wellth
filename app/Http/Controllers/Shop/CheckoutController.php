<?php

namespace App\Http\Controllers\Shop;

use App\Actions\CreateCheckoutOrder;
use App\Actions\StartPaystackPayment;
use App\Actions\SubmitPaymentProof;
use App\Actions\VerifyPaystackPayment;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApplyPromoCodeRequest;
use App\Http\Requests\PreviewLoyaltyDiscountRequest;
use App\Http\Requests\StoreCheckoutRequest;
use App\Http\Requests\StorePaymentProofRequest;
use App\Models\Order;
use App\Support\Cart;
use App\Support\CheckoutPricing;
use App\Support\GhanaLocations;
use App\Support\LoyaltyPoints;
use App\Support\PromoCodes;
use App\Support\SecondPurchaseDiscount;
use App\Support\StorefrontVisitLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Throwable;

class CheckoutController extends Controller
{
    public function create(
        Cart $cart,
        GhanaLocations $locations,
        CheckoutPricing $pricing,
        SecondPurchaseDiscount $loyalty,
    ): Response|RedirectResponse {
        if ($cart->count() === 0) {
            return redirect()
                ->route('shop.index')
                ->with('error', 'Add something to your cart before checking out.');
        }

        if (! $cart->meetsMinimumOrder()) {
            return redirect()
                ->route('cart.index')
                ->with('error', $cart->minimumOrderErrorMessage());
        }

        $summary = $cart->summary();
        $shippingAmount = number_format((float) config('shop.shipping_amount', 0), 2, '.', '');
        $quote = $pricing->quote($summary['subtotal'], $shippingAmount, $loyalty->previewPhone());

        return Inertia::render('Checkout/Show', [
            'items' => $summary['items'],
            'subtotal' => $summary['subtotal'],
            'shipping_amount' => $shippingAmount,
            'service_fee_amount' => $quote['service_fee_amount'],
            'discount_amount' => $quote['discount_amount'],
            'loyalty_discount_amount' => $quote['loyalty_discount_amount'],
            'loyalty_label' => $quote['loyalty_label'],
            'loyalty_eligible' => $quote['loyalty_eligible'],
            'promo_code' => $quote['promo_code'],
            'promo_label' => $quote['promo_label'],
            'promo_discount_amount' => $quote['promo_discount_amount'],
            'points_enabled' => $quote['points_enabled'],
            'points_redeeming' => $quote['points_redeeming'],
            'points_balance' => $quote['points_balance'],
            'points_balance_value' => $quote['points_balance_value'],
            'points_redeemed' => $quote['points_redeemed'],
            'points_discount_amount' => $quote['points_discount_amount'],
            'points_per_paid_order' => $quote['points_per_paid_order'],
            'point_value_ghs' => $quote['point_value_ghs'],
            'total' => $quote['total'],
            'currency' => config('shop.currency', 'GHS'),
            'locations' => $locations->regions(),
        ]);
    }

    public function previewLoyalty(
        PreviewLoyaltyDiscountRequest $request,
        SecondPurchaseDiscount $loyalty,
    ): RedirectResponse {
        $loyalty->rememberPreviewPhone($request->validated('customer_phone'));

        return redirect()->route('checkout.create');
    }

    public function applyPromo(ApplyPromoCodeRequest $request, PromoCodes $promos, LoyaltyPoints $points): RedirectResponse
    {
        if ($points->isRedeeming()) {
            return redirect()
                ->route('checkout.create')
                ->withErrors(['promo_code' => 'Remove loyalty points redemption before applying a promo code.']);
        }

        try {
            $promos->apply($request->validated('code'));
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('checkout.create')
                ->withErrors(['promo_code' => $exception->getMessage()]);
        }

        return redirect()
            ->route('checkout.create')
            ->with('success', 'Promo code applied.');
    }

    public function removePromo(PromoCodes $promos): RedirectResponse
    {
        $promos->clear();

        return redirect()
            ->route('checkout.create')
            ->with('success', 'Promo code removed.');
    }

    public function applyPoints(LoyaltyPoints $points, PromoCodes $promos): RedirectResponse
    {
        if (! $points->enabled()) {
            return redirect()
                ->route('checkout.create')
                ->withErrors(['points' => 'Loyalty points are not available right now.']);
        }

        $promos->clear();
        $points->enableRedeem();

        return redirect()
            ->route('checkout.create')
            ->with('success', 'Loyalty points will be applied to this order.');
    }

    public function removePoints(LoyaltyPoints $points): RedirectResponse
    {
        $points->clearRedeem();

        return redirect()
            ->route('checkout.create')
            ->with('success', 'Loyalty points removed from this order.');
    }

    public function store(
        StoreCheckoutRequest $request,
        Cart $cart,
        CreateCheckoutOrder $createCheckoutOrder,
        PromoCodes $promos,
        SecondPurchaseDiscount $loyalty,
        LoyaltyPoints $points,
    ): RedirectResponse {
        if ($cart->count() === 0) {
            return redirect()
                ->route('shop.index')
                ->with('error', 'Your cart is empty.');
        }

        if (! $cart->meetsMinimumOrder()) {
            return redirect()
                ->route('cart.index')
                ->with('error', $cart->minimumOrderErrorMessage());
        }

        try {
            $order = $createCheckoutOrder->handle($cart, $request->validated());
        } catch (InvalidArgumentException $exception) {
            Log::notice('checkout.store_failed', [
                'message' => $exception->getMessage(),
            ]);

            return redirect()
                ->route('checkout.create')
                ->with('error', $exception->getMessage());
        }

        $cart->clear();
        $promos->clear();
        $loyalty->clearPreviewPhone();
        $points->clearRedeem();

        return redirect()->route('checkout.payment', $order);
    }

    public function payment(Order $order): Response|RedirectResponse
    {
        if ($order->isPaid()) {
            return redirect()->route('checkout.confirmation', $order);
        }

        abort_unless($order->canAcceptPaymentProof(), 404);

        $order->load('items');

        return Inertia::render('Checkout/Payment', [
            'order' => $this->orderPayload($order),
            'momo' => [
                'number' => config('shop.momo_number'),
                'display_number' => config('shop.momo_display_number'),
                'account_name' => config('shop.momo_account_name'),
                'network' => config('shop.momo_network'),
            ],
        ]);
    }

    public function startPayment(
        Order $order,
        StartPaystackPayment $startPaystackPayment,
    ): RedirectResponse|\Symfony\Component\HttpFoundation\Response {
        if ($order->isPaid()) {
            return redirect()->route('checkout.confirmation', $order);
        }

        abort_unless($order->canConfirmPayment(), 404);

        return $this->redirectToPaystackOrPaymentPage($order, $startPaystackPayment);
    }

    public function paystackCallback(Request $request, VerifyPaystackPayment $verifyPaystackPayment): RedirectResponse
    {
        $reference = $request->query('reference', $request->query('trxref'));

        Log::info('checkout.paystack_callback_received', [
            'reference' => is_string($reference) ? $reference : null,
            'trxref' => $request->query('trxref'),
        ]);

        if (! is_string($reference) || trim($reference) === '') {
            return redirect()
                ->route('shop.index')
                ->with('error', 'We could not find that payment. Please contact support if you were charged.');
        }

        try {
            $order = $verifyPaystackPayment->handle($reference);
        } catch (InvalidArgumentException|Throwable $exception) {
            Log::notice('checkout.paystack_callback_failed', [
                'reference' => $reference,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'previous' => $exception->getPrevious()?->getMessage(),
            ]);

            $order = Order::query()->where('paystack_reference', $reference)->first();

            if ($order === null) {
                return redirect()
                    ->route('shop.index')
                    ->with('error', 'We could not confirm that payment. Please contact support if you were charged.');
            }

            return redirect()
                ->route('checkout.payment', $order)
                ->with('error', $exception->getMessage());
        }

        return redirect()->route('checkout.confirmation', $order);
    }

    public function storePaymentProof(
        StorePaymentProofRequest $request,
        Order $order,
        SubmitPaymentProof $submitPaymentProof,
    ): RedirectResponse {
        abort_unless($order->canAcceptPaymentProof(), 404);

        $proof = $request->file('payment_proof');

        Log::info('checkout.payment_proof_upload_started', [
            'order' => $order->uuid,
            'status' => $order->status->value,
            'original_name' => $proof?->getClientOriginalName(),
            'mime' => $proof?->getClientMimeType(),
            'size' => $proof?->getSize(),
            'upload_error' => $proof?->getError(),
        ]);

        try {
            $submitPaymentProof->handle($order, $proof);
        } catch (InvalidArgumentException $exception) {
            Log::notice('checkout.payment_proof_failed', [
                'order' => $order->uuid,
                'message' => $exception->getMessage(),
                'original_name' => $proof?->getClientOriginalName(),
                'mime' => $proof?->getClientMimeType(),
                'size' => $proof?->getSize(),
            ]);

            return redirect()
                ->route('checkout.payment', $order)
                ->withErrors(['payment_proof' => $exception->getMessage()]);
        }

        app(StorefrontVisitLog::class)->note(
            $request,
            "Visitor uploaded payment proof for order {$order->uuid}.",
        );

        return redirect()
            ->route('checkout.confirmation', $order)
            ->with('payment_proof_received', true);
    }

    public function confirmation(Order $order): Response
    {
        abort_unless($order->isPaid() || $order->isAwaitingConfirmation(), 404);

        $order->load('items');

        return Inertia::render('Checkout/Confirmation', [
            'order' => $this->orderPayload($order),
        ]);
    }

    private function redirectToPaystackOrPaymentPage(
        Order $order,
        StartPaystackPayment $startPaystackPayment,
    ): RedirectResponse|\Symfony\Component\HttpFoundation\Response {
        try {
            $authorizationUrl = $startPaystackPayment->handle($order);
        } catch (Throwable $exception) {
            Log::notice('checkout.paystack_redirect_failed', [
                'order' => $order->uuid,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'previous' => $exception->getPrevious()?->getMessage(),
            ]);

            return redirect()
                ->route('checkout.payment', $order)
                ->with('error', $exception->getMessage());
        }

        // Inertia form posts cannot follow a normal external redirect; this forces a full browser visit.
        return Inertia::location($authorizationUrl);
    }

    /**
     * @return array<string, mixed>
     */
    private function orderPayload(Order $order): array
    {
        return [
            'uuid' => $order->uuid,
            'status' => $order->status->value,
            'customer_name' => $order->customer_name,
            'customer_email' => $order->customer_email,
            'customer_phone' => $order->customer_phone,
            'shipping_line1' => $order->shipping_line1,
            'shipping_line2' => $order->shipping_line2,
            'shipping_city' => $order->shipping_city,
            'shipping_region' => $order->shipping_region,
            'subtotal' => $order->subtotal,
            'shipping_amount' => $order->shipping_amount,
            'service_fee_amount' => $order->service_fee_amount,
            'discount_amount' => $order->discount_amount,
            'loyalty_discount_amount' => $order->loyalty_discount_amount,
            'promo_code' => $order->promo_code,
            'points_redeemed' => $order->points_redeemed,
            'points_discount_amount' => $order->points_discount_amount,
            'points_earned' => $order->points_earned,
            'total' => $order->total,
            'currency' => $order->currency,
            'payment_reference' => $order->paymentReference(),
            'has_payment_proof' => filled($order->payment_proof_path),
            'payment_submitted_at' => $order->payment_submitted_at?->toIso8601String(),
            'paid_at' => $order->paid_at?->toIso8601String(),
            'items' => $order->items->map(fn ($item): array => [
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'line_total' => $item->line_total,
            ]),
        ];
    }
}
