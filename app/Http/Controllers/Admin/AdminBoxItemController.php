<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MysteryBox;
use App\Models\MysteryBoxItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminBoxItemController extends Controller
{
    public function index(Request $request, int $boxId): View
    {
        $box = MysteryBox::query()->findOrFail($boxId);
        $items = $box->items()->orderBy('sort_order')->orderBy('id')->get();
        $otherBoxes = MysteryBox::query()
            ->whereKeyNot($box->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'name']);
        $editingItem = null;

        if ($request->filled('edit')) {
            $editingItem = $items->firstWhere('id', (int) $request->integer('edit'));
        }

        return view('admin.items.index', [
            'box' => $box,
            'items' => $items,
            'otherBoxes' => $otherBoxes,
            'editingItem' => $editingItem,
        ]);
    }

    public function store(Request $request, int $boxId): RedirectResponse
    {
        $box = MysteryBox::query()->findOrFail($boxId);
        $data = $this->normalizeItemRules($this->validateItem($request));
        $data['mystery_box_id'] = $box->id;
        $data['sort_order'] = $this->resolveSortOrder($box, $data['sort_order'] ?? null);
        $data = array_merge($data, $this->deriveDisplayFields($box, $data));
        $this->assertActiveWeightBudget($box, (int) $data['drop_weight']);

        if ($imagePath = $this->storeUploadedImage($request)) {
            $data['image'] = $imagePath;
        }

        MysteryBoxItem::query()->create($data);

        return back()->with('status', 'Item added');
    }

    public function update(Request $request, int $boxId, int $itemId): RedirectResponse
    {
        $box = MysteryBox::query()->findOrFail($boxId);
        $item = MysteryBoxItem::query()->where('mystery_box_id', $box->id)->findOrFail($itemId);
        $data = $this->normalizeItemRules($this->validateItem($request));
        $data['sort_order'] = $this->resolveSortOrder($box, $data['sort_order'] ?? null, $item);
        $data = array_merge($data, $this->deriveDisplayFields($box, array_merge($item->toArray(), $data)));
        $this->assertActiveWeightBudget($box, (int) $data['drop_weight'], $item);

        if ($request->boolean('remove_image')) {
            $this->deleteStoredImage($item->image);
            $data['image'] = null;
        }

        if ($imagePath = $this->storeUploadedImage($request)) {
            $this->deleteStoredImage($item->image);
            $data['image'] = $imagePath;
        }

        $item->fill($data)->save();

        return back()->with('status', 'Item saved');
    }

    public function duplicate(int $boxId, int $itemId): RedirectResponse
    {
        $box = MysteryBox::query()->findOrFail($boxId);
        $item = MysteryBoxItem::query()->where('mystery_box_id', $box->id)->findOrFail($itemId);

        $copy = $item->replicate([
            'created_at',
            'updated_at',
            'archived_at',
        ]);

        $copy->name = $item->name.' Copy';
        $copy->sort_order = $this->resolveSortOrder($box, null);
        $copy->archived_at = null;
        $copy->is_active = true;
        $copy->save();

        return back()->with('status', 'Item duplicated');
    }

    public function duplicateFromBox(Request $request, int $boxId): RedirectResponse
    {
        $box = MysteryBox::query()->findOrFail($boxId);

        $data = $request->validate([
            'source_box_id' => ['required', 'integer', 'exists:mystery_boxes,id'],
        ]);

        $sourceId = (int) $data['source_box_id'];
        if ($sourceId === (int) $box->id) {
            return back()->with('status', 'Choose a different box to import from');
        }

        $source = MysteryBox::query()->findOrFail($sourceId);
        $sourceItems = $source->items()->orderBy('sort_order')->orderBy('id')->get();

        $nextSort = (int) ($box->items()->max('sort_order') ?? -1) + 1;

        foreach ($sourceItems as $sourceItem) {
            $copy = $sourceItem->replicate([
                'created_at',
                'updated_at',
                'archived_at',
            ]);

            $copy->mystery_box_id = $box->id;
            $copy->name = $sourceItem->name;
            $copy->sort_order = $nextSort++;
            $copy->archived_at = null;
            $copy->is_active = true;
            $copy->save();
        }

        return back()->with('status', 'Items imported');
    }

    public function delete(int $boxId, int $itemId): RedirectResponse
    {
        $box = MysteryBox::query()->findOrFail($boxId);
        $item = MysteryBoxItem::query()->where('mystery_box_id', $box->id)->findOrFail($itemId);

        if ($item->userInventoryItems()->exists() || $item->spins()->exists()) {
            $item->is_active = false;
            $item->archived_at = now();
            $item->save();

            return back()->with('status', 'Item archived to preserve history');
        }

        $this->deleteStoredImage($item->image);
        $item->delete();

        return back()->with('status', 'Item deleted');
    }

    private function validateItem(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:140'],
            // Support modern image formats like AVIF even when PHP can't decode them server-side.
            // We only store and serve the file; no server-side image processing is required.
            'image_upload' => ['nullable', File::types([
                'jpg',
                'jpeg',
                'png',
                'gif',
                'webp',
                'avif',
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
                'image/avif',
            ])->max(5 * 1024)],
            'item_type' => ['required', 'string', 'in:coupon,digital,physical,jackpot'],
            'drop_weight' => ['required', 'integer', 'min:0', 'max:100'],
            'sell_value_credits' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ]);
    }

    private function normalizeItemRules(array $data): array
    {
        // These policy-heavy fields exist for compatibility but are intentionally not editable in "simple" admin UX.
        // We null them out so items default to the box-level policy.
        $data['eligible_credit_sources'] = null;
        $data['eligible_spin_ranges'] = null;
        $data['daily_limit'] = null;
        $data['lifetime_limit'] = null;
        $data['min_account_age_hours'] = null;
        $data['min_real_spend'] = null;
        $data['max_repeat_per_day'] = null;
        $data['is_onboarding_only'] = false;
        $data['is_returning_user_only'] = false;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{rarity: string, value_tier: string}
     */
    private function deriveDisplayFields(MysteryBox $box, array $data): array
    {
        $type = (string) ($data['item_type'] ?? 'digital');
        $value = (float) ($data['sell_value_credits'] ?? 0);
        $price = max(0.01, (float) $box->price_credits);

        $valueTier = match ($type) {
            'jackpot' => 'jackpot',
            default => $value >= ($price * 0.75)
                ? 'high'
                : ($value >= ($price * 0.25) ? 'mid' : 'low'),
        };

        $rarity = match ($valueTier) {
            'jackpot' => 'legendary',
            'high' => 'epic',
            'mid' => 'rare',
            default => 'common',
        };

        return [
            'rarity' => $rarity,
            'value_tier' => $valueTier,
            // We use a single public "price" field. Keep the legacy column synced for compatibility.
            'estimated_value_credits' => $value,
        ];
    }

    private function deleteStoredImage(?string $path): void
    {
        if (! $path || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return;
        }

        $absolutePath = $disk->path($path);

        $disk->delete($path);

        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    /**
     * @throws ValidationException
     */
    private function storeUploadedImage(Request $request): ?string
    {
        $file = $request->file('image_upload');

        if (! $file) {
            return null;
        }

        if (! $file->isValid()) {
            throw ValidationException::withMessages([
                'image_upload' => 'The uploaded image could not be processed. Please choose the file again.',
            ]);
        }

        return $file->storePublicly('box-items', 'public');
    }

    private function resolveSortOrder(MysteryBox $box, mixed $sortOrder, ?MysteryBoxItem $item = null): int
    {
        if ($sortOrder !== null && $sortOrder !== '') {
            return (int) $sortOrder;
        }

        if ($item?->exists) {
            return (int) $item->sort_order;
        }

        return (int) ($box->items()->max('sort_order') ?? -1) + 1;
    }

    private function assertActiveWeightBudget(MysteryBox $box, int $candidateWeight, ?MysteryBoxItem $item = null): void
    {
        $currentActiveWeight = (int) $box->items()
            ->where('is_active', true)
            ->whereNull('archived_at')
            ->when($item, fn ($query) => $query->whereKeyNot($item->id))
            ->sum('drop_weight');

        $nextActiveWeight = $currentActiveWeight + max(0, $candidateWeight);

        if ($nextActiveWeight > 100) {
            throw ValidationException::withMessages([
                'drop_weight' => 'Active item weights cannot exceed 100 total for a box.',
            ]);
        }
    }
}
