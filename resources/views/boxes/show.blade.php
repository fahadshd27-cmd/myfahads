<x-layouts.site>
    @php($wallet = auth()->user()->wallet)
    @php($totalBalance = (float) ($wallet?->balance_credits ?? 0))
    @php($boxPrice = (float) $box->price_credits)
    @php($hasEnoughBalance = $totalBalance >= $boxPrice)

    <div class="mx-auto w-full max-w-6xl">
        <div class="flex flex-col gap-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <flux:button href="{{ route('boxes.index') }}" variant="ghost">Back to boxes</flux:button>
                    <h1 class="mt-3 text-2xl font-semibold">{{ $box->name }}</h1>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $box->description }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-zinc-50 px-3 py-2 text-sm font-semibold text-zinc-900 dark:bg-zinc-800 dark:text-zinc-50">
                        ${{ number_format($boxPrice, 2) }}
                    </div>
                    <flux:button href="{{ route('wallet') }}" variant="outline">Wallet</flux:button>
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex flex-col gap-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold">Spinner</h2>
                        <div class="text-right text-sm text-zinc-500">
                            <div>Credits: <span id="balance" class="font-semibold">{{ number_format($totalBalance, 2) }}</span></div>
                        </div>
                    </div>

                    @unless ($hasEnoughBalance)
                        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700/60 dark:bg-amber-900/20 dark:text-amber-100">
                            You need at least ${{ number_format($boxPrice, 2) }} to open this box. Add credits first and then come back to spin.
                        </div>
                    @endunless

                    <div class="relative overflow-hidden rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                        <div class="pointer-events-none absolute inset-y-0 left-1/2 w-0 border-l-2 border-violet-500"></div>
                        <div id="reel" class="flex gap-3 will-change-transform">
                            @for ($i = 0; $i < 20; $i++)
                                @php($reelItem = $items->isNotEmpty() ? $items[$i % $items->count()] : null)
                                <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-white text-zinc-400 shadow-sm dark:bg-zinc-900 dark:text-zinc-500">
                                    @if ($reelItem?->imageUrl())
                                        <img src="{{ $reelItem->imageUrl() }}" alt="{{ $reelItem->name }}" class="h-full w-full object-contain p-2" />
                                    @elseif ($reelItem)
                                        <span class="text-xs font-semibold">{{ \Illuminate\Support\Str::substr($reelItem->name, 0, 1) }}</span>
                                    @else
                                        G
                                    @endif
                                </div>
                            @endfor
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <flux:button
                            id="open-btn"
                            variant="primary"
                            data-box-slug="{{ $box->slug }}"
                            data-box-price="{{ number_format($boxPrice, 2, '.', '') }}"
                            data-wallet-url="{{ route('wallet', ['deposit' => 1, 'box' => $box->slug]) }}"
                        >
                            {{ $hasEnoughBalance ? 'Open' : 'Add credits to open' }}
                        </flux:button>
                        <input id="client-seed" type="text" placeholder="Client seed (optional)" class="w-72 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900" />
                        <div id="open-status" class="text-sm text-zinc-500">
                            @unless ($hasEnoughBalance)
                                Redirects to deposit
                            @endunless
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold">Items in this box</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($items as $item)
                        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                            <div class="flex items-center justify-between">
                                <div class="text-xs uppercase tracking-wide text-zinc-500">{{ $item->rarity }}</div>
                                <div class="rounded-md bg-zinc-50 px-2 py-1 text-xs font-semibold dark:bg-zinc-800">
                                    ${{ number_format((float) $item->sell_value_credits, 2) }}
                                </div>
                            </div>
                            <div class="mt-3 h-28 overflow-hidden rounded-lg bg-zinc-50 dark:bg-zinc-800/50">
                                @if ($item->imageUrl())
                                    <img src="{{ $item->imageUrl() }}" alt="{{ $item->name }}" class="h-full w-full object-contain p-2" />
                                @else
                                    <div class="flex h-full items-center justify-center text-xs text-zinc-500">No image</div>
                                @endif
                            </div>
                            <div class="mt-3 text-sm font-semibold">{{ $item->name }}</div>
                            <div class="mt-1 text-xs text-zinc-500">Sell: ${{ number_format((float) $item->sell_value_credits, 2) }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <dialog id="win-dialog" class="w-full max-w-md rounded-xl bg-white p-0 shadow-xl backdrop:bg-black/40 dark:bg-zinc-900">
        <div class="p-6">
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-sm text-zinc-500">You won</div>
                    <div id="win-name" class="text-xl font-semibold"></div>
                </div>
                <button type="button" class="rounded-lg px-2 py-1 text-sm text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800" onclick="document.getElementById('win-dialog').close()">
                    Close
                </button>
            </div>

            <div class="mt-4 h-40 overflow-hidden rounded-lg bg-zinc-50 dark:bg-zinc-800/50">
                <img id="win-image" alt="" class="h-full w-full object-contain p-4" />
            </div>

            <div class="mt-4 flex items-center justify-between text-sm">
                <span class="text-zinc-500">Price</span>
                <span id="win-sell" class="font-semibold"></span>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <flux:button id="act-save" variant="outline">Add to inventory</flux:button>
                <flux:button id="act-sell" variant="primary">Sell</flux:button>
            </div>

            <div id="win-msg" class="mt-3 text-sm text-zinc-500"></div>
        </div>
    </dialog>
</x-layouts.site>
