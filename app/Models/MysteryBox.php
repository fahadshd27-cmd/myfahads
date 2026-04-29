<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MysteryBox extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'thumbnail',
        'price_credits',
        'requires_real_money_only',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price_credits' => 'decimal:2',
        'requires_real_money_only' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(MysteryBoxItem::class);
    }

    public function activeItems(): HasMany
    {
        return $this->items()
            ->where('is_active', true)
            ->whereNull('archived_at')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function configVersions(): HasMany
    {
        return $this->hasMany(BoxConfigVersion::class);
    }

    public function rewardProfile(): HasOne
    {
        return $this->hasOne(BoxRewardProfile::class, 'mystery_box_id');
    }

    public function progressEntries(): HasMany
    {
        return $this->hasMany(UserBoxProgress::class, 'mystery_box_id');
    }

    public function thumbnailUrl(): ?string
    {
        if (! $this->thumbnail) {
            return null;
        }

        if (str_starts_with($this->thumbnail, 'http://') || str_starts_with($this->thumbnail, 'https://')) {
            return $this->thumbnail;
        }

        return route('media.public', ['path' => $this->thumbnail]);
    }
}
