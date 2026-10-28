<?php

namespace App\Models;

use App\Enums\SmsCampaignAudience;
use App\Enums\SmsCampaignStatus;
use Database\Factories\SmsCampaignFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'audience',
    'message',
    'sender_id',
    'status',
    'recipients_count',
    'sent_count',
    'failed_count',
    'recipient_phones',
    'meta',
    'error_message',
    'sent_at',
])]
class SmsCampaign extends Model
{
    /** @use HasFactory<SmsCampaignFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'audience' => SmsCampaignAudience::class,
            'status' => SmsCampaignStatus::class,
            'recipients_count' => 'integer',
            'sent_count' => 'integer',
            'failed_count' => 'integer',
            'recipient_phones' => 'array',
            'meta' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
