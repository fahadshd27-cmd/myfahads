<x-layouts.app>
    <div class="mx-auto w-full max-w-[96rem]">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold">Items: {{ $box->name }}</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">Manage item pool, value tiers, source rules, and repeat protection.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <flux:button href="{{ route('admin.boxes.edit', $box->id) }}" variant="outline">Box settings</flux:button>
                <flux:button href="{{ route('admin.boxes') }}" variant="ghost">All boxes</flux:button>
                @if (($otherBoxes ?? collect())->isNotEmpty())
                    <form method="POST" action="{{ route('admin.items.duplicate-from', $box->id) }}" class="flex items-center gap-2">
                        @csrf
                        <select name="source_box_id" class="h-9 rounded-lg border border-zinc-200 bg-white px-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                            @foreach ($otherBoxes as $other)
                                <option value="{{ $other->id }}">Import from: {{ $other->name }}</option>
                            @endforeach
                        </select>
                        <flux:button type="submit" variant="outline">Import items</flux:button>
                    </form>
                @endif
            </div>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-200">
                <p class="font-medium">The item could not be saved yet.</p>
                <ul class="mt-2 list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(24rem,28rem)_minmax(0,1fr)] 2xl:grid-cols-[minmax(26rem,30rem)_minmax(0,1fr)]">
            <div class="self-start rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 xl:sticky xl:top-6">
                <h2 class="text-lg font-semibold">{{ $editingItem ? 'Edit item' : 'Add item' }}</h2>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">Define display data plus the rules that control when this item can appear.</p>

                @php($formItem = $editingItem ?? null)
                <form
                    method="POST"
                    enctype="multipart/form-data"
                    action="{{ $editingItem ? route('admin.items.update', [$box->id, $editingItem->id]) : route('admin.items.store', $box->id) }}"
                    class="mt-4 grid gap-4"
                >
                    @csrf

                    <div>
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Item name</label>
                        <input name="name" value="{{ old('name', $formItem?->name) }}" placeholder="Starter Sticker" class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900" />
                        @error('name')
                            <p class="mt-1 text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ $editingItem ? 'Replace image' : 'Item image' }}</label>
                        <input name="image_upload" type="file" accept="image/*" class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900" />
                        @error('image_upload')
                            <p class="mt-1 text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>
                        @enderror
                        @if ($editingItem && $editingItem->imageUrl())
                            <div class="mt-3 flex items-center gap-4 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                                <img src="{{ $editingItem->imageUrl() }}" alt="{{ $editingItem->name }}" class="h-16 w-16 rounded-lg object-cover" />
                                <label class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                                    <input name="remove_image" type="checkbox" value="1" />
                                    Remove current image
                                </label>
                            </div>
                        @endif
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Item type</label>
                            <select name="item_type" class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                                @foreach (['coupon', 'digital', 'physical', 'jackpot'] as $type)
                                    <option value="{{ $type }}" @selected(old('item_type', $formItem?->item_type ?? 'digital') === $type)>{{ ucfirst($type) }}</option>
                                @endforeach
                            </select>
                            @error('item_type')
                                <p class="mt-1 text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Status</label>
                            <div class="mt-2 flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                                <input name="is_active" type="checkbox" value="1" @checked(old('is_active', $formItem?->is_active ?? true)) />
                                Active and eligible
                            </div>
                            <p class="mt-1 text-xs text-zinc-500">Rarity and tiers are auto-derived from item value vs box price.</p>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Drop weight</label>
                            <input name="drop_weight" type="number" min="0" value="{{ old('drop_weight', $formItem?->drop_weight ?? 1) }}" class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900" />
                            @error('drop_weight')
                                <p class="mt-1 text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Sort order</label>
                            <input name="sort_order" type="number" min="0" value="{{ old('sort_order', $formItem?->sort_order) }}" class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900" />
                            @error('sort_order')
                                <p class="mt-1 text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Item price (credits)</label>
                        <input name="sell_value_credits" type="number" step="0.01" min="0" value="{{ old('sell_value_credits', $formItem?->sell_value_credits ?? 0) }}" class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900" />
                        @error('sell_value_credits')
                            <p class="mt-1 text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-zinc-500">This is the single price used for economy targeting and for “Sell”.</p>
                    </div>

                    <div class="flex flex-wrap justify-end gap-3">
                        @if ($editingItem)
                            <flux:button href="{{ route('admin.items', $box->id) }}" variant="ghost">Cancel</flux:button>
                        @endif
                        <flux:button type="submit" variant="primary">{{ $editingItem ? 'Save changes' : 'Add item' }}</flux:button>
                    </div>
                </form>
            </div>

            <div class="min-w-0 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold">Existing items</h2>
                <div class="mt-4 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                    <div class="overflow-x-auto">
                        <table class="min-w-[56rem] w-full text-left text-sm">
                            <thead class="bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-800/60 dark:text-zinc-300">
                                <tr>
                                    <th class="w-[44%] px-4 py-3">Item</th>
                                    <th class="w-[10%] px-4 py-3">Type</th>
                                    <th class="w-[8%] px-4 py-3">Tier</th>
                                    <th class="w-[8%] px-4 py-3">Weight</th>
                                    <th class="w-[10%] px-4 py-3">Sell</th>
                                    <th class="w-[8%] px-4 py-3">Status</th>
                                    <th class="w-[12%] px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                @forelse ($items as $item)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-3">
                                                <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                                                    @if ($item->imageUrl())
                                                        <img src="{{ $item->imageUrl() }}" alt="{{ $item->name }}" class="h-full w-full object-cover" />
                                                    @else
                                                        <span class="text-xs text-zinc-500">No image</span>
                                                    @endif
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="truncate font-medium">{{ $item->name }}</div>
                                                    <div class="truncate text-xs text-zinc-500">${{ number_format((float) $item->sell_value_credits, 2) }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 align-top">{{ $item->item_type }}</td>
                                        <td class="px-4 py-3 align-top">{{ $item->value_tier }}</td>
                                        <td class="px-4 py-3 align-top">{{ number_format((int) $item->drop_weight) }}</td>
                                        <td class="px-4 py-3 align-top">${{ number_format((float) $item->sell_value_credits, 2) }}</td>
                                        <td class="px-4 py-3 align-top">
                                            <span class="{{ $item->is_active && ! $item->archived_at ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-500' }}">
                                                {{ $item->archived_at ? 'Archived' : ($item->is_active ? 'Active' : 'Inactive') }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 align-top">
                                            <div class="flex justify-end gap-2 whitespace-nowrap">
                                                <flux:button href="{{ route('admin.items', ['boxId' => $box->id, 'edit' => $item->id]) }}" variant="outline" icon="pencil-square">Edit</flux:button>
                                                <form method="POST" action="{{ route('admin.items.duplicate', [$box->id, $item->id]) }}">
                                                    @csrf
                                                    <flux:button type="submit" variant="ghost" icon="document-duplicate">Duplicate</flux:button>
                                                </form>
                                                <form method="POST" action="{{ route('admin.items.delete', [$box->id, $item->id]) }}">
                                                    @csrf
                                                    <flux:button type="submit" variant="danger" icon="trash" onclick="return confirm('Delete or archive this item?')">Delete</flux:button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-6 text-center text-sm text-zinc-500">No items yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
