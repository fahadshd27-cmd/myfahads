<?php

use App\Models\MysteryBox;
use App\Models\MysteryBoxItem;
use App\Models\User;

it('shows the boxes index page for verified active users', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'status' => 'active',
    ]);

    MysteryBox::query()->create([
        'name' => 'Starter Box',
        'slug' => 'starter-box',
        'description' => 'Demo box',
        'price_credits' => 5,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get(route('boxes.index'))
        ->assertOk()
        ->assertSee('Starter Box');
});

it('shows the box detail page for verified active users', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'status' => 'active',
    ]);

    $box = MysteryBox::query()->create([
        'name' => 'Detail Box',
        'slug' => 'detail-box',
        'description' => 'Detail demo',
        'price_credits' => 7,
        'is_active' => true,
    ]);

    MysteryBoxItem::query()->create([
        'mystery_box_id' => $box->id,
        'name' => 'Detail Item',
        'rarity' => 'rare',
        'drop_weight' => 1,
        'estimated_value_credits' => 9,
        'sell_value_credits' => 4,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get(route('boxes.show', $box->slug))
        ->assertOk()
        ->assertSee('Detail Box')
        ->assertSee('Detail Item')
        ->assertSee('GIVEAWAYS.COM')
        ->assertDontSee('Laravel Starter Kit');
});

it('shows a deposit prompt on the box page when the user balance is below the box price', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'status' => 'active',
    ]);

    $box = MysteryBox::query()->create([
        'name' => 'Premium Box',
        'slug' => 'premium-box',
        'description' => 'Needs more credits',
        'price_credits' => 25,
        'is_active' => true,
    ]);

    MysteryBoxItem::query()->create([
        'mystery_box_id' => $box->id,
        'name' => 'Premium Item',
        'item_type' => 'digital',
        'rarity' => 'rare',
        'value_tier' => 'mid',
        'drop_weight' => 1,
        'estimated_value_credits' => 9,
        'sell_value_credits' => 4,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get(route('boxes.show', $box->slug))
        ->assertOk()
        ->assertSee('Add credits to open')
        ->assertSee('Add credits first and then come back to spin.');
});
