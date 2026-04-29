<?php

namespace App\Services;

use App\Models\BoxRewardProfile;
use App\Models\MysteryBoxItem;
use App\Models\User;
use App\Models\UserBoxItemStat;
use App\Models\UserBoxProgress;

class SpinEligibilityService
{
    public function isEligible(
        User $user,
        MysteryBoxItem $item,
        BoxRewardProfile $profile,
        UserBoxProgress $progress,
        string $primaryBucket,
    ): bool {
        if (! $item->is_active || $item->archived_at) {
            return false;
        }

        $eligibleSources = $item->eligible_credit_sources ?: $profile->eligible_credit_sources ?: WalletService::availableBuckets();
        if (! in_array($primaryBucket, $eligibleSources, true)) {
            return false;
        }

        if ($item->min_account_age_hours && $user->created_at?->diffInHours(now()) < $item->min_account_age_hours) {
            return false;
        }

        if ($item->min_real_spend && $this->realMoneySpend($user) < (float) $item->min_real_spend) {
            return false;
        }

        if (($profile->economy_mode ?? 'advanced') !== 'simple') {
            if ($item->is_onboarding_only && ! $this->isOnboardingWindow($user, $profile, $progress)) {
                return false;
            }

            if ($item->is_returning_user_only && $this->isOnboardingWindow($user, $profile, $progress)) {
                return false;
            }

            $eligibleSpinRanges = collect($item->eligible_spin_ranges ?? [])
                ->map(function ($range): array {
                    return [
                        'from' => data_get($range, 'from'),
                        'to' => data_get($range, 'to'),
                    ];
                })
                ->filter(fn (array $range): bool => $range['from'] !== null || $range['to'] !== null)
                ->values();

            if ($eligibleSpinRanges->isNotEmpty()) {
                $lifetimeSpinIndex = $progress->lifetime_spin_count + 1;
                $matchedRange = $eligibleSpinRanges->contains(function (array $range) use ($lifetimeSpinIndex) {
                    $start = (int) ($range['from'] ?? 1);
                    $end = (int) ($range['to'] ?? PHP_INT_MAX);

                    return $lifetimeSpinIndex >= $start && $lifetimeSpinIndex <= $end;
                });

                if (! $matchedRange) {
                    return false;
                }
            }

            $stats = UserBoxItemStat::query()->where([
                'user_id' => $user->id,
                'mystery_box_item_id' => $item->id,
            ])->first();

            if ($item->daily_limit && $stats && $stats->won_today_count >= $item->daily_limit) {
                return false;
            }

            if ($item->lifetime_limit && $stats && $stats->won_count >= $item->lifetime_limit) {
                return false;
            }

            if ($item->value_tier === 'jackpot' && (! $profile->jackpot_enabled || $progress->jackpot_wins_today >= $profile->jackpot_max_wins_per_day)) {
                return false;
            }

            if (in_array($item->value_tier, ['high', 'jackpot'], true)
                && $progress->high_tier_wins_today >= max(1, $profile->daily_progress_cap + 1)) {
                return false;
            }
        } elseif ($item->value_tier === 'jackpot' && ! $profile->jackpot_enabled) {
            return false;
        }

        return true;
    }

    public function isOnboardingWindow(User $user, BoxRewardProfile $profile, UserBoxProgress $progress): bool
    {
        return $progress->onboarding_spins_used < $profile->onboarding_max_spins
            && ($user->created_at?->diffInHours(now()) ?? PHP_INT_MAX) <= $profile->onboarding_max_account_age_hours;
    }

    private function realMoneySpend(User $user): float
    {
        return (float) $user->walletTransactions()
            ->where('type', 'deposit_credit')
            ->sum('amount');
    }
}
