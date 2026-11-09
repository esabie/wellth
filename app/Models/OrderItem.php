<?php

namespace App\Models;

use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id',
    'product_id',
    'product_name',
    'product_sku',
    'product_slug',
    'unit_price',
    'quantity',
    'lactose_intolerant',
    'line_total',
])]
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'quantity' => 'integer',
            'lactose_intolerant' => 'boolean',
            'line_total' => 'decimal:2',
        ];
    }

    public function lactoseLabel(): ?string
    {
        if ($this->lactose_intolerant === null) {
            return null;
        }

        return $this->lactose_intolerant ? 'Lactose intolerant' : 'Not lactose intolerant';
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
