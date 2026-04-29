<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MysteryBox;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;

class AdminBoxController extends Controller
{
    public function index(): View
    {
        $boxes = MysteryBox::query()
            ->with(['rewardProfile', 'items'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('admin.boxes.index', ['boxes' => $boxes]);
    }

    public function create(): View
    {
        return view('admin.boxes.edit', ['box' => new MysteryBox]);
    }

    public function edit(int $id): View
    {
        $box = MysteryBox::query()->with('rewardProfile')->findOrFail($id);

        return view('admin.boxes.edit', ['box' => $box]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateBox($request);
        $data['sort_order'] = $this->resolveSortOrder($data['sort_order'] ?? null);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']).'-'.Str::lower(Str::random(4));
        }

        if ($request->hasFile('thumbnail_upload')) {
            $data['thumbnail'] = $request->file('thumbnail_upload')->store('boxes', 'public');
        }

        $box = MysteryBox::query()->create($data);
        $this->saveRewardProfile($box, $request);

        return redirect()->route('admin.boxes.edit', $box->id)->with('status', 'Box created');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $box = MysteryBox::query()->findOrFail($id);
        $data = $this->validateBox($request, $box->id);
        $data['sort_order'] = $this->resolveSortOrder($data['sort_order'] ?? null, $box);

        if ($request->boolean('remove_thumbnail')) {
            $this->deleteStoredImage($box->thumbnail);
            $data['thumbnail'] = null;
        }

        if ($request->hasFile('thumbnail_upload')) {
            $this->deleteStoredImage($box->thumbnail);
            $data['thumbnail'] = $request->file('thumbnail_upload')->store('boxes', 'public');
        }

        $box->fill($data)->save();
        $this->saveRewardProfile($box, $request);

        return back()->with('status', 'Saved');
    }

    public function toggle(int $id): RedirectResponse
    {
        $box = MysteryBox::query()->findOrFail($id);
        $box->is_active = ! $box->is_active;
        $box->save();

        return back()->with('status', 'Updated status');
    }

    public function duplicate(int $id): RedirectResponse
    {
        $box = MysteryBox::query()
            ->with(['rewardProfile', 'items'])
            ->findOrFail($id);

        $copy = $box->replicate(['created_at', 'updated_at']);
        $copy->name = $box->name.' Copy';
        $copy->slug = Str::slug($copy->name).'-'.Str::lower(Str::random(4));
        $copy->is_active = false;
        $copy->sort_order = (int) (MysteryBox::query()->max('sort_order') ?? -1) + 1;
        $copy->save();

        if ($box->rewardProfile) {
            $profileCopy = $box->rewardProfile->replicate(['created_at', 'updated_at']);
            $profileCopy->mystery_box_id = $copy->id;
            $profileCopy->save();
        }

        $nextSort = 0;
        foreach ($box->items as $item) {
            $itemCopy = $item->replicate(['created_at', 'updated_at', 'archived_at']);
            $itemCopy->mystery_box_id = $copy->id;
            $itemCopy->sort_order = $nextSort++;
            $itemCopy->archived_at = null;
            $itemCopy->is_active = true;
            $itemCopy->save();
        }

        return redirect()->route('admin.boxes.edit', $copy->id)->with('status', 'Box duplicated');
    }

    private function validateBox(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', 'unique:mystery_boxes,slug'.($ignoreId ? ','.$ignoreId : '')],
            'description' => ['nullable', 'string', 'max:2000'],
            // Allow AVIF uploads for box thumbnails as well.
            'thumbnail_upload' => ['nullable', File::types([
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
            'price_credits' => ['required', 'numeric', 'min:0.01'],
            'requires_real_money_only' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function saveRewardProfile(MysteryBox $box, Request $request): void
    {
        $simpleProfiles = array_keys((array) config('spinner.simple_profiles', []));
        $existingProfile = $box->rewardProfile()->first();

        // Keep admin configuration small for the competitor-style "simple" economy:
        // profile + allowed funding sources + jackpot on/off. Everything else is auto-managed via config defaults.
        $profileData = $request->validate([
            'economy_mode' => ['nullable', 'string', 'in:simple,advanced'],
            'economy_profile' => ['nullable', 'string', 'in:'.implode(',', $simpleProfiles ?: ['normal'])],
            'eligible_credit_sources' => ['nullable', 'array'],
            'eligible_credit_sources.*' => ['string', 'in:promo,sale,real_money'],
            'jackpot_enabled' => ['nullable', 'boolean'],
        ]);

        $economyMode = $profileData['economy_mode'] ?? $existingProfile?->economy_mode ?? 'simple';
        $economyProfileKey = $profileData['economy_profile'] ?? $existingProfile?->economy_profile ?? 'normal';
        $simpleDefaults = (array) config('spinner.simple_profiles.'.$economyProfileKey, config('spinner.simple_profiles.normal', []));

        $box->rewardProfile()->updateOrCreate(
            ['mystery_box_id' => $box->id],
            [
                'economy_mode' => $economyMode,
                'economy_profile' => $economyMode === 'simple' ? $economyProfileKey : null,
                'window_hours' => $existingProfile?->window_hours ?? ($simpleDefaults['window_hours'] ?? 24),
                'max_payout_percent' => $existingProfile?->max_payout_percent ?? ($simpleDefaults['max_payout_percent'] ?? 70),
                'repeat_same_box_after_spins' => $existingProfile?->repeat_same_box_after_spins ?? ($simpleDefaults['repeat_same_box_after_spins'] ?? 3),
                'recovery_after_net_loss_percent' => $existingProfile?->recovery_after_net_loss_percent ?? ($simpleDefaults['recovery_after_net_loss_percent'] ?? 150),
                'first_spin_min_percent' => data_get($simpleDefaults, 'first_spin.0', 5),
                'first_spin_max_percent' => data_get($simpleDefaults, 'first_spin.1', 20),
                'first_box_spin_min_percent' => data_get($simpleDefaults, 'first_box_spin.0', 10),
                'first_box_spin_max_percent' => data_get($simpleDefaults, 'first_box_spin.1', 35),
                'normal_spin_min_percent' => data_get($simpleDefaults, 'normal_spin.0', 10),
                'normal_spin_max_percent' => data_get($simpleDefaults, 'normal_spin.1', 30),
                'repeat_spin_min_percent' => data_get($simpleDefaults, 'repeat_spin.0', 5),
                'repeat_spin_max_percent' => data_get($simpleDefaults, 'repeat_spin.1', 15),
                'recovery_spin_min_percent' => data_get($simpleDefaults, 'recovery_spin.0', 35),
                'recovery_spin_max_percent' => data_get($simpleDefaults, 'recovery_spin.1', 60),
                // Advanced RTP fields remain stored, but we keep them stable by default (simple mode uses payout bands).
                'target_rtp_min' => $existingProfile?->target_rtp_min ?? 30,
                'target_rtp_max' => $existingProfile?->target_rtp_max ?? 85,
                'eligible_credit_sources' => $profileData['eligible_credit_sources'] ?? $existingProfile?->eligible_credit_sources ?? [
                    'promo',
                    'sale',
                    'real_money',
                ],
                // Keep these advanced fields stable (ignored in simple mode).
                'onboarding_max_spins' => $existingProfile?->onboarding_max_spins ?? 3,
                'onboarding_max_account_age_hours' => $existingProfile?->onboarding_max_account_age_hours ?? 48,
                'onboarding_item_types' => $existingProfile?->onboarding_item_types ?? ['coupon'],
                'pity_after_spins' => $existingProfile?->pity_after_spins ?? 3,
                'pity_multiplier' => $existingProfile?->pity_multiplier ?? 2,
                'daily_progress_after_spins' => $existingProfile?->daily_progress_after_spins ?? 6,
                'daily_progress_multiplier' => $existingProfile?->daily_progress_multiplier ?? 1.2,
                'daily_progress_cap' => $existingProfile?->daily_progress_cap ?? 2,
                'jackpot_enabled' => $request->has('jackpot_enabled')
                    ? $request->boolean('jackpot_enabled')
                    : ($existingProfile?->jackpot_enabled ?? true),
                'jackpot_max_wins_per_day' => $existingProfile?->jackpot_max_wins_per_day ?? 1,
                'jackpot_cooldown_spins' => $existingProfile?->jackpot_cooldown_spins ?? 0,
                'high_tier_value_threshold' => $existingProfile?->high_tier_value_threshold ?? 250,
            ],
        );
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

    private function resolveSortOrder(mixed $sortOrder, ?MysteryBox $box = null): int
    {
        if ($sortOrder !== null && $sortOrder !== '') {
            return (int) $sortOrder;
        }

        if ($box?->exists) {
            return (int) $box->sort_order;
        }

        return (int) (MysteryBox::query()->max('sort_order') ?? -1) + 1;
    }
}
