<?php

use App\Models\BoxRewardProfile;
use App\Models\MysteryBox;
use App\Models\MysteryBoxItem;
use App\Models\User;
use App\Models\UserInventoryItem;
use App\Services\WalletService;

test('users can view their inventory page', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'status' => 'active',
    ]);

    $box = MysteryBox::query()->create([
        'name' => 'Inventory Box',
        'slug' => 'inventory-box',
        'price_credits' => 10,
        'is_active' => true,
    ]);

    BoxRewardProfile::query()->create([
        'mystery_box_id' => $box->id,
        'eligible_credit_sources' => ['promo', 'sale', 'real_money'],
        'onboarding_item_types' => ['sticker'],
    ]);

    $item = MysteryBoxItem::query()->create([
        'mystery_box_id' => $box->id,
        'name' => 'Inventory Item',
        'item_type' => 'digital',
        'rarity' => 'common',
        'value_tier' => 'mid',
        'drop_weight' => 1,
        'estimated_value_credits' => 10,
        'sell_value_credits' => 5,
        'is_active' => true,
    ]);

    UserInventoryItem::query()->create([
        'user_id' => $user->id,
        'box_spin_id' => $user->spins()->create([
            'mystery_box_id' => $box->id,
            'result_item_id' => $item->id,
            'cost_credits' => 10,
            'status' => 'resolved',
            'server_seed_hash' => 'hash',
            'server_seed_plain' => 'plain',
            'client_seed' => 'client',
            'nonce' => 1,
            'roll_value' => 0.5,
            'resolved_at' => now(),
            'meta' => [],
        ])->id,
        'mystery_box_item_id' => $item->id,
        'state' => UserInventoryItem::STATE_SAVED,
        'item_snapshot' => [
            'name' => 'Inventory Item',
            'item_type' => 'digital',
            'value_tier' => 'mid',
            'estimated_value_credits' => 10,
            'sell_value_credits' => 5,
        ],
        'claim_status' => 'saved',
    ]);

    $this->actingAs($user)
        ->get(route('inventory.index'))
        ->assertOk()
        ->assertSee('Inventory')
        ->assertSee('Inventory Item')
        ->assertSee('Sell from inventory');
});

test('users can sell saved items from the inventory page', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'status' => 'active',
    ]);

    app(WalletService::class)->credit(
        user: $user,
        amount: 10,
        type: 'promo_seed',
        bucket: WalletService::BUCKET_PROMO,
        creditSource: WalletService::BUCKET_PROMO,
    );

    $box = MysteryBox::query()->create([
        'name' => 'Sell Box',
        'slug' => 'sell-box',
        'price_credits' => 10,
        'is_active' => true,
    ]);

    BoxRewardProfile::query()->create([
        'mystery_box_id' => $box->id,
        'eligible_credit_sources' => ['promo', 'sale', 'real_money'],
        'onboarding_item_types' => ['sticker'],
    ]);

    $item = MysteryBoxItem::query()->create([
        'mystery_box_id' => $box->id,
        'name' => 'Saved Inventory Item',
        'item_type' => 'digital',
        'rarity' => 'common',
        'value_tier' => 'mid',
        'drop_weight' => 1,
        'estimated_value_credits' => 10,
        'sell_value_credits' => 6,
        'is_active' => true,
    ]);

    $spin = $user->spins()->create([
        'mystery_box_id' => $box->id,
        'result_item_id' => $item->id,
        'cost_credits' => 10,
        'status' => 'resolved',
        'server_seed_hash' => 'hash',
        'server_seed_plain' => 'plain',
        'client_seed' => 'client',
        'nonce' => 1,
        'roll_value' => 0.5,
        'resolved_at' => now(),
        'meta' => [],
    ]);

    $inventoryItem = UserInventoryItem::query()->create([
        'user_id' => $user->id,
        'box_spin_id' => $spin->id,
        'mystery_box_item_id' => $item->id,
        'state' => UserInventoryItem::STATE_SAVED,
        'item_snapshot' => [
            'name' => 'Saved Inventory Item',
            'item_type' => 'digital',
            'value_tier' => 'mid',
            'estimated_value_credits' => 10,
            'sell_value_credits' => 6,
        ],
        'claim_status' => 'saved',
    ]);

    $this->actingAs($user)
        ->post(route('inventory.sell', $inventoryItem->id))
        ->assertRedirect();

    $inventoryItem->refresh();
    $user->refresh();

    expect($inventoryItem->state)->toBe(UserInventoryItem::STATE_SOLD);
    expect((float) $user->wallet->sale_credits)->toBe(6.0);
});
