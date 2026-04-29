<?php

use App\Models\AppSetting;
use App\Models\BoxRewardProfile;
use App\Models\MysteryBox;
use App\Models\MysteryBoxItem;
use App\Models\User;
use App\Models\UserBoxItemStat;
use App\Models\UserBoxProgress;
use App\Models\UserInventoryItem;
use App\Services\DepositService;
use App\Services\SpinEconomyService;
use App\Services\WalletService;

function createManagedBox(array $boxOverrides = [], array $profileOverrides = []): MysteryBox
{
    $box = MysteryBox::query()->create(array_merge([
        'name' => 'Test Box',
        'slug' => 'test-box',
        'price_credits' => 10,
        'is_active' => true,
        'sort_order' => 0,
        'requires_real_money_only' => false,
    ], $boxOverrides));

    BoxRewardProfile::query()->create(array_merge([
        'mystery_box_id' => $box->id,
        'economy_mode' => 'advanced',
        'target_rtp_min' => 20,
        'target_rtp_max' => 90,
        'eligible_credit_sources' => ['promo', 'sale', 'real_money'],
        'onboarding_max_spins' => 3,
        'onboarding_max_account_age_hours' => 48,
        'onboarding_item_types' => ['sticker', 'coupon'],
        'pity_after_spins' => 2,
        'pity_multiplier' => 3,
        'daily_progress_after_spins' => 5,
        'daily_progress_multiplier' => 1.4,
        'daily_progress_cap' => 2,
        'jackpot_enabled' => true,
        'jackpot_max_wins_per_day' => 1,
        'jackpot_cooldown_spins' => 0,
        'high_tier_value_threshold' => 200,
    ], $profileOverrides));

    return $box;
}

function addBoxItem(MysteryBox $box, array $overrides = []): MysteryBoxItem
{
    return MysteryBoxItem::query()->create(array_merge([
        'mystery_box_id' => $box->id,
        'name' => 'Item '.fake()->word(),
        'item_type' => 'digital',
        'rarity' => 'common',
        'value_tier' => 'low',
        'drop_weight' => 1,
        'estimated_value_credits' => 1,
        'sell_value_credits' => 0.5,
        'is_active' => true,
        'sort_order' => 0,
    ], $overrides));
}

it('debits promo balance and creates spin with pending inventory snapshot', function () {
    $user = User::factory()->create();

    app(WalletService::class)->credit(
        user: $user,
        amount: 100,
        type: 'test_topup',
        bucket: WalletService::BUCKET_PROMO,
        creditSource: WalletService::BUCKET_PROMO,
    );

    $box = createManagedBox();
    addBoxItem($box, ['name' => 'Item A', 'item_type' => 'sticker', 'is_onboarding_only' => true]);
    addBoxItem($box, ['name' => 'Item B', 'value_tier' => 'mid']);

    $res = $this->actingAs($user)->postJson('/boxes/test-box/spins', [
        'client_seed' => 'seed',
    ]);

    $res->assertSuccessful();
    expect($res->json('spin_id'))->toBeInt();
    expect($res->json('winner.id'))->toBeInt();
    expect($res->json('funding_source.primary_bucket'))->toBe('promo');
    expect($res->json('inventory_state'))->toBe(UserInventoryItem::STATE_PENDING);
    expect(collect($res->json('reel'))->every(fn (array $slot) => $slot['type'] === 'item'))->toBeTrue();

    $user->refresh();
    expect((float) $user->wallet->promo_credits)->toBe(90.0);
    expect((float) $user->wallet->balance_credits)->toBe(90.0);

    $inventory = UserInventoryItem::query()->where('user_id', $user->id)->latest()->first();
    expect($inventory)->not->toBeNull();
    expect($inventory->state)->toBe(UserInventoryItem::STATE_PENDING);
    expect(data_get($inventory->item_snapshot, 'name'))->not->toBeNull();
});

