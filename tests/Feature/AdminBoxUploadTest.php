<?php

use App\Models\MysteryBox;
use App\Models\MysteryBoxItem;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('allows admins to upload a box thumbnail', function () {
    Storage::fake('public');

    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'is_admin' => true,
        'status' => 'active',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.boxes.store'), [
            'name' => 'Upload Box',
            'description' => 'Has uploaded image',
            'price_credits' => 12,
            'sort_order' => 0,
            'thumbnail_upload' => UploadedFile::fake()->image('box.png'),
        ])
        ->assertRedirect();

    $box = MysteryBox::query()->where('name', 'Upload Box')->firstOrFail();

    expect($box->thumbnail)->not->toBeNull();
    Storage::disk('public')->assertExists($box->thumbnail);
});

it('allows admins to replace an existing box thumbnail', function () {
    Storage::fake('public');

    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'is_admin' => true,
        'status' => 'active',
    ]);

    $existingPath = UploadedFile::fake()->image('old-box.png')->store('boxes', 'public');

    $box = MysteryBox::query()->create([
        'name' => 'Replace Box',
        'slug' => 'replace-box',
        'price_credits' => 10,
        'is_active' => true,
        'thumbnail' => $existingPath,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.boxes.update', $box->id), [
            'name' => 'Replace Box',
            'slug' => 'replace-box',
            'description' => 'Updated image',
            'price_credits' => 10,
            'is_active' => 1,
            'thumbnail_upload' => UploadedFile::fake()->image('new-box.png'),
        ])
        ->assertRedirect();

    $box->refresh();

    expect($box->thumbnail)->not->toBe($existingPath);
    Storage::disk('public')->assertExists($box->thumbnail);
});

it('auto-assigns box sort order when it is left empty', function () {
    Storage::fake('public');

    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'is_admin' => true,
        'status' => 'active',
    ]);

    MysteryBox::query()->create([
        'name' => 'Existing Box',
        'slug' => 'existing-box',
        'price_credits' => 9,
        'is_active' => true,
        'sort_order' => 4,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.boxes.store'), [
            'name' => 'Auto Sort Box',
            'description' => 'No sort order entered',
            'price_credits' => 12,
        ])
        ->assertRedirect();

    $box = MysteryBox::query()->where('name', 'Auto Sort Box')->firstOrFail();

    expect($box->sort_order)->toBe(5);
});

it('allows admins to upload a box item image', function () {
    Storage::fake('public');

    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'is_admin' => true,
        'status' => 'active',
    ]);

    $box = MysteryBox::query()->create([
        'name' => 'Upload Item Box',
        'slug' => 'upload-item-box',
        'price_credits' => 10,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.items.store', $box->id), [
            'name' => 'Uploaded Item',
            'item_type' => 'digital',
            'rarity' => 'common',
            'value_tier' => 'low',
            'drop_weight' => 5,
            'estimated_value_credits' => 8,
            'sell_value_credits' => 4,
            'sort_order' => 0,
            'image_upload' => UploadedFile::fake()->image('item.png'),
        ])
        ->assertRedirect();

    $item = MysteryBoxItem::query()->where('name', 'Uploaded Item')->firstOrFail();

    expect($item->image)->not->toBeNull();
    Storage::disk('public')->assertExists($item->image);
});

it('allows admins to upload AVIF images for box items', function () {
    Storage::fake('public');

    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'is_admin' => true,
        'status' => 'active',
    ]);

    $box = MysteryBox::query()->create([
        'name' => 'Upload AVIF Item Box',
        'slug' => 'upload-avif-item-box',
        'price_credits' => 10,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.items.store', $box->id), [
            'name' => 'Uploaded AVIF Item',
            'item_type' => 'digital',
            'drop_weight' => 5,
            'sell_value_credits' => 4,
            'sort_order' => 0,
            'image_upload' => UploadedFile::fake()->create('item.avif', 20, 'image/avif'),
        ])
        ->assertRedirect();

    $item = MysteryBoxItem::query()->where('name', 'Uploaded AVIF Item')->firstOrFail();

    expect($item->image)->not->toBeNull();
    Storage::disk('public')->assertExists($item->image);
});

