<?php

namespace App\Services;

use App\Models\BoxConfigVersion;
use App\Models\BoxSpin;
use App\Models\MysteryBox;
use App\Models\MysteryBoxItem;
use App\Models\User;
use App\Models\UserInventoryItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SpinService
{
    public function __construct(
        private readonly WalletFundingService $walletFunding,
        private readonly UserProgressService $progress,
        private readonly SpinEconomyService $economy,
        private readonly SpinAuditService $audit,
        private readonly ProvablyFairService $fair,
        private readonly WeightedPicker $picker,
    ) {}

    public function spin(User $user, MysteryBox $box, string $clientSeed): BoxSpin
    {
        if (! $box->is_active) {
            throw ValidationException::withMessages(['box' => 'This box is not active.']);
        }

        $activeWeightTotal = (int) $box->activeItems()->sum('drop_weight');
        if ($activeWeightTotal <= 0) {
            throw ValidationException::withMessages([
                'box' => 'This box needs active item weights greater than 0 before spinning.',
            ]);
        }

        $clientSeed = trim($clientSeed);
        if ($clientSeed === '') {
            $clientSeed = (string) Str::uuid();
        }

        return DB::transaction(function () use ($user, $box, $clientSeed) {
            $progress = $this->progress->progressForUpdate($user, $box);
            $nonce = (int) $progress->lifetime_spin_count + 1;

            $serverSeedPlain = $this->fair->generateServerSeedPlain();
            $serverSeedHash = $this->fair->hashServerSeed($serverSeedPlain);

            $funding = $this->walletFunding->spend(
                user: $user,
                amount: (float) $box->price_credits,
                type: 'box_open',
                meta: ['box_id' => $box->id, 'box_slug' => $box->slug],
                referenceType: MysteryBox::class,
                referenceId: $box->id,
                originContext: ['kind' => 'spin', 'box_slug' => $box->slug],
            );

            if ($box->requires_real_money_only && array_sum(array_diff_key(
                $funding['funding_breakdown'],
                [WalletService::BUCKET_REAL_MONEY => true]
            )) > 0) {
                throw ValidationException::withMessages(['box' => 'This box requires real money credits only.']);
            }

            $items = $box->activeItems()->get();
            $economy = $this->economy->prepareSpin(
                user: $user,
                box: $box,
                items: $items,
                progress: $progress,
                primaryBucket: $funding['primary_bucket'] ?? WalletService::BUCKET_PROMO,
                entropy: [
                    'server_seed_plain' => $serverSeedPlain,
                    'client_seed' => $clientSeed,
                    'nonce' => $nonce,
                ],
            );

            $configVersion = $this->snapshotConfig($box, $economy['candidates'], $economy['profile'], $economy['candidate_map']);
            $roll = $this->fair->roll($serverSeedPlain, $clientSeed, $nonce);
            $winner = $this->picker->pick($economy['candidates'], $roll);

            $meta = $this->audit->spinMeta(
                user: $user,
                box: $box,
                winner: $winner,
                profile: $economy['profile'],
                progress: $progress,
                economySnapshot: $economy,
                funding: $funding,
            );

            $spin = BoxSpin::query()->create([
                'user_id' => $user->id,
                'mystery_box_id' => $box->id,
                'box_config_version_id' => $configVersion?->id,
                'result_item_id' => $winner->id,
                'cost_credits' => (float) $box->price_credits,
                'status' => 'resolved',
                'server_seed_hash' => $serverSeedHash,
                'server_seed_plain' => $serverSeedPlain,
                'client_seed' => $clientSeed,
                'nonce' => $nonce,
                'roll_value' => $roll,
                'resolved_at' => now(),
                'meta' => $meta,
            ]);

            $this->progress->registerWin($progress, $user, $winner, $this->progress->localDay($user));
            $progress->refresh();

            $inventoryItem = UserInventoryItem::query()->create([
                'user_id' => $user->id,
                'box_spin_id' => $spin->id,
                'mystery_box_item_id' => $winner->id,
                'state' => UserInventoryItem::STATE_PENDING,
                'item_snapshot' => array_merge(
                    $this->audit->itemSnapshot($winner, $box),
                    ['box_config_version_id' => $configVersion?->id]
                ),
                'claim_status' => 'available',
                'claimable_at' => now(),
            ]);

            $spin->meta = array_merge($meta, [
                'progress_after_spin' => [
                    'local_day' => $progress->local_day,
                    'daily_spin_count' => (int) $progress->daily_spin_count,
                    'lifetime_spin_count' => (int) $progress->lifetime_spin_count,
                    'consecutive_low_tier_spins' => (int) $progress->consecutive_low_tier_spins,
                ],
                'inventory_item_id' => $inventoryItem->id,
            ]);
            $spin->save();

            return $spin->load(['resultItem', 'box', 'inventoryItem']);
        }, attempts: 5);
    }

    /**
     * @param  Collection<int, MysteryBoxItem>  $items
     * @param  array<int, array<string, mixed>>  $candidateMap
     */
    private function snapshotConfig(
        MysteryBox $box,
        Collection $items,
        $profile,
        array $candidateMap,
    ): ?BoxConfigVersion {
        $latestVersion = (int) ($box->configVersions()->max('version') ?? 0);
        $version = $latestVersion + 1;

        return BoxConfigVersion::query()->create([
            'mystery_box_id' => $box->id,
            'version' => $version,
            'snapshot' => [
                'price_credits' => (float) $box->price_credits,
                'requires_real_money_only' => (bool) $box->requires_real_money_only,
                'reward_profile' => [
                    'target_rtp_min' => (float) $profile->target_rtp_min,
                    'target_rtp_max' => (float) $profile->target_rtp_max,
                    'eligible_credit_sources' => $profile->eligible_credit_sources,
                    'onboarding_max_spins' => (int) $profile->onboarding_max_spins,
                    'pity_after_spins' => (int) $profile->pity_after_spins,
                    'pity_multiplier' => (float) $profile->pity_multiplier,
                    'daily_progress_after_spins' => (int) $profile->daily_progress_after_spins,
                    'daily_progress_multiplier' => (float) $profile->daily_progress_multiplier,
                    'jackpot_enabled' => (bool) $profile->jackpot_enabled,
                    'high_tier_value_threshold' => (float) $profile->high_tier_value_threshold,
                ],
                'items' => $items->map(fn ($item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'item_type' => $item->item_type,
                    'rarity' => $item->rarity,
                    'value_tier' => $item->value_tier,
                    'drop_weight' => (int) $item->drop_weight,
                    'sell_value_credits' => (float) $item->sell_value_credits,
                    'estimated_value_credits' => (float) $item->sell_value_credits,
                    'rules' => $candidateMap[$item->id] ?? [],
                ])->values()->all(),
            ],
        ]);
    }
}