it('favors onboarding-only starter items for first spins', function () {
    $user = User::factory()->create();

    app(WalletService::class)->credit(
        user: $user,
        amount: 20,
        type: 'promo_seed',
        bucket: WalletService::BUCKET_PROMO,
        creditSource: WalletService::BUCKET_PROMO,
    );

    $box = createManagedBox(['slug' => 'starter-box'], ['onboarding_max_spins' => 2]);
    $starter = addBoxItem($box, [
        'name' => 'Starter Sticker',
        'item_type' => 'sticker',
        'is_onboarding_only' => true,
        'drop_weight' => 50,
    ]);
    addBoxItem($box, [
        'name' => 'Returning Reward',
        'value_tier' => 'mid',
        'is_returning_user_only' => true,
        'drop_weight' => 50,
    ]);

    $response = $this->actingAs($user)->postJson('/boxes/starter-box/spins', []);

    $response->assertSuccessful();
    expect($response->json('winner.id'))->toBe($starter->id);
});

it('stops onboarding restrictions after configured count', function () {
    $user = User::factory()->create();

    app(WalletService::class)->credit(
        user: $user,
        amount: 30,
        type: 'promo_seed',
        bucket: WalletService::BUCKET_PROMO,
        creditSource: WalletService::BUCKET_PROMO,
    );

    $box = createManagedBox(['slug' => 'progress-box'], ['onboarding_max_spins' => 1]);
    $starter = addBoxItem($box, [
        'name' => 'Starter Sticker',
        'item_type' => 'sticker',
        'is_onboarding_only' => true,
    ]);
    $returning = addBoxItem($box, [
        'name' => 'Returning Reward',
        'item_type' => 'digital',
        'value_tier' => 'mid',
        'is_returning_user_only' => true,
        'drop_weight' => 200,
    ]);

    $this->actingAs($user)->postJson('/boxes/progress-box/spins', [])->assertSuccessful();
    $second = $this->actingAs($user)->postJson('/boxes/progress-box/spins', []);

    $second->assertSuccessful();
    expect($second->json('winner.id'))->toBe($returning->id);
    expect($second->json('winner.id'))->not->toBe($starter->id);
});

it('gives the highest-weight starter item a stronger lead during early onboarding spins', function () {
    $user = User::factory()->create();

    $box = createManagedBox(['slug' => 'starter-bias-box'], ['onboarding_max_spins' => 4]);
    $sticker = addBoxItem($box, [
        'name' => 'Starter Sticker',
        'item_type' => 'sticker',
        'is_onboarding_only' => true,
        'drop_weight' => 250,
    ]);
    $coupon = addBoxItem($box, [
        'name' => 'Welcome Coupon',
        'item_type' => 'coupon',
        'is_onboarding_only' => true,
        'drop_weight' => 220,
    ]);

    $progress = UserBoxProgress::query()->create([
        'user_id' => $user->id,
        'mystery_box_id' => $box->id,
        'local_day' => now()->toDateString(),
        'daily_spin_count' => 0,
        'lifetime_spin_count' => 0,
    ]);

    $economy = app(SpinEconomyService::class)->prepareSpin(
        user: $user,
        box: $box,
        items: $box->activeItems()->get(),
        progress: $progress,
        primaryBucket: WalletService::BUCKET_PROMO,
    );

    expect(data_get($economy, 'candidate_map.'.$sticker->id.'.effective_weight'))->toBeGreaterThan(
        data_get($economy, 'candidate_map.'.$coupon->id.'.effective_weight') * 1.5
    );
});

it('excludes real-money-only items from promo-funded spins', function () {
    $user = User::factory()->create();

    app(WalletService::class)->credit(
        user: $user,
        amount: 20,
        type: 'promo_seed',
        bucket: WalletService::BUCKET_PROMO,
        creditSource: WalletService::BUCKET_PROMO,
    );

    $box = createManagedBox(['slug' => 'source-box']);
    addBoxItem($box, [
        'name' => 'Real Money Prize',
        'value_tier' => 'jackpot',
        'eligible_credit_sources' => ['real_money'],
    ]);
    $allowed = addBoxItem($box, [
        'name' => 'Promo Prize',
        'item_type' => 'sticker',
        'is_onboarding_only' => true,
    ]);

    $response = $this->actingAs($user)->postJson('/boxes/source-box/spins', []);

    $response->assertSuccessful();
    expect($response->json('winner.id'))->toBe($allowed->id);
});

