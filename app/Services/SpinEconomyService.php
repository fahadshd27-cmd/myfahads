<?php

namespace App\Services;

use App\Models\BoxRewardProfile;
use App\Models\BoxSpin;
use App\Models\MysteryBox;
use App\Models\MysteryBoxItem;
use App\Models\User;
use App\Models\UserBoxItemStat;
use App\Models\UserBoxProgress;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class SpinEconomyService
{
    public function __construct(
        private readonly SpinEligibilityService $eligibility,
        private readonly ProvablyFairService $fair,
    ) {}

    /**
     * @param  Collection<int, MysteryBoxItem>  $items
     * @return array{profile: BoxRewardProfile, candidates: Collection<int, MysteryBoxItem>, candidate_map: array<int, array<string, mixed>>, reason_trail: array<int, array<string, mixed>>}
     */
    public function prepareSpin(
        User $user,
        MysteryBox $box,
        Collection $items,
        UserBoxProgress $progress,
        string $primaryBucket,
        ?array $entropy = null,
    ): array {
        $profile = $box->rewardProfile()->firstOrCreate([], [
            'eligible_credit_sources' => WalletService::availableBuckets(),
            'onboarding_item_types' => ['sticker', 'coupon'],
        ]);

        $allowedSources = $profile->eligible_credit_sources;
        if (is_array($allowedSources) && count($allowedSources) > 0 && ! in_array($primaryBucket, $allowedSources, true)) {
            throw ValidationException::withMessages([
                'box' => 'This box does not allow '.$primaryBucket.' credits. Update Allowed credit sources in box settings.',
            ]);
        }

        $eligibleItems = $items->filter(fn (MysteryBoxItem $item) => $this->eligibility->isEligible($user, $item, $profile, $progress, $primaryBucket));
        if (($profile->economy_mode ?? 'advanced') === 'simple') {
            return $this->prepareSimpleSpin(
                user: $user,
                box: $box,
                items: $items,
                eligibleItems: $eligibleItems,
                profile: $profile,
                progress: $progress,
                entropy: $entropy,
            );
        }

        $preferredStarterItemId = $this->preferredStarterItemId($eligibleItems, $profile);
        $candidateMap = [];
        $reasonTrail = [];

        foreach ($items as $item) {
            if (! $eligibleItems->contains(fn (MysteryBoxItem $eligibleItem) => $eligibleItem->id === $item->id)) {
                $reasonTrail[] = [
                    'item_id' => $item->id,
                    'status' => 'filtered',
                    'reason' => 'hard_rule',
                ];

                continue;
            }

            $baseWeight = max(0, (float) $item->drop_weight);
            $effectiveWeight = $baseWeight;
            $rules = [];
            $stats = UserBoxItemStat::query()->where([
                'user_id' => $user->id,
                'mystery_box_item_id' => $item->id,
            ])->first();

            if ($this->eligibility->isOnboardingWindow($user, $profile, $progress)
                && in_array($item->item_type, $profile->onboarding_item_types ?: ['sticker', 'coupon'], true)) {
                $effectiveWeight *= 3;
                $rules[] = 'onboarding_boost';
            }

            if ($preferredStarterItemId === $item->id && $progress->lifetime_spin_count < 2) {
                $effectiveWeight *= 1.8;
                $rules[] = 'starter_lead_bias';
            }

            if ($progress->consecutive_low_tier_spins >= $profile->pity_after_spins && $item->value_tier === 'mid') {
                $effectiveWeight *= (float) $profile->pity_multiplier;
                $rules[] = 'pity_boost';
            }

            if ($progress->daily_spin_count >= $profile->daily_progress_after_spins
                && in_array($item->item_type, ['sticker', 'coupon', 'digital'], true)
                && $progress->progression_segment < $profile->daily_progress_cap) {
                $effectiveWeight *= (float) $profile->daily_progress_multiplier;
                $rules[] = 'daily_progress_boost';
            }

            if ($stats && $item->max_repeat_per_day && $stats->won_today_count >= $item->max_repeat_per_day) {
                $effectiveWeight *= 0.1;
                $rules[] = 'anti_repeat_soft_cap';
            }

            if ($stats && $stats->won_today_count > 0 && $item->value_tier === 'low') {
                $effectiveWeight *= 0.2;
                $rules[] = 'anti_repeat_dampening';
            }

            if ($item->value_tier === 'jackpot') {
                if (! $profile->jackpot_enabled) {
                    continue;
                }

                $effectiveWeight *= 0.05;
                $rules[] = 'jackpot_guardrail';
            }

            if ((float) $item->sell_value_credits > (float) $profile->high_tier_value_threshold) {
                $effectiveWeight *= 0.25;
                $rules[] = 'high_tier_guardrail';
            }

            if ($effectiveWeight <= 0) {
                continue;
            }

            $item->drop_weight = max(1, (int) round($effectiveWeight));
            $candidateMap[$item->id] = [
                'base_weight' => $baseWeight,
                'effective_weight' => (float) $item->drop_weight,
                'matched_rules' => $rules,
                'item_type' => $item->item_type,
                'value_tier' => $item->value_tier,
            ];

            $reasonTrail[] = [
                'item_id' => $item->id,
                'status' => 'eligible',
                'rules' => $rules,
            ];
        }

        $candidates = $items->filter(fn (MysteryBoxItem $item) => isset($candidateMap[$item->id]))->values();
        if ($candidates->isEmpty()) {
            throw ValidationException::withMessages(['items' => 'No eligible items available for this spin.']);
        }

        $expectedReturn = $this->expectedReturn($candidateMap, $candidates);
        if ($expectedReturn < (float) $profile->target_rtp_min || $expectedReturn > (float) $profile->target_rtp_max * 1.5) {
            $reasonTrail[] = [
                'status' => 'guardrail_warning',
                'reason' => 'rtp_out_of_bounds',
                'expected_return' => $expectedReturn,
            ];
        }

        return [
            'profile' => $profile,
            'candidates' => $candidates,
            'candidate_map' => $candidateMap,
            'reason_trail' => $reasonTrail,
        ];
    }

    /**
     * @param  Collection<int, MysteryBoxItem>  $items
     * @param  Collection<int, MysteryBoxItem>  $eligibleItems
     * @return array{profile: BoxRewardProfile, candidates: Collection<int, MysteryBoxItem>, candidate_map: array<int, array<string, mixed>>, reason_trail: array<int, array<string, mixed>>}
     */
    private function prepareSimpleSpin(
        User $user,
        MysteryBox $box,
        Collection $items,
        Collection $eligibleItems,
        BoxRewardProfile $profile,
        UserBoxProgress $progress,
        ?array $entropy = null,
    ): array {
        $settings = $this->simpleSettings($profile);
        $windowHours = (int) ($settings['window_hours'] ?? 24);
        $since = now()->subHours($windowHours);

        $spins = BoxSpin::query()
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $since)
            ->with(['inventoryItem'])
            ->latest()
            ->limit(500)
            ->get();

        $spent = (float) $spins->sum('cost_credits');
        $returned = (float) $spins->sum(function (BoxSpin $spin): float {
            return (float) data_get($spin->inventoryItem?->item_snapshot, 'sell_value_credits', 0);
        });

        $sameBoxSpins = (int) $spins->where('mystery_box_id', $box->id)->count();
        $firstSpin = $spins->isEmpty();
        $firstBoxSpin = $sameBoxSpins === 0;

        $netLoss = max(0.0, $spent - $returned);
        $recoveryAfterNetLoss = (float) ($settings['recovery_after_net_loss_percent'] ?? 150) / 100.0;
        $shouldRecover = $netLoss >= ((float) $box->price_credits * $recoveryAfterNetLoss);

        $repeatAfter = (int) ($settings['repeat_same_box_after_spins'] ?? 3);
        $scenario = 'normal_spin';
        if ($firstSpin) {
            $scenario = 'first_spin';
        } elseif ($firstBoxSpin) {
            $scenario = 'first_box_spin';
        } elseif ($sameBoxSpins >= $repeatAfter) {
            $scenario = 'repeat_spin';
        } elseif ($shouldRecover) {
            $scenario = 'recovery_spin';
        }

        [$minPercent, $maxPercent] = $this->scenarioBand($settings, $scenario);
        $capPercent = (float) ($settings['max_payout_percent'] ?? 70);
        $maxPercent = min($maxPercent, $capPercent);

        $basis = match ((string) ($settings['band_basis'] ?? 'net_loss_after_cost')) {
            'box_price' => (float) $box->price_credits,
            default => (float) $netLoss + (float) $box->price_credits,
        };

        $basis = max((float) $box->price_credits, $basis);

        // In simple mode we still want to feel "random" within the configured band.
        // When entropy is available (from provably-fair seeds), we pick a per-spin band target
        // and use a narrower band window around it. This avoids deterministic step-function behavior.
        $bandTargetPercent = null;
        $bandWindowPercent = null;
        $effectiveMinPercent = $minPercent;
        $effectiveMaxPercent = $maxPercent;

        if (is_array($entropy) && isset($entropy['server_seed_plain'], $entropy['client_seed'], $entropy['nonce'])) {
            $targetRoll = $this->fair->rollWithSalt(
                (string) $entropy['server_seed_plain'],
                (string) $entropy['client_seed'],
                (int) $entropy['nonce'],
                'simple_band_target:'.$box->id,
            );

            $bandTargetPercent = $minPercent + (($maxPercent - $minPercent) * $targetRoll);
            $bandWindowPercent = max(6.0, ($maxPercent - $minPercent) * 0.65);

            $effectiveMinPercent = max($minPercent, $bandTargetPercent - ($bandWindowPercent / 2));
            $effectiveMaxPercent = min($maxPercent, $bandTargetPercent + ($bandWindowPercent / 2));
        }

        $minValue = max(0.0, $basis * ($effectiveMinPercent / 100.0));
        $maxValue = max($minValue, $basis * ($effectiveMaxPercent / 100.0));
        $capValue = max($maxValue, $basis * ($capPercent / 100.0));

        $bandItems = $this->itemsInValueBand($eligibleItems, $minValue, $maxValue, $capValue);
        $jackpotTailItems = $eligibleItems
            ->filter(fn (MysteryBoxItem $item): bool => $item->item_type === 'jackpot')
            ->values();
        $floorCount = min(3, max(1, (int) floor($eligibleItems->count() / 4)));
        $floorItems = $eligibleItems
            ->sortBy(fn (MysteryBoxItem $item) => (float) $item->sell_value_credits)
            ->take($floorCount)
            ->values();

        // Use last spin as a soft anti-streak signal so the spinner feels less "robotic".
        $lastSpin = $spins->first();
        $lastItemId = (int) ($lastSpin?->result_item_id ?? 0);
        $lastWinValue = (float) data_get($lastSpin?->inventoryItem?->item_snapshot, 'sell_value_credits', 0);

        $reasonTrail = [[
            'status' => 'payout_band',
            'mode' => 'simple',
            'profile' => $settings['profile_key'] ?? null,
            'window_hours' => $windowHours,
            'scenario' => $scenario,
            'spent_credits' => $spent,
            'returned_credits' => $returned,
            'net_loss_credits' => $netLoss,
            'same_box_spins' => $sameBoxSpins,
            'band_basis' => (string) ($settings['band_basis'] ?? 'net_loss_after_cost'),
            'band_basis_amount' => $basis,
            'min_percent' => $minPercent,
            'max_percent' => $maxPercent,
            'effective_min_percent' => $effectiveMinPercent,
            'effective_max_percent' => $effectiveMaxPercent,
            'band_target_percent' => $bandTargetPercent,
            'band_window_percent' => $bandWindowPercent,
            'min_value' => $minValue,
            'max_value' => $maxValue,
            'cap_value' => $capValue,
            'eligible_items' => $eligibleItems->count(),
            'band_items' => $bandItems->count(),
            'floor_items' => $floorItems->count(),
            'last_win_value' => $lastWinValue,
            'jackpot_tail_items' => $jackpotTailItems->count(),
        ]];

        $candidateMap = [];

        // Prefer band items, but do not overpower the distribution so hard that it feels deterministic.
        // When entropy is available we allow fallback variety every time (not only when the band is tiny).
        $bandWeightScale = 20;
        $floorWeightScale = 8;
        $fallbackWeightScale = 4;
        $allowFallbackAlways = false;

        if (is_array($entropy) && isset($entropy['server_seed_plain'], $entropy['client_seed'], $entropy['nonce'])) {
            $scaleRoll = $this->fair->rollWithSalt(
                (string) $entropy['server_seed_plain'],
                (string) $entropy['client_seed'],
                (int) $entropy['nonce'],
                'simple_scale:'.$box->id,
            );

            // Small per-spin variation to avoid the "robot" feel while staying provably-fair.
            $bandWeightScale = (int) round(14 + (10 * $scaleRoll)); // 14..24
            $floorWeightScale = 7;
            $fallbackWeightScale = 5;
            $allowFallbackAlways = true;
        }

        foreach ($items as $item) {
            $inBand = $bandItems->contains(fn (MysteryBoxItem $bandItem) => $bandItem->id === $item->id);
            $isJackpotTail = $profile->jackpot_enabled && $item->item_type === 'jackpot';
            $inFloor = $floorItems->contains(fn (MysteryBoxItem $floorItem) => $floorItem->id === $item->id);
            $allowFallback = $allowFallbackAlways || $bandItems->count() < 3;

            if (! $inBand && ! $inFloor && ! $allowFallback && ! $isJackpotTail) {
                $reasonTrail[] = [
                    'item_id' => $item->id,
                    'status' => 'filtered',
                    'reason' => $eligibleItems->contains(fn (MysteryBoxItem $eligibleItem) => $eligibleItem->id === $item->id)
                        ? 'payout_band'
                        : 'hard_rule',
                ];

                continue;
            }

            $baseWeight = max(0, (float) $item->drop_weight);
            $effectiveWeight = $baseWeight;
            $rules = [];

            if ($isJackpotTail) {
                $effectiveWeight *= 1;
                $rules[] = 'jackpot_tail';
            } elseif ($inBand) {
                $effectiveWeight *= $bandWeightScale;
                $rules[] = 'simple_band';
            } elseif ($inFloor) {
                $effectiveWeight *= $floorWeightScale;
                $rules[] = 'floor_layer';
            } else {
                $effectiveWeight *= $fallbackWeightScale;
                $rules[] = 'fallback_variety';
            }

            $stats = UserBoxItemStat::query()->where([
                'user_id' => $user->id,
                'mystery_box_item_id' => $item->id,
            ])->first();

            // Small deterministic weight jitter (provably-fair) so results do not feel like a fixed script.
            if (is_array($entropy) && isset($entropy['server_seed_plain'], $entropy['client_seed'], $entropy['nonce'])) {
                $jitterRoll = $this->fair->rollWithSalt(
                    (string) $entropy['server_seed_plain'],
                    (string) $entropy['client_seed'],
                    (int) $entropy['nonce'],
                    'simple_jitter:'.$item->id,
                );

                $jitterMultiplier = 0.90 + (0.20 * $jitterRoll); // 0.90..1.10
                $effectiveWeight *= $jitterMultiplier;
                $rules[] = 'weight_jitter';
            }

            // Avoid same item back-to-back (unless the pool is tiny).
            if ($lastItemId > 0 && $item->id === $lastItemId) {
                $poolCount = max(1, (int) $eligibleItems->count());
                $effectiveWeight *= $poolCount <= 3 ? 0.25 : 0.03;
                $rules[] = 'no_back_to_back';
            }

            // If we just gave a "big" item, reduce the chance of another big item immediately after.
            // This keeps the experience feeling more random and less like a step-function.
            if ($lastWinValue > 0
                && $lastWinValue >= ((float) $box->price_credits * 0.2)
                && (float) $item->sell_value_credits >= ($lastWinValue * 0.9)) {
                $effectiveWeight *= 0.08;
                $rules[] = 'cooldown_after_big_win';
            }

            // General repeat dampening for any previously-won item today.
            if ($stats && $stats->won_today_count > 0) {
                $dampen = match (true) {
                    $stats->won_today_count >= 3 => 0.18,
                    $stats->won_today_count === 2 => 0.30,
                    default => 0.50,
                };

                $effectiveWeight *= $dampen;
                $rules[] = 'anti_repeat_dampening';
            }

            if ($effectiveWeight <= 0) {
                continue;
            }

            $item->drop_weight = max(1, (int) round($effectiveWeight));
            $candidateMap[$item->id] = [
                'base_weight' => $baseWeight,
                'effective_weight' => (float) $item->drop_weight,
                'matched_rules' => $rules,
                'item_type' => $item->item_type,
                'value_tier' => $item->value_tier,
            ];

            $reasonTrail[] = [
                'item_id' => $item->id,
                'status' => 'eligible',
                'rules' => $rules,
            ];
        }

        $candidates = $items->filter(fn (MysteryBoxItem $item) => isset($candidateMap[$item->id]))->values();
        if ($candidates->isEmpty()) {
            throw ValidationException::withMessages(['items' => 'No eligible items available for this spin.']);
        }

        return [
            'profile' => $profile,
            'candidates' => $candidates,
            'candidate_map' => $candidateMap,
            'reason_trail' => $reasonTrail,
        ];
    }

    /**
     * @param  Collection<int, MysteryBoxItem>  $eligibleItems
     */
    private function preferredStarterItemId(Collection $eligibleItems, BoxRewardProfile $profile): ?int
    {
        $starterItem = $eligibleItems
            ->filter(function (MysteryBoxItem $item) use ($profile): bool {
                return $item->value_tier === 'low'
                    && in_array($item->item_type, $profile->onboarding_item_types ?: ['sticker', 'coupon'], true);
            })
            ->sortByDesc('drop_weight')
            ->first();

        return $starterItem?->id;
    }

    /**
     * @return array<string, mixed>
     */
    private function simpleSettings(BoxRewardProfile $profile): array
    {
        $key = (string) ($profile->economy_profile ?? 'normal');
        $defaults = (array) config('spinner.simple_profiles.'.$key, config('spinner.simple_profiles.normal', []));

        return array_merge($defaults, [
            'profile_key' => $key,
            'window_hours' => (int) ($profile->window_hours ?? $defaults['window_hours'] ?? 24),
            'max_payout_percent' => (float) ($profile->max_payout_percent ?? $defaults['max_payout_percent'] ?? 70),
            'repeat_same_box_after_spins' => (int) ($profile->repeat_same_box_after_spins ?? $defaults['repeat_same_box_after_spins'] ?? 3),
            'recovery_after_net_loss_percent' => (float) ($profile->recovery_after_net_loss_percent ?? $defaults['recovery_after_net_loss_percent'] ?? 150),
            'band_basis' => (string) ($defaults['band_basis'] ?? 'net_loss_after_cost'),
        ]);
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function scenarioBand(array $settings, string $scenario): array
    {
        $band = $settings[$scenario] ?? null;
        if (is_array($band) && count($band) >= 2) {
            return [(float) $band[0], (float) $band[1]];
        }

        return match ($scenario) {
            'first_spin' => [(float) ($settings['first_spin_min_percent'] ?? 5), (float) ($settings['first_spin_max_percent'] ?? 20)],
            'first_box_spin' => [(float) ($settings['first_box_spin_min_percent'] ?? 10), (float) ($settings['first_box_spin_max_percent'] ?? 35)],
            'repeat_spin' => [(float) ($settings['repeat_spin_min_percent'] ?? 5), (float) ($settings['repeat_spin_max_percent'] ?? 15)],
            'recovery_spin' => [(float) ($settings['recovery_spin_min_percent'] ?? 35), (float) ($settings['recovery_spin_max_percent'] ?? 60)],
            default => [(float) ($settings['normal_spin_min_percent'] ?? 10), (float) ($settings['normal_spin_max_percent'] ?? 30)],
        };
    }

    /**
     * @param  Collection<int, MysteryBoxItem>  $eligibleItems
     * @return Collection<int, MysteryBoxItem>
     */
    private function itemsInValueBand(Collection $eligibleItems, float $minValue, float $maxValue, float $capValue): Collection
    {
        $band = $eligibleItems->filter(function (MysteryBoxItem $item) use ($minValue, $maxValue): bool {
            $sell = (float) $item->sell_value_credits;

            return $sell >= $minValue && $sell <= $maxValue;
        })->values();

        if ($band->isNotEmpty()) {
            return $band;
        }

        $expand = (float) (($maxValue - $minValue) ?: 0.25);
        for ($i = 1; $i <= 8; $i++) {
            $min = max(0.0, $minValue - ($expand * $i));
            $max = min($capValue, $maxValue + ($expand * $i));

            $band = $eligibleItems->filter(function (MysteryBoxItem $item) use ($min, $max): bool {
                $sell = (float) $item->sell_value_credits;

                return $sell >= $min && $sell <= $max;
            })->values();

            if ($band->isNotEmpty()) {
                return $band;
            }
        }

        return $eligibleItems
            ->sortBy(fn (MysteryBoxItem $item) => (float) $item->sell_value_credits)
            ->take(min(12, max(1, $eligibleItems->count())))
            ->values();
    }

    /**
     * @param  array<int, array<string, mixed>>  $candidateMap
     * @param  Collection<int, MysteryBoxItem>  $candidates
     */
    public function expectedReturn(array $candidateMap, Collection $candidates): float
    {
        $totalWeight = max(1.0, (float) $candidates->sum('drop_weight'));
        $expectedValue = $candidates->sum(function (MysteryBoxItem $item) use ($candidateMap, $totalWeight) {
            $weight = (float) data_get($candidateMap, $item->id.'.effective_weight', 0);

            return ((float) $item->sell_value_credits * $weight) / $totalWeight;
        });

        return $expectedValue;
    }
}
