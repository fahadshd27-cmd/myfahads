<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MysteryBoxItem extends Model
{
    protected $fillable = [
        'mystery_box_id',
        'name',
        'image',
        'item_type',
        'rarity',
        'value_tier',
        'is_onboarding_only',
        'is_returning_user_only',
        'eligible_credit_sources',
        'eligible_spin_ranges',
        'daily_limit',
        'lifetime_limit',
        'min_account_age_hours',
        'min_real_spend',
        'max_repeat_per_day',
        'drop_weight',
        'estimated_value_credits',
        'sell_value_credits',
        'is_active',
        'sort_order',
        'archived_at',
    ];

    protected $casts = [
        'eligible_credit_sources' => 'array',
        'eligible_spin_ranges' => 'array',
        'daily_limit' => 'integer',
        'lifetime_limit' => 'integer',
        'min_account_age_hours' => 'integer',
        'min_real_spend' => 'decimal:2',
        'max_repeat_per_day' => 'integer',
        'drop_weight' => 'integer',
        'estimated_value_credits' => 'decimal:2',
        'sell_value_credits' => 'decimal:2',
        'is_onboarding_only' => 'boolean',
        'is_returning_user_only' => 'boolean',
        'is_active' => 'boolean',
        'archived_at' => 'datetime',
    ];

    public function box(): BelongsTo
    {
        return $this->belongsTo(MysteryBox::class, 'mystery_box_id');
    }

    public function spins(): HasMany
    {
        return $this->hasMany(BoxSpin::class, 'result_item_id');
    }

    public function userInventoryItems(): HasMany
    {
        return $this->hasMany(UserInventoryItem::class, 'mystery_box_item_id');
    }

    public function imageUrl(): ?string
    {
        if (! $this->image) {
            return null;
        }

        if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
            return $this->image;
        }

        return route('media.public', ['path' => $this->image]);
    }
}
