<?php

namespace App\Services;

use App\Models\MysteryBox;
use App\Models\MysteryBoxItem;
use App\Models\User;
use App\Models\UserBoxItemStat;
use App\Models\UserBoxProgress;
use App\Models\UserInventoryItem;
use Carbon\CarbonImmutable;

class UserProgressService
{
    public function progressForUpdate(User $user, MysteryBox $box): UserBoxProgress
    {
        $progress = UserBoxProgress::query()->firstOrCreate([
            'user_id' => $user->id,
            'mystery_box_id' => $box->id,
        ], [
            'local_day' => $this->localDay($user),
        ]);

        $progress = UserBoxProgress::query()
            ->whereKey($progress->id)
            ->lockForUpdate()
            ->firstOrFail();

        return $this->resetDailyCountersIfNeeded($progress, $user);
    }

    public function localDay(User $user): string
    {
        return CarbonImmutable::now($this->timezone($user))->toDateString();
    }

    public function timezone(User $user): string
    {
        return $user->timezone ?: config('app.timezone', 'UTC');
    }

    public function registerWin(UserBoxProgress $progress, User $user, MysteryBoxItem $item, string $localDay): void
    {
        $progress->daily_spin_count++;
        $progress->lifetime_spin_count++;
        $progress->last_spin_at = now();
        $progress->progression_segment = (int) floor($progress->daily_spin_count / 5);

        if ($item->is_onboarding_only || in_array($item->item_type, ['sticker', 'coupon'], true)) {
            $progress->onboarding_spins_used++;
        }

        if ($item->value_tier === 'low') {
            $progress->consecutive_low_tier_spins++;
        } else {
            $progress->consecutive_low_tier_spins = 0;
        }

        if (in_array($item->value_tier, ['high', 'jackpot'], true)) {
            $progress->high_tier_wins_today++;
        }

        if ($item->value_tier === 'jackpot') {
            $progress->jackpot_wins_today++;
            $progress->last_jackpot_spin_index = $progress->lifetime_spin_count;
        }

        $progress->save();

        $stats = UserBoxItemStat::query()->firstOrCreate([
            'user_id' => $user->id,
            'mystery_box_id' => $item->mystery_box_id,
            'mystery_box_item_id' => $item->id,
        ]);

        if ($stats->last_local_day !== $localDay) {
            $stats->won_today_count = 0;
        }

        $stats->won_count++;
        $stats->won_today_count++;
        $stats->last_local_day = $localDay;
        $stats->last_won_at = now();
        $stats->save();
    }

    public function registerInventoryAction(UserInventoryItem $inventoryItem, string $action): void
    {
        $stats = UserBoxItemStat::query()->where([
            'user_id' => $inventoryItem->user_id,
            'mystery_box_item_id' => $inventoryItem->mystery_box_item_id,
        ])->first();

        if (! $stats) {
            return;
        }

        if ($action === UserInventoryItem::STATE_SOLD) {
            $stats->sold_count++;
        }

        if ($action === UserInventoryItem::STATE_SAVED) {
            $stats->saved_count++;
        }

        if ($action === UserInventoryItem::STATE_CLAIMED) {
            $stats->claimed_count++;
        }

        $stats->save();
    }

    private function resetDailyCountersIfNeeded(UserBoxProgress $progress, User $user): UserBoxProgress
    {
        $localDay = $this->localDay($user);
        if ($progress->local_day === $localDay) {
            return $progress;
        }

        $progress->local_day = $localDay;
        $progress->daily_spin_count = 0;
        $progress->progression_segment = 0;
        $progress->high_tier_wins_today = 0;
        $progress->jackpot_wins_today = 0;
        $progress->save();

        UserBoxItemStat::query()
            ->where('user_id', $progress->user_id)
            ->where('mystery_box_id', $progress->mystery_box_id)
            ->update([
                'won_today_count' => 0,
                'last_local_day' => $localDay,
            ]);

        return $progress;
    }
}
