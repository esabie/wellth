<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PointsRedeemStatus;
use App\Support\PhoneNumber;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'status',
    'customer_name',
    'customer_email',
    'customer_phone',
    'customer_phone_normalized',
    'shipping_line1',
    'shipping_line2',
    'shipping_city',
    'shipping_region',
    'shipping_notes',
    'subtotal',
    'shipping_amount',
    'discount_amount',
    'loyalty_discount_amount',
    'promo_code',
    'points_redeemed',
    'points_discount_amount',
    'points_earned',
    'points_redeem_status',
    'total',
    'currency',
    'paystack_reference',
    'paystack_access_code',
    'payment_proof_path',
    'payment_submitted_at',
    'paid_at',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            if (blank($order->uuid)) {
                $order->uuid = static::generateOrderNumber();
            }

            if (blank($order->paystack_reference)) {
                $order->paystack_reference = $order->uuid;
            }

            if (filled($order->customer_phone) && blank($order->customer_phone_normalized)) {
                $order->customer_phone_normalized = PhoneNumber::normalize((string) $order->customer_phone);
            }
        });

        static::updating(function (Order $order): void {
            if ($order->isDirty('customer_phone')) {
                $order->customer_phone_normalized = PhoneNumber::normalize((string) $order->customer_phone);
            }
        });
    }

    /**
     * Short public order number, e.g. WLK7M2P9QX.
     */
    public static function generateOrderNumber(): string
    {
        do {
            $number = 'WL'.Str::upper(Str::random(8));
        } while (
            static::query()
                ->where('uuid', $number)
                ->orWhere('paystack_reference', $number)
                ->exists()
        );

        return $number;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal' => 'decimal:2',
            'shipping_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'loyalty_discount_amount' => 'decimal:2',
            'points_redeemed' => 'integer',
            'points_discount_amount' => 'decimal:2',
            'points_earned' => 'integer',
            'points_redeem_status' => PointsRedeemStatus::class,
            'total' => 'decimal:2',
            'payment_submitted_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function isPaid(): bool
    {
        return in_array($this->status, [
            OrderStatus::Paid,
            OrderStatus::Preparing,
            OrderStatus::Shipped,
        ], true);
    }

    public function isAwaitingConfirmation(): bool
    {
        return $this->status === OrderStatus::AwaitingConfirmation;
    }

    public function canAcceptPaymentProof(): bool
    {
        return in_array($this->status, [
            OrderStatus::Pending,
            OrderStatus::AwaitingConfirmation,
        ], true);
    }

    public function paymentReference(): string
    {
        return $this->paystack_reference;
    }
}