it('credits sale bucket from selling and preserves inventory snapshot values', function () {
    $user = User::factory()->create();

    app(WalletService::class)->credit(
        user: $user,
        amount: 20,
        type: 'promo_seed',
        bucket: WalletService::BUCKET_PROMO,
        creditSource: WalletService::BUCKET_PROMO,
    );

    $box = createManagedBox(['slug' => 'snapshot-box']);
    $item = addBoxItem($box, [
        'name' => 'Snapshot Item',
        'sell_value_credits' => 4,
        'estimated_value_credits' => 9,
        'item_type' => 'sticker',
        'is_onboarding_only' => true,
    ]);

    $spinResponse = $this->actingAs($user)->postJson('/boxes/snapshot-box/spins', []);
    $inventoryId = $spinResponse->json('inventory_item_id');
    $inventory = UserInventoryItem::query()->findOrFail($inventoryId);

    $item->update([
        'name' => 'Changed Name',
        'sell_value_credits' => 1,
        'estimated_value_credits' => 1,
    ]);

    $sellResponse = $this->actingAs($user)->postJson("/inventory/{$inventoryId}/sell", []);

    $sellResponse->assertSuccessful();

    $inventory->refresh();
    $user->refresh();

    expect($inventory->state)->toBe(UserInventoryItem::STATE_SOLD);
    expect((float) $inventory->sell_amount_credits)->toBe(4.0);
    expect(data_get($inventory->item_snapshot, 'name'))->toBe('Snapshot Item');
    expect((float) $user->wallet->sale_credits)->toBe(4.0);
});

it('supports simple economy mode by selecting items inside a payout band', function () {
    $user = User::factory()->create([
        'created_at' => now()->subDays(7),
    ]);

    $box = MysteryBox::query()->create([
        'name' => 'Simple Box',
        'slug' => 'simple-box',
        'price_credits' => 10,
        'is_active' => true,
    ]);

    BoxRewardProfile::query()->create([
        'mystery_box_id' => $box->id,
        'economy_mode' => 'simple',
        'economy_profile' => 'normal',
        'eligible_credit_sources' => ['promo', 'sale', 'real_money'],
        'jackpot_enabled' => true,
    ]);

    $low = MysteryBoxItem::query()->create([
        'mystery_box_id' => $box->id,
        'name' => 'Low Payout',
        'item_type' => 'coupon',
        'rarity' => 'common',
        'value_tier' => 'low',
        'drop_weight' => 100,
        'estimated_value_credits' => 1,
        'sell_value_credits' => 0.8,
        'is_active' => true,
    ]);

    MysteryBoxItem::query()->create([
        'mystery_box_id' => $box->id,
        'name' => 'Too High For First Band',
        'item_type' => 'digital',
        'rarity' => 'rare',
        'value_tier' => 'mid',
        'drop_weight' => 100,
        'estimated_value_credits' => 10,
        'sell_value_credits' => 4.5,
        'is_active' => true,
    ]);

    $progress = UserBoxProgress::query()->create([
        'user_id' => $user->id,
        'mystery_box_id' => $box->id,
        'local_day' => now()->toDateString(),
        'daily_spin_count' => 0,
        'lifetime_spin_count' => 0,
    ]);

    $economy = app(SpinEconomyService::class)->prepareSpin(
        user: $user,
        box: $box,
        items: $box->activeItems()->get(),
        progress: $progress,
        primaryBucket: WalletService::BUCKET_PROMO,
    );

    expect(data_get($economy, 'reason_trail.0.status'))->toBe('payout_band');
    expect(data_get($economy, 'candidate_map.'.$low->id))->not->toBeNull();
    expect($economy['candidates']->pluck('id')->all())->toContain($low->id);
});

