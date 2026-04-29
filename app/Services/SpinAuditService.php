<?php

namespace App\Services;

use App\Models\BoxRewardProfile;
use App\Models\BoxSpin;
use App\Models\MysteryBox;
use App\Models\MysteryBoxItem;
use App\Models\User;
use App\Models\UserBoxProgress;

class SpinAuditService
{
    /**
     * @param  array<string, mixed>  $economySnapshot
     * @param  array<string, mixed>  $funding
     * @return array<string, mixed>
     */
    public function spinMeta(
        User $user,
        MysteryBox $box,
        MysteryBoxItem $winner,
        BoxRewardProfile $profile,
        UserBoxProgress $progress,
        array $economySnapshot,
        array $funding,
    ): array {
        return [
            'funding' => [
                'primary_bucket' => $funding['primary_bucket'] ?? null,
                'breakdown' => $funding['funding_breakdown'] ?? [],
            ],
            'profile' => [
                'target_rtp_min' => (float) $profile->target_rtp_min,
                'target_rtp_max' => (float) $profile->target_rtp_max,
                'onboarding_max_spins' => (int) $profile->onboarding_max_spins,
                'pity_after_spins' => (int) $profile->pity_after_spins,
                'jackpot_enabled' => (bool) $profile->jackpot_enabled,
            ],
            'candidate_items' => array_keys($economySnapshot['candidate_map'] ?? []),
            'weights' => $economySnapshot['candidate_map'] ?? [],
            'reason_trail' => $economySnapshot['reason_trail'] ?? [],
            'progress_before_spin' => [
                'local_day' => $progress->local_day,
                'daily_spin_count' => (int) $progress->daily_spin_count,
                'lifetime_spin_count' => (int) $progress->lifetime_spin_count,
                'consecutive_low_tier_spins' => (int) $progress->consecutive_low_tier_spins,
            ],
            'winner_snapshot' => $this->itemSnapshot($winner, $box),
            'user_timezone' => $user->timezone ?: config('app.timezone', 'UTC'),
        ];
    }

    public function itemSnapshot(MysteryBoxItem $item, MysteryBox $box): array
    {
        $sell = (float) $item->sell_value_credits;

        return [
            'item_id' => $item->id,
            'box_id' => $box->id,
            'box_name' => $box->name,
            'name' => $item->name,
            'image' => $item->image,
            'item_type' => $item->item_type,
            'rarity' => $item->rarity,
            'value_tier' => $item->value_tier,
            // We use a single public price field. Keep the legacy key for compatibility.
            'estimated_value_credits' => $sell,
            'sell_value_credits' => $sell,
            'drop_weight' => (int) $item->drop_weight,
        ];
    }

    public function fairnessSummary(BoxSpin $spin): array
    {
        return [
            'server_seed_hash' => $spin->server_seed_hash,
            'client_seed' => $spin->client_seed,
            'nonce' => (int) $spin->nonce,
            'roll_value' => (float) $spin->roll_value,
        ];
    }
}
