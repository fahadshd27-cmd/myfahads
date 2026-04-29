<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BoxSpin extends Model
{
    protected $fillable = [
        'user_id',
        'mystery_box_id',
        'box_config_version_id',
        'result_item_id',
        'cost_credits',
        'status',
        'server_seed_hash',
        'server_seed_plain',
        'client_seed',
        'nonce',
        'roll_value',
        'resolved_at',
        'meta',
    ];

    protected $casts = [
        'cost_credits' => 'decimal:2',
        'nonce' => 'integer',
        'roll_value' => 'decimal:8',
        'resolved_at' => 'datetime',
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

    public function resultItem(): BelongsTo
    {
        return $this->belongsTo(MysteryBoxItem::class, 'result_item_id');
    }

    public function configVersion(): BelongsTo
    {
        return $this->belongsTo(BoxConfigVersion::class, 'box_config_version_id');
    }

    public function inventoryItem(): HasOne
    {
        return $this->hasOne(UserInventoryItem::class);
    }

    public function fundingSource(): ?string
    {
        return data_get($this->meta, 'funding.primary_bucket');
    }
}