it('strongly dampens repeat low-tier wins for the same item', function () {
    $user = User::factory()->create();

    $box = createManagedBox(['slug' => 'repeat-dampen-box'], ['onboarding_max_spins' => 4]);
    $sticker = addBoxItem($box, [
        'name' => 'Starter Sticker',
        'item_type' => 'sticker',
        'is_onboarding_only' => true,
        'drop_weight' => 250,
    ]);
    $coupon = addBoxItem($box, [
        'name' => 'Welcome Coupon',
        'item_type' => 'coupon',
        'is_onboarding_only' => true,
        'drop_weight' => 220,
    ]);

    UserBoxItemStat::query()->create([
        'user_id' => $user->id,
        'mystery_box_id' => $box->id,
        'mystery_box_item_id' => $coupon->id,
        'won_count' => 1,
        'won_today_count' => 1,
        'last_local_day' => now()->toDateString(),
    ]);

    $progress = UserBoxProgress::query()->create([
        'user_id' => $user->id,
        'mystery_box_id' => $box->id,
        'local_day' => now()->toDateString(),
        'daily_spin_count' => 1,
        'lifetime_spin_count' => 1,
        'onboarding_spins_used' => 1,
    ]);

    $economy = app(SpinEconomyService::class)->prepareSpin(
        user: $user,
        box: $box,
        items: $box->activeItems()->get(),
        progress: $progress,
        primaryBucket: WalletService::BUCKET_PROMO,
    );

    expect(data_get($economy, 'candidate_map.'.$coupon->id.'.matched_rules'))->toContain('anti_repeat_dampening');
    expect(data_get($economy, 'candidate_map.'.$sticker->id.'.effective_weight'))->toBeGreaterThan(
        data_get($economy, 'candidate_map.'.$coupon->id.'.effective_weight') * 4
    );
});

it('ignores blank eligible spin ranges saved from the admin form', function () {
    $user = User::factory()->create([
        'created_at' => now()->subDays(7),
    ]);

    app(WalletService::class)->credit(
        user: $user,
        amount: 20,
        type: 'deposit_credit',
        bucket: WalletService::BUCKET_REAL_MONEY,
        creditSource: WalletService::BUCKET_REAL_MONEY,
    );

    $box = createManagedBox(['slug' => 'blank-range-box', 'price_credits' => 4], [
        'onboarding_max_spins' => 0,
    ]);

    $eligibleItem = addBoxItem($box, [
        'name' => 'Range Safe Item',
        'item_type' => 'digital',
        'value_tier' => 'mid',
        'drop_weight' => 50,
        'estimated_value_credits' => 5,
        'sell_value_credits' => 2,
        'eligible_spin_ranges' => [['from' => null, 'to' => null]],
    ]);

    $response = $this->actingAs($user)->postJson('/boxes/blank-range-box/spins', []);

    $response->assertSuccessful();
    expect($response->json('winner.id'))->toBe($eligibleItem->id);
});

it('resets local-day progress counters when the user day changes', function () {
    $user = User::factory()->create([
        'timezone' => 'America/New_York',
    ]);

    app(WalletService::class)->credit(
        user: $user,
        amount: 20,
        type: 'promo_seed',
        bucket: WalletService::BUCKET_PROMO,
        creditSource: WalletService::BUCKET_PROMO,
    );

    $box = createManagedBox(['slug' => 'reset-box']);
    addBoxItem($box, ['name' => 'Reset Prize', 'item_type' => 'sticker', 'is_onboarding_only' => true]);

    UserBoxProgress::query()->create([
        'user_id' => $user->id,
        'mystery_box_id' => $box->id,
        'local_day' => now('America/New_York')->subDay()->toDateString(),
        'daily_spin_count' => 5,
        'lifetime_spin_count' => 5,
        'consecutive_low_tier_spins' => 4,
    ]);

    $this->actingAs($user)->postJson('/boxes/reset-box/spins', [])->assertSuccessful();

    $progress = UserBoxProgress::query()->where('user_id', $user->id)->where('mystery_box_id', $box->id)->firstOrFail();

    expect($progress->local_day)->toBe(now('America/New_York')->toDateString());
    expect($progress->daily_spin_count)->toBe(1);
});

