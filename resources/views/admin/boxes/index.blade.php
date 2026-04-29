<x-layouts.app>
    <div class="mx-auto w-full max-w-6xl">
        <div class="flex items-end justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Boxes</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">Manage mystery boxes.</p>
            </div>
            <div class="flex gap-3">
                <flux:button href="{{ route('admin.payment-settings') }}" variant="outline">Payment</flux:button>
                <flux:button href="{{ route('admin.boxes.create') }}" variant="primary">New box</flux:button>
            </div>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">
                {{ session('status') }}
            </div>
        @endif

        <div class="mt-6 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-800/60 dark:text-zinc-300">
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Slug</th>
                        <th class="px-4 py-3">Price</th>
                        <th class="px-4 py-3">Items</th>
                        <th class="px-4 py-3">RTP</th>
                        <th class="px-4 py-3">Active</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($boxes as $b)
                        <tr>
                            <td class="px-4 py-3 font-semibold">{{ $b->name }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ $b->slug }}</td>
                            <td class="px-4 py-3">${{ number_format((float) $b->price_credits, 2) }}</td>
                            <td class="px-4 py-3">{{ $b->items->count() }}</td>
                            <td class="px-4 py-3 text-xs text-zinc-500">
                                {{ $b->rewardProfile ? number_format((float) $b->rewardProfile->target_rtp_min, 0).'% - '.number_format((float) $b->rewardProfile->target_rtp_max, 0).'%' : 'Default' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="{{ $b->is_active ? 'text-emerald-600' : 'text-zinc-500' }}">{{ $b->is_active ? 'YES' : 'NO' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <flux:button href="{{ route('admin.boxes.edit', $b->id) }}" variant="ghost">Edit</flux:button>
                                    <flux:button href="{{ route('admin.items', $b->id) }}" variant="outline">Items</flux:button>
                                    <form method="POST" action="{{ route('admin.boxes.duplicate', $b->id) }}">
                                        @csrf
                                        <flux:button type="submit" variant="outline">Duplicate</flux:button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.boxes.toggle', $b->id) }}">
                                        @csrf
                                        <flux:button type="submit" variant="{{ $b->is_active ? 'danger' : 'primary' }}">{{ $b->is_active ? 'Disable' : 'Enable' }}</flux:button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
