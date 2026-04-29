<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBoxItemStat extends Model
{
    protected $fillable = [
        'user_id',
        'mystery_box_id',
        'mystery_box_item_id',
        'won_count',
        'sold_count',
        'saved_count',
        'claimed_count',
        'won_today_count',
        'last_local_day',
        'last_won_at',
        'meta',
    ];

    protected $casts = [
        'won_count' => 'integer',
        'sold_count' => 'integer',
        'saved_count' => 'integer',
        'claimed_count' => 'integer',
        'won_today_count' => 'integer',
        'last_won_at' => 'datetime',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function box(): BelongsTo
    {
        return $this->belongsTo(MysteryBox::class, 'mystery_box_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(MysteryBoxItem::class, 'mystery_box_item_id');
    }
}