it('credits wallet after testing deposit is simulated paid into real-money bucket', function () {
    $user = User::factory()->create();

    AppSetting::putString('payments.mode', DepositService::MODE_TESTING);

    $res = $this->actingAs($user)->postJson('/deposits', [
        'amount' => 25,
        'gateway' => 'paylink',
    ]);

    $res->assertSuccessful();
    $ref = $res->json('reference');
    expect($ref)->toBeString();

    $this->actingAs($user)->post("/deposits/{$ref}/simulate", ['outcome' => 'paid'])->assertRedirect();

    $user->refresh();
    expect((float) $user->wallet->real_money_credits)->toBe(25.0);
    expect((float) $user->wallet->balance_credits)->toBe(25.0);
});

it('lets returning users open a starter-style box with real-money credits', function () {
    $user = User::factory()->create([
        'created_at' => now()->subDays(7),
    ]);

    app(WalletService::class)->credit(
        user: $user,
        amount: 25,
        type: 'deposit_credit',
        bucket: WalletService::BUCKET_REAL_MONEY,
        creditSource: WalletService::BUCKET_REAL_MONEY,
    );

    $box = createManagedBox([
        'slug' => 'starter-style-box',
        'price_credits' => 4,
    ], [
        'onboarding_max_spins' => 4,
        'onboarding_max_account_age_hours' => 48,
        'onboarding_item_types' => ['sticker', 'coupon'],
    ]);

    addBoxItem($box, [
        'name' => 'Starter Sticker',
        'item_type' => 'sticker',
        'is_onboarding_only' => true,
        'drop_weight' => 250,
    ]);
    addBoxItem($box, [
        'name' => 'Welcome Coupon',
        'item_type' => 'coupon',
        'is_onboarding_only' => true,
        'drop_weight' => 220,
    ]);
    $eligibleItem = addBoxItem($box, [
        'name' => 'Mystery Art Card',
        'item_type' => 'digital',
        'value_tier' => 'mid',
        'drop_weight' => 45,
        'estimated_value_credits' => 2,
        'sell_value_credits' => 1,
    ]);
    addBoxItem($box, [
        'name' => 'Premium Headset',
        'item_type' => 'physical',
        'value_tier' => 'high',
        'eligible_credit_sources' => ['sale', 'real_money'],
        'min_real_spend' => 20,
        'drop_weight' => 3,
        'estimated_value_credits' => 180,
        'sell_value_credits' => 120,
    ]);
    addBoxItem($box, [
        'name' => 'Legendary Dream Prize',
        'item_type' => 'jackpot',
        'value_tier' => 'jackpot',
        'eligible_credit_sources' => ['real_money'],
        'min_real_spend' => 100,
        'min_account_age_hours' => 24,
        'drop_weight' => 1,
        'estimated_value_credits' => 22000,
        'sell_value_credits' => 15000,
    ]);

    $response = $this->actingAs($user)->postJson('/boxes/starter-style-box/spins', []);

    $response->assertSuccessful();
    expect($response->json('winner.id'))->toBeInt();
    expect((float) $response->json('balance.total'))->toBe(21.0);
});

it('allows spinning when active item weights are below 100 but above zero', function () {
    $user = User::factory()->create();

    app(WalletService::class)->credit(
        user: $user,
        amount: 20,
        type: 'promo_seed',
        bucket: WalletService::BUCKET_PROMO,
        creditSource: WalletService::BUCKET_PROMO,
    );

    $box = createManagedBox(['slug' => 'invalid-weight-box']);
    addBoxItem($box, [
        'name' => 'Item A',
        'drop_weight' => 60,
        'is_onboarding_only' => true,
    ]);
    addBoxItem($box, [
        'name' => 'Item B',
        'drop_weight' => 20,
        'value_tier' => 'mid',
    ]);

    $response = $this->actingAs($user)->postJson('/boxes/invalid-weight-box/spins', []);

    $response->assertSuccessful();
    expect($response->json('winner.id'))->toBeInt();
});
