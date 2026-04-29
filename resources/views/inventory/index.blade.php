<x-layouts.site>
    <div class="mx-auto w-full max-w-6xl">
        <div class="flex flex-col gap-6">
            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-semibold">Inventory</h1>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">See every prize you won, decide what to sell, and track which rewards are still saved for future use.</p>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-zinc-500">Balance</div>
                        <div class="text-3xl font-semibold">${{ number_format((float) $wallet->balance_credits, 2) }}</div>
                        <div class="mt-2 text-xs text-zinc-500">
                            Promo ${{ number_format((float) $wallet->promo_credits, 2) }}
                            · Sale ${{ number_format((float) $wallet->sale_credits, 2) }}
                            · Real ${{ number_format((float) $wallet->real_money_credits, 2) }}
                        </div>
                    </div>
                </div>

                @if (session('status'))
                    <div class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">
                        {{ session('status') }}
                    </div>
                @endif
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @php($grouped = $items->getCollection()->groupBy('state'))
                @foreach ([['pending_decision', 'Pending decisions'], ['saved', 'Saved'], ['sold', 'Sold'], ['claimed', 'Claimed']] as [$state, $label])
                    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="text-xs uppercase tracking-wide text-zinc-500">{{ $label }}</div>
                        <div class="mt-2 text-2xl font-semibold">{{ $grouped->get($state, collect())->count() }}</div>
                    </div>
                @endforeach
            </div>

            <div class="grid gap-4">
                @forelse ($items as $inventoryItem)
                    @php($snapshot = $inventoryItem->item_snapshot ?? [])
                    <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex min-w-0 items-center gap-4">
                                <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                                    @if ($inventoryItem->item?->imageUrl())
                                        <img src="{{ $inventoryItem->item->imageUrl() }}" alt="{{ data_get($snapshot, 'name', $inventoryItem->item?->name) }}" class="h-full w-full object-cover" />
                                    @else
                                        <span class="text-xs text-zinc-500">No image</span>
                                    @endif
                                </div>

                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="truncate text-lg font-semibold">{{ data_get($snapshot, 'name', $inventoryItem->item?->name) }}</h2>
                                        <span class="rounded-full bg-zinc-100 px-2 py-1 text-[11px] uppercase tracking-wide text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                            {{ str_replace('_', ' ', $inventoryItem->state) }}
                                        </span>
                                    </div>
                                    <div class="mt-1 flex flex-wrap gap-3 text-sm text-zinc-500">
                                        <span>{{ strtoupper((string) data_get($snapshot, 'item_type', $inventoryItem->item?->item_type)) }}</span>
                                        <span>{{ strtoupper((string) data_get($snapshot, 'value_tier', $inventoryItem->item?->value_tier)) }}</span>
                                        <span>Won from {{ $inventoryItem->spin?->box?->name ?? 'Unknown box' }}</span>
                                        <span>{{ $inventoryItem->created_at->toDayDateTimeString() }}</span>
                                    </div>
                                    <div class="mt-2 flex flex-wrap gap-4 text-sm">
                                        <span class="text-zinc-500">Price: <span class="font-semibold text-zinc-900 dark:text-zinc-100">${{ number_format((float) data_get($snapshot, 'sell_value_credits', $inventoryItem->sell_amount_credits ?? 0), 2) }}</span></span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                @if ($inventoryItem->state === \App\Models\UserInventoryItem::STATE_PENDING)
                                    <form method="POST" action="{{ route('inventory.save', $inventoryItem->id) }}">
                                        @csrf
                                        <flux:button type="submit" variant="outline">Add to inventory</flux:button>
                                    </form>
                                    <form method="POST" action="{{ route('inventory.sell', $inventoryItem->id) }}">
                                        @csrf
                                        <flux:button type="submit" variant="primary">Sell now</flux:button>
                                    </form>
                                @elseif ($inventoryItem->state === \App\Models\UserInventoryItem::STATE_SAVED)
                                    <form method="POST" action="{{ route('inventory.claim', $inventoryItem->id) }}">
                                        @csrf
                                        <flux:button type="submit" variant="outline">Mark claimed</flux:button>
                                    </form>
                                    <form method="POST" action="{{ route('inventory.sell', $inventoryItem->id) }}">
                                        @csrf
                                        <flux:button type="submit" variant="primary">Sell from inventory</flux:button>
                                    </form>
                                @else
                                    <div class="rounded-lg border border-zinc-200 px-3 py-2 text-sm text-zinc-500 dark:border-zinc-700">
                                        No further actions
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-zinc-300 bg-white p-10 text-center text-sm text-zinc-500 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400">
                        No inventory items yet. Open a box first, then your wins will appear here.
                    </div>
                @endforelse
            </div>

            @if ($items->hasPages())
                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    {{ $items->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.site>
