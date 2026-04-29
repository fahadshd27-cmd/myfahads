<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserInventoryItem extends Model
{
    public const STATE_PENDING = 'pending_decision';

    public const STATE_KEPT = 'kept';

    public const STATE_SAVED = 'saved';

    public const STATE_SOLD = 'sold';

    public const STATE_CLAIMED = 'claimed';

    public const STATE_EXPIRED = 'expired';

    public const STATE_CANCELED = 'canceled';

    protected $fillable = [
        'user_id',
        'box_spin_id',
        'mystery_box_item_id',
        'state',
        'item_snapshot',
        'claim_status',
        'sell_amount_credits',
        'claimable_at',
        'claimed_at',
        'acted_at',
    ];

    protected $casts = [
        'item_snapshot' => 'array',
        'sell_amount_credits' => 'decimal:2',
        'claimable_at' => 'datetime',
        'claimed_at' => 'datetime',
        'acted_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function spin(): BelongsTo
    {
        return $this->belongsTo(BoxSpin::class, 'box_spin_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(MysteryBoxItem::class, 'mystery_box_item_id');
    }
}