it('allows admins to replace an existing box item image', function () {
    Storage::fake('public');

    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'is_admin' => true,
        'status' => 'active',
    ]);

    $box = MysteryBox::query()->create([
        'name' => 'Replace Item Box',
        'slug' => 'replace-item-box',
        'price_credits' => 10,
        'is_active' => true,
    ]);

    $existingPath = UploadedFile::fake()->image('old-item.png')->store('box-items', 'public');

    $item = MysteryBoxItem::query()->create([
        'mystery_box_id' => $box->id,
        'name' => 'Replaceable Item',
        'image' => $existingPath,
        'item_type' => 'digital',
        'rarity' => 'common',
        'value_tier' => 'low',
        'drop_weight' => 3,
        'estimated_value_credits' => 4,
        'sell_value_credits' => 2,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.items.update', [$box->id, $item->id]), [
            'name' => 'Replaceable Item',
            'item_type' => 'digital',
            'rarity' => 'common',
            'value_tier' => 'low',
            'drop_weight' => 3,
            'estimated_value_credits' => 4,
            'sell_value_credits' => 2,
            'is_active' => 1,
            'image_upload' => UploadedFile::fake()->image('new-item.png'),
        ])
        ->assertRedirect();

    $item->refresh();

    expect($item->image)->not->toBe($existingPath);
    Storage::disk('public')->assertExists($item->image);
});

it('shows uploaded item images on the admin items page', function () {
    Storage::fake('public');

    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'is_admin' => true,
        'status' => 'active',
    ]);

    $box = MysteryBox::query()->create([
        'name' => 'Visible Item Box',
        'slug' => 'visible-item-box',
        'price_credits' => 10,
        'is_active' => true,
    ]);

    $imagePath = UploadedFile::fake()->image('visible-item.png')->store('box-items', 'public');

    MysteryBoxItem::query()->create([
        'mystery_box_id' => $box->id,
        'name' => 'Visible Item',
        'image' => $imagePath,
        'item_type' => 'digital',
        'rarity' => 'common',
        'value_tier' => 'low',
        'drop_weight' => 3,
        'estimated_value_credits' => 4,
        'sell_value_credits' => 2,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.items', $box->id))
        ->assertSuccessful()
        ->assertSee(route('media.public', ['path' => $imagePath]), false);
});

it('shows a clear validation error when an invalid item image is uploaded', function () {
    Storage::fake('public');

    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'is_admin' => true,
        'status' => 'active',
    ]);

    $box = MysteryBox::query()->create([
        'name' => 'Invalid Item Image Box',
        'slug' => 'invalid-item-image-box',
        'price_credits' => 10,
        'is_active' => true,
    ]);

    $this->from(route('admin.items', $box->id))
        ->actingAs($admin)
        ->post(route('admin.items.store', $box->id), [
            'name' => 'Broken Image Item',
            'item_type' => 'digital',
            'rarity' => 'common',
            'value_tier' => 'low',
            'drop_weight' => 5,
            'estimated_value_credits' => 8,
            'sell_value_credits' => 4,
            'sort_order' => 0,
            'image_upload' => UploadedFile::fake()->create('not-an-image.txt', 10, 'text/plain'),
        ])
        ->assertRedirect(route('admin.items', $box->id))
        ->assertSessionHasErrors(['image_upload']);
});

it('lets admins duplicate a box item', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'is_admin' => true,
        'status' => 'active',
    ]);

    $box = MysteryBox::query()->create([
        'name' => 'Duplicate Item Box',
        'slug' => 'duplicate-item-box',
        'price_credits' => 10,
        'is_active' => true,
    ]);

    $item = MysteryBoxItem::query()->create([
        'mystery_box_id' => $box->id,
        'name' => 'Original Item',
        'item_type' => 'digital',
        'rarity' => 'common',
        'value_tier' => 'low',
        'drop_weight' => 3,
        'estimated_value_credits' => 4,
        'sell_value_credits' => 2,
        'is_active' => true,
        'sort_order' => 0,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.items.duplicate', [$box->id, $item->id]))
        ->assertRedirect();

    $copy = MysteryBoxItem::query()
        ->where('mystery_box_id', $box->id)
        ->where('name', 'Original Item Copy')
        ->first();

    expect($copy)->not->toBeNull();
    expect($copy->sort_order)->toBe(1);
    expect($copy->is_active)->toBeTrue();
});

