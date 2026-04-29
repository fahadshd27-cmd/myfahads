<x-layouts.site>
    <div class="mx-auto w-full max-w-6xl">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold">Boxes</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">Open a box and win an item.</p>
            </div>
            <flux:button href="{{ route('wallet') }}" variant="outline">Wallet</flux:button>
        </div>

        <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($boxes as $box)
                <a href="{{ route('boxes.show', $box->slug) }}" class="group rounded-xl border border-zinc-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="truncate text-lg font-semibold">{{ $box->name }}</div>
                            <div class="mt-1 text-sm text-zinc-500">{{ \Illuminate\Support\Str::limit($box->description ?? '', 80) }}</div>
                        </div>
                        <div class="rounded-lg bg-zinc-50 px-3 py-2 text-sm font-semibold text-zinc-900 dark:bg-zinc-800 dark:text-zinc-50">
                            ${{ number_format((float) $box->price_credits, 2) }}
                        </div>
                    </div>

                    <div class="mt-4 overflow-hidden rounded-lg bg-zinc-50 dark:bg-zinc-800/50">
                        @if ($box->thumbnailUrl())
                            <img src="{{ $box->thumbnailUrl() }}" alt="{{ $box->name }}" class="h-40 w-full object-cover transition group-hover:scale-[1.02]" />
                        @else
                            <div class="flex h-40 items-center justify-center text-sm text-zinc-500">No thumbnail</div>
                        @endif
                    </div>
                </a>
            @empty
                <p class="text-sm text-zinc-500">No active boxes yet.</p>
            @endforelse
        </div>
    </div>
</x-layouts.site>
