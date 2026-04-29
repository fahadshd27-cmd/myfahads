<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBoxProgress extends Model
{
    protected $fillable = [
        'user_id',
        'mystery_box_id',
        'local_day',
        'daily_spin_count',
        'lifetime_spin_count',
        'onboarding_spins_used',
        'consecutive_low_tier_spins',
        'progression_segment',
        'high_tier_wins_today',
        'jackpot_wins_today',
        'last_jackpot_spin_index',
        'last_spin_at',
        'meta',
    ];

    protected $casts = [
        'daily_spin_count' => 'integer',
        'lifetime_spin_count' => 'integer',
        'onboarding_spins_used' => 'integer',
        'consecutive_low_tier_spins' => 'integer',
        'progression_segment' => 'integer',
        'high_tier_wins_today' => 'integer',
        'jackpot_wins_today' => 'integer',
        'last_jackpot_spin_index' => 'integer',
        'last_spin_at' => 'datetime',
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
}