it('auto-assigns item sort order when it is left empty', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'is_admin' => true,
        'status' => 'active',
    ]);

    $box = MysteryBox::query()->create([
        'name' => 'Auto Item Box',
        'slug' => 'auto-item-box',
        'price_credits' => 10,
        'is_active' => true,
    ]);

    MysteryBoxItem::query()->create([
        'mystery_box_id' => $box->id,
        'name' => 'Existing Item',
        'item_type' => 'digital',
        'rarity' => 'common',
        'value_tier' => 'low',
        'drop_weight' => 2,
        'estimated_value_credits' => 5,
        'sell_value_credits' => 2,
        'is_active' => true,
        'sort_order' => 3,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.items.store', $box->id), [
            'name' => 'Auto Sort Item',
            'item_type' => 'digital',
            'rarity' => 'rare',
            'value_tier' => 'mid',
            'drop_weight' => 5,
            'estimated_value_credits' => 8,
            'sell_value_credits' => 4,
        ])
        ->assertRedirect();

    $item = MysteryBoxItem::query()->where('name', 'Auto Sort Item')->firstOrFail();

    expect($item->sort_order)->toBe(4);
});

it('archives used items instead of deleting them', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);

    $user = User::factory()->create();

    app(WalletService::class)->credit(
        user: $user,
        amount: 20,
        type: 'promo_seed',
        bucket: WalletService::BUCKET_PROMO,
        creditSource: WalletService::BUCKET_PROMO,
    );

    $box = MysteryBox::query()->create([
        'name' => 'Archive Box',
        'slug' => 'archive-box',
        'price_credits' => 10,
        'is_active' => true,
    ]);

    $box->rewardProfile()->create([
        'eligible_credit_sources' => ['promo', 'sale', 'real_money'],
        'onboarding_item_types' => ['sticker'],
    ]);

    $item = MysteryBoxItem::query()->create([
        'mystery_box_id' => $box->id,
        'name' => 'Used Item',
        'item_type' => 'sticker',
        'rarity' => 'common',
        'value_tier' => 'low',
        'is_onboarding_only' => true,
        'drop_weight' => 5,
        'estimated_value_credits' => 5,
        'sell_value_credits' => 2,
        'is_active' => true,
    ]);

    $this->actingAs($user)->postJson('/boxes/archive-box/spins', [])->assertSuccessful();

    $this->actingAs($admin)
        ->post(route('admin.items.delete', [$box->id, $item->id]))
        ->assertRedirect();

    $item->refresh();

    expect($item->archived_at)->not->toBeNull();
    expect($item->is_active)->toBeFalse();
});

it('blocks adding item weights that push active box total above 100', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'is_admin' => true,
        'status' => 'active',
    ]);

    $box = MysteryBox::query()->create([
        'name' => 'Weight Cap Box',
        'slug' => 'weight-cap-box',
        'price_credits' => 10,
        'is_active' => true,
    ]);

    MysteryBoxItem::query()->create([
        'mystery_box_id' => $box->id,
        'name' => 'Heavy Existing Item',
        'item_type' => 'digital',
        'rarity' => 'common',
        'value_tier' => 'low',
        'drop_weight' => 80,
        'estimated_value_credits' => 4,
        'sell_value_credits' => 2,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.items.store', $box->id), [
            'name' => 'Too Heavy New Item',
            'item_type' => 'digital',
            'drop_weight' => 25,
            'sell_value_credits' => 3,
        ])
        ->assertSessionHasErrors('drop_weight');
});
