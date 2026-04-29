<?php

use App\Models\MysteryBox;
use App\Models\MysteryBoxItem;
use Illuminate\Support\Facades\Storage;

it('serves uploaded box thumbnails through the public media route', function () {
    Storage::fake('public');
    Storage::disk('public')->put('boxes/demo-box.png', 'box-image');

    $box = MysteryBox::query()->create([
        'name' => 'Media Box',
        'slug' => 'media-box',
        'price_credits' => 10,
        'is_active' => true,
        'thumbnail' => 'boxes/demo-box.png',
    ]);

    expect($box->thumbnailUrl())->toContain('/media/boxes/demo-box.png');

    $this->get($box->thumbnailUrl())
        ->assertOk();
});

it('serves uploaded item images through the public media route', function () {
    Storage::fake('public');
    Storage::disk('public')->put('box-items/demo-item.png', 'item-image');

    $box = MysteryBox::query()->create([
        'name' => 'Media Item Box',
        'slug' => 'media-item-box',
        'price_credits' => 10,
        'is_active' => true,
    ]);

    $item = MysteryBoxItem::query()->create([
        'mystery_box_id' => $box->id,
        'name' => 'Media Item',
        'image' => 'box-items/demo-item.png',
        'rarity' => 'common',
        'drop_weight' => 1,
        'estimated_value_credits' => 5,
        'sell_value_credits' => 2,
        'is_active' => true,
    ]);

    expect($item->imageUrl())->toContain('/media/box-items/demo-item.png');

    $this->get($item->imageUrl())
        ->assertOk();
});
