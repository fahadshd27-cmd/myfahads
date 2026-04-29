<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoxRewardProfile extends Model
{
    protected $fillable = [
        'mystery_box_id',
        'economy_mode',
        'economy_profile',
        'window_hours',
        'max_payout_percent',
        'first_spin_min_percent',
        'first_spin_max_percent',
        'first_box_spin_min_percent',
        'first_box_spin_max_percent',
        'normal_spin_min_percent',
        'normal_spin_max_percent',
        'repeat_spin_min_percent',
        'repeat_spin_max_percent',
        'recovery_spin_min_percent',
        'recovery_spin_max_percent',
        'repeat_same_box_after_spins',
        'recovery_after_net_loss_percent',
        'target_rtp_min',
        'target_rtp_max',
        'eligible_credit_sources',
        'onboarding_max_spins',
        'onboarding_max_account_age_hours',
        'onboarding_item_types',
        'pity_after_spins',
        'pity_multiplier',
        'daily_progress_after_spins',
        'daily_progress_multiplier',
        'daily_progress_cap',
        'jackpot_enabled',
        'jackpot_max_wins_per_day',
        'jackpot_cooldown_spins',
        'high_tier_value_threshold',
    ];

    protected $casts = [
        'window_hours' => 'integer',
        'max_payout_percent' => 'decimal:2',
        'first_spin_min_percent' => 'decimal:2',
        'first_spin_max_percent' => 'decimal:2',
        'first_box_spin_min_percent' => 'decimal:2',
        'first_box_spin_max_percent' => 'decimal:2',
        'normal_spin_min_percent' => 'decimal:2',
        'normal_spin_max_percent' => 'decimal:2',
        'repeat_spin_min_percent' => 'decimal:2',
        'repeat_spin_max_percent' => 'decimal:2',
        'recovery_spin_min_percent' => 'decimal:2',
        'recovery_spin_max_percent' => 'decimal:2',
        'repeat_same_box_after_spins' => 'integer',
        'recovery_after_net_loss_percent' => 'decimal:2',
        'target_rtp_min' => 'decimal:2',
        'target_rtp_max' => 'decimal:2',
        'eligible_credit_sources' => 'array',
        'onboarding_max_spins' => 'integer',
        'onboarding_max_account_age_hours' => 'integer',
        'onboarding_item_types' => 'array',
        'pity_after_spins' => 'integer',
        'pity_multiplier' => 'decimal:4',
        'daily_progress_after_spins' => 'integer',
        'daily_progress_multiplier' => 'decimal:4',
        'daily_progress_cap' => 'integer',
        'jackpot_enabled' => 'boolean',
        'jackpot_max_wins_per_day' => 'integer',
        'jackpot_cooldown_spins' => 'integer',
        'high_tier_value_threshold' => 'decimal:2',
    ];

    public function box(): BelongsTo
    {
        return $this->belongsTo(MysteryBox::class, 'mystery_box_id');
    }
}
