<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Delivery fee (GHS) — currently free
    |--------------------------------------------------------------------------
    */
    'shipping_amount' => (float) env('SHOP_SHIPPING_AMOUNT', 0),

    'currency' => env('SHOP_CURRENCY', 'GHS'),

    /*
    |--------------------------------------------------------------------------
    | Minimum order quantity (shots / bottles)
    |--------------------------------------------------------------------------
    |
    | Guests must have at least this many bottles in the cart before checkout.
    | Formulas can be mixed — the total cart quantity is what counts.
    |
    */
    'minimum_order_quantity' => (int) env('SHOP_MINIMUM_ORDER_QUANTITY', 10),

    /*
    |--------------------------------------------------------------------------
    | Admin notifications
    |--------------------------------------------------------------------------
    |
    | Receives alerts when a customer places an order, uploads payment proof,
    | and when an order is marked paid. Leave blank to skip that channel.
    |
    */
    'admin_notification_email' => env('SHOP_ADMIN_NOTIFICATION_EMAIL'),
    'admin_notification_phone' => env('SHOP_ADMIN_NOTIFICATION_PHONE'),

    /*
    |--------------------------------------------------------------------------
    | Manual Mobile Money payment
    |--------------------------------------------------------------------------
    */
    'momo_number' => env('SHOP_MOMO_NUMBER', '233545365434'),
    'momo_display_number' => env('SHOP_MOMO_DISPLAY_NUMBER', '+233 54 536 5434'),
    'momo_account_name' => env('SHOP_MOMO_ACCOUNT_NAME', 'Wellth'),
    'momo_network' => env('SHOP_MOMO_NETWORK', 'MTN MoMo'),

    /*
    |--------------------------------------------------------------------------
    | Second-purchase loyalty discount
    |--------------------------------------------------------------------------
    |
    | Exactly the second paid order for a phone number gets this percent off
    | merchandise subtotal. Pending/abandoned checkouts do not count.
    |
    */
    'second_purchase_discount_enabled' => (bool) env('SHOP_SECOND_PURCHASE_DISCOUNT_ENABLED', true),
    'second_purchase_discount_percent' => (float) env('SHOP_SECOND_PURCHASE_DISCOUNT_PERCENT', 5),
    'second_purchase_discount_label' => env('SHOP_SECOND_PURCHASE_DISCOUNT_LABEL', 'Second purchase 5% off'),

    /*
    |--------------------------------------------------------------------------
    | Loyalty points
    |--------------------------------------------------------------------------
    |
    | Flat points credited when an order is marked paid. Redeeming points at
    | checkout blocks second-purchase % and promo codes on that order.
    |
    */
    'points_enabled' => (bool) env('SHOP_POINTS_ENABLED', false),
    'points_per_paid_order' => (int) env('SHOP_POINTS_PER_PAID_ORDER', 10),
    'point_value_ghs' => (float) env('SHOP_POINT_VALUE_GHS', 0.10),

    /*
    |--------------------------------------------------------------------------
    | Referral promo rewards
    |--------------------------------------------------------------------------
    |
    | Flat points credited to a promo code's assigned referrer phone when an
    | order using that code is marked paid. Self-referral is blocked.
    |
    */
    'referral_points_per_order' => (int) env('SHOP_REFERRAL_POINTS_PER_ORDER', 10),

    /*
    |--------------------------------------------------------------------------
    | Abandoned cart lifetime (minutes)
    |--------------------------------------------------------------------------
    |
    | Guest carts clear automatically on the next request after this many minutes
    | without cart activity, if checkout/payment was never completed.
    |
    */
    'cart_ttl_minutes' => (int) env('SHOP_CART_TTL_MINUTES', 20),

];
