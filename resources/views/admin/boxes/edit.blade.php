<x-layouts.app>
    @php($profile = $box->rewardProfile)

    <div class="mx-auto w-full max-w-5xl">
        <div class="flex items-end justify-between">
            <div>
                <h1 class="text-2xl font-semibold">{{ $box->exists ? 'Edit Box' : 'Create Box' }}</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">Configure pricing, policy guardrails, and progression behavior.</p>
            </div>
            <div class="flex gap-3">
                <flux:button href="{{ route('admin.boxes') }}" variant="outline">Back</flux:button>
                @if ($box->exists)
                    <flux:button href="{{ route('admin.items', $box->id) }}" variant="ghost">Manage items</flux:button>
                @endif
            </div>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" enctype="multipart/form-data" action="{{ $box->exists ? route('admin.boxes.update', $box->id) : route('admin.boxes.store') }}" class="mt-6 grid gap-6">
            @csrf

            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold">Box Settings</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="text-sm text-zinc-600 dark:text-zinc-300">Name</label>
                        <input name="name" value="{{ old('name', $box->name) }}" class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="text-sm text-zinc-600 dark:text-zinc-300">Slug</label>
                        <input name="slug" value="{{ old('slug', $box->slug) }}" class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900" placeholder="optional" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="text-sm text-zinc-600 dark:text-zinc-300">Description</label>
                        <textarea name="description" rows="4" class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">{{ old('description', $box->description) }}</textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="text-sm text-zinc-600 dark:text-zinc-300">Thumbnail image</label>
                        <input name="thumbnail_upload" type="file" accept="image/*" class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900" />
                        @if ($box->thumbnailUrl())
                            <div class="mt-3 flex items-center gap-4 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                                <img src="{{ $box->thumbnailUrl() }}" alt="{{ $box->name }}" class="h-20 w-20 rounded-lg object-cover" />
                                <label class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                                    <input name="remove_thumbnail" type="checkbox" value="1" />
                                    Remove current thumbnail
                                </label>
                            </div>
                        @endif
                    </div>
                    <div>
                        <label class="text-sm text-zinc-600 dark:text-zinc-300">Price credits</label>
                        <input name="price_credits" type="number" step="0.01" min="0.01" value="{{ old('price_credits', $box->price_credits) }}" class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900" />
                    </div>
                    <div>
                        <label class="text-sm text-zinc-600 dark:text-zinc-300">Sort order</label>
                        <input name="sort_order" type="number" min="0" value="{{ old('sort_order', $box->sort_order) }}" class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900" />
                    </div>
                    <div class="sm:col-span-2 flex flex-wrap gap-6">
                        <label class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                            <input id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $box->is_active)) />
                            Active
                        </label>
                        <label class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                            <input name="requires_real_money_only" type="checkbox" value="1" @checked(old('requires_real_money_only', $box->requires_real_money_only)) />
                            Real-money credits only
                        </label>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold">Economy Policy</h2>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">Pick a profile and let the backend auto-manage 24h returns.</p>
                    </div>
                    @if ($box->exists)
                        <div class="rounded-lg bg-zinc-50 px-3 py-2 text-xs text-zinc-500 dark:bg-zinc-800/70">
                            Item count: {{ $box->items()->count() }}
                        </div>
                    @endif
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <input type="hidden" name="economy_mode" value="simple" />
                    <div>
                        <label class="text-sm text-zinc-600 dark:text-zinc-300">Simple profile</label>
                        <select name="economy_profile" class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                            @foreach (array_keys(config('spinner.simple_profiles', ['normal' => []])) as $key)
                                <option value="{{ $key }}" @selected(old('economy_profile', $profile?->economy_profile ?? 'normal') === $key)>{{ ucfirst($key) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-sm text-zinc-600 dark:text-zinc-300">Enable jackpot-tier items</label>
                        <div class="mt-2">
                            <label class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                                <input type="checkbox" name="jackpot_enabled" value="1" @checked(old('jackpot_enabled', $profile?->jackpot_enabled ?? true)) />
                                Jackpot enabled
                            </label>
                        </div>
                    </div>
                </div>

                <p class="mt-4 text-xs text-zinc-500">
                    Advanced guardrails (24h window, payout caps, repeat/recovery thresholds) are auto-managed by the selected simple profile in <span class="font-mono">config/spinner.php</span>.
                </p>

                <div class="mt-4">
                    <div>
                        <label class="text-sm text-zinc-600 dark:text-zinc-300">Allowed credit sources</label>
                        <div class="mt-2 flex flex-wrap gap-4 text-sm text-zinc-600 dark:text-zinc-300">
                            @foreach (['promo', 'sale', 'real_money'] as $source)
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="eligible_credit_sources[]" value="{{ $source }}" @checked(in_array($source, old('eligible_credit_sources', $profile?->eligible_credit_sources ?? ['promo', 'sale', 'real_money']), true)) />
                                    {{ strtoupper(str_replace('_', ' ', $source)) }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </div>
</x-layouts.app>
