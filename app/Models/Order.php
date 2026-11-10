<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PointsRedeemStatus;
use App\Support\PhoneNumber;
use Carbon\CarbonInterface;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

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
    'prepared_at',
    'shipped_at',
    'delivered_at',
    'feedback_token',
    'feedback_invited_at',
    'feedback_auto_invite_on',
    'stock_decremented_at',
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
     * Public order number, e.g. WL261101001 for the first order on 1 Nov 2026.
     */
    public static function generateOrderNumber(): string
    {
        $date = now()->format('ymd');

        $serial = DB::transaction(function () use ($date): int {
            DB::table('order_number_sequences')->insertOrIgnore([
                'order_date' => $date,
                'last_serial' => 0,
            ]);

            $lastSerial = (int) DB::table('order_number_sequences')
                ->where('order_date', $date)
                ->lockForUpdate()
                ->value('last_serial');

            $nextSerial = $lastSerial + 1;

            DB::table('order_number_sequences')
                ->where('order_date', $date)
                ->update(['last_serial' => $nextSerial]);

            return $nextSerial;
        });

        return 'WL'.$date.str_pad((string) $serial, 3, '0', STR_PAD_LEFT);
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
            'prepared_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'feedback_invited_at' => 'datetime',
            'feedback_auto_invite_on' => 'date',
            'stock_decremented_at' => 'datetime',
        ];
    }

    /**
     * Calendar date (GMT) when the delayed feedback invite SMS should go out.
     */
    public static function feedbackAutoInviteOnFor(CarbonInterface $deliveredAt): string
    {
        return $deliveredAt->copy()
            ->timezone('GMT')
            ->startOfDay()
            ->addDays(3)
            ->toDateString();
    }

    /**
     * New deliveries only: eligible once feedback_auto_invite_on is due (09:00 GMT job).
     *
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopeDueForAutomaticFeedbackInvite(Builder $query): Builder
    {
        return $query
            ->where('status', OrderStatus::Delivered)
            ->whereNotNull('feedback_auto_invite_on')
            ->whereDate('feedback_auto_invite_on', '<=', now('GMT')->toDateString())
            ->whereNull('feedback_invited_at')
            ->whereDoesntHave('feedback')
            ->whereNotNull('customer_phone')
            ->where('customer_phone', '!=', '');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function feedback(): HasOne
    {
        return $this->hasOne(Feedback::class);
    }

    public function feedbackUrl(): ?string
    {
        if (blank($this->feedback_token)) {
            return null;
        }

        return route('feedback.show', $this->feedback_token);
    }

    public function canAcceptFeedback(): bool
    {
        return $this->status === OrderStatus::Delivered && $this->feedback()->doesntExist();
    }

    public function isPaid(): bool
    {
        return in_array($this->status, [
            OrderStatus::Paid,
            OrderStatus::Preparing,
            OrderStatus::Shipped,
            OrderStatus::Delivered,
        ], true);
    }

    public function isAwaitingConfirmation(): bool
    {
        return $this->status === OrderStatus::AwaitingConfirmation;
    }

    public function canConfirmPayment(): bool
    {
        return in_array($this->status, [
            OrderStatus::Pending,
            OrderStatus::AwaitingConfirmation,
        ], true);
    }

    public function canAcceptPaymentProof(): bool
    {
        return $this->canConfirmPayment();
    }

    public function paymentReference(): string
    {
        return $this->paystack_reference;
    }
}
