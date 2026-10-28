<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Enums\PointsRedeemStatus;
use App\Models\Order;
use App\Support\Cart;
use App\Support\CheckoutPricing;
use App\Support\LoyaltyPoints;
use App\Support\PhoneNumber;
use App\Support\PromoCodes;
use App\Support\ShopAdminNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class CreateCheckoutOrder
{
    /**
     * @param  array{
     *     customer_name: string,
     *     customer_email: string,
     *     customer_phone: string,
     *     shipping_line1: string,
     *     shipping_line2?: string|null,
     *     shipping_city: string,
     *     shipping_region?: string|null,
     *     shipping_notes?: string|null
     * }  $customer
     */
    public function handle(Cart $cart, array $customer): Order
    {
        $items = $cart->detailedItems();

        if ($items->isEmpty()) {
            Log::warning('checkout.order_rejected', ['reason' => 'empty_cart']);

            throw new InvalidArgumentException('Your cart is empty.');
        }

        if (! $cart->meetsMinimumOrder()) {
            $minimum = $cart->minimumOrderQuantity();
            $remaining = $cart->remainingForMinimumOrder();

            Log::warning('checkout.order_rejected', [
                'reason' => 'below_minimum',
                'cart_count' => $cart->count(),
                'minimum' => $minimum,
            ]);

            throw new InvalidArgumentException(
                "Minimum order is {$minimum} shots. Add {$remaining} more to continue to checkout.",
            );
        }

        $promos = app(PromoCodes::class);
        $appliedPromo = $promos->applied();

        if ($appliedPromo !== null) {
            try {
                $promos->assertNotSelfReferral($appliedPromo, $customer['customer_phone']);
            } catch (InvalidArgumentException $exception) {
                Log::warning('checkout.order_rejected', [
                    'reason' => 'self_referral',
                    'promo_code' => $appliedPromo->code,
                    'phone' => PhoneNumber::normalize($customer['customer_phone']),
                ]);

                throw $exception;
            }
        }

        $subtotal = $cart->subtotal();
        $shippingAmount = number_format((float) config('shop.shipping_amount', 0), 2, '.', '');
        $pricing = app(CheckoutPricing::class)->quote(
            $subtotal,
            $shippingAmount,
            $customer['customer_phone'],
        );

        $order = DB::transaction(function () use ($items, $customer, $subtotal, $shippingAmount, $pricing): Order {
            $order = Order::query()->create([
                'status' => OrderStatus::Pending,
                'customer_name' => $customer['customer_name'],
                'customer_email' => $customer['customer_email'],
                'customer_phone' => $customer['customer_phone'],
                'customer_phone_normalized' => PhoneNumber::normalize($customer['customer_phone']),
                'shipping_line1' => $customer['shipping_line1'],
                'shipping_line2' => $customer['shipping_line2'] ?? null,
                'shipping_city' => $customer['shipping_city'],
                'shipping_region' => $customer['shipping_region'] ?? null,
                'shipping_notes' => $customer['shipping_notes'] ?? null,
                'subtotal' => $subtotal,
                'shipping_amount' => $shippingAmount,
                'discount_amount' => $pricing['discount_amount'],
                'loyalty_discount_amount' => $pricing['loyalty_discount_amount'],
                'promo_code' => $pricing['promo_code'],
                'points_redeemed' => $pricing['points_redeemed'],
                'points_discount_amount' => $pricing['points_discount_amount'],
                'points_earned' => null,
                'points_redeem_status' => PointsRedeemStatus::None,
                'total' => $pricing['total'],
                'currency' => (string) config('shop.currency', 'GHS'),
            ]);

            foreach ($items as $item) {
                $order->items()->create([
                    'product_id' => $item['product']->id,
                    'product_name' => $item['product']->name,
                    'product_sku' => $item['product']->sku,
                    'product_slug' => $item['product']->slug,
                    'unit_price' => $item['product']->price,
                    'quantity' => $item['quantity'],
                    'line_total' => $item['line_total'],
                ]);
            }

            app(LoyaltyPoints::class)->reserveForOrder($order->refresh());

            return $order->refresh()->load('items');
        });

        Log::info('checkout.order_placed', [
            'order' => $order->uuid,
            'status' => $order->status->value,
            'total' => $order->total,
            'currency' => $order->currency,
            'item_count' => $order->items->sum('quantity'),
            'promo_code' => $order->promo_code,
            'loyalty_discount' => $order->loyalty_discount_amount,
            'points_redeemed' => $order->points_redeemed,
            'points_discount' => $order->points_discount_amount,
            'phone' => $order->customer_phone_normalized,
            'email' => $order->customer_email,
        ]);

        app(ShopAdminNotifier::class)->orderPlaced($order);

        return $order;
    }
}
