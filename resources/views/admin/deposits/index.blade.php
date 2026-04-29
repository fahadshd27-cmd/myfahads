<x-layouts.app>
    <div class="mx-auto w-full max-w-6xl">
        <div class="flex items-end justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Deposits</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">Latest deposit orders (max 100).</p>
            </div>
            <div class="flex gap-3">
                <flux:button href="{{ route('admin.payment-settings') }}" variant="outline">Payment settings</flux:button>
                <form method="POST" action="{{ route('admin.deposits.expire') }}">
                    @csrf
                    <flux:button type="submit" variant="ghost">Expire pending now</flux:button>
                </form>
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
                        <th class="px-4 py-3">Ref</th>
                        <th class="px-4 py-3">User</th>
                        <th class="px-4 py-3">Gateway</th>
                        <th class="px-4 py-3">Mode</th>
                        <th class="px-4 py-3">Amount</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Created</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($orders as $o)
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs">{{ $o->reference }}</td>
                            <td class="px-4 py-3">{{ $o->user?->email }}</td>
                            <td class="px-4 py-3">{{ strtoupper($o->gateway) }}</td>
                            <td class="px-4 py-3">{{ strtoupper($o->mode) }}</td>
                            <td class="px-4 py-3 font-semibold">${{ number_format((float) $o->amount_credits, 2) }}</td>
                            <td class="px-4 py-3">{{ strtoupper($o->status) }}</td>
                            <td class="px-4 py-3 text-xs text-zinc-500">{{ $o->created_at?->toDateTimeString() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
