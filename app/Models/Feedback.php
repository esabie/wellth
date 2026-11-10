<?php

namespace App\Models;

use App\Support\FeedbackFormOptions;
use Database\Factories\FeedbackFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id',
    'product_id',
    'shopping_rating',
    'improvement_tags',
    'note',
])]
class Feedback extends Model
{
    /** @use HasFactory<FeedbackFactory> */
    use HasFactory;

    protected $table = 'feedbacks';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'shopping_rating' => 'integer',
            'improvement_tags' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function shoppingLabel(): string
    {
        return FeedbackFormOptions::shoppingLabel((int) $this->shopping_rating);
    }

    /**
     * @return list<string>
     */
    public function improvementLabels(): array
    {
        return FeedbackFormOptions::improvementLabels($this->improvement_tags ?? []);
    }
}
