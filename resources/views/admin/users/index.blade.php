<x-layouts.app>
    <div class="mx-auto w-full max-w-6xl">
        <div class="flex items-end justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Users</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">Latest users (max 100).</p>
            </div>
            <flux:button href="{{ route('admin.boxes') }}" variant="outline">Boxes</flux:button>
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
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Admin</th>
                        <th class="px-4 py-3">Created</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($users as $u)
                        <tr>
                            <td class="px-4 py-3">{{ $u->email }}</td>
                            <td class="px-4 py-3">{{ strtoupper($u->status ?? 'active') }}</td>
                            <td class="px-4 py-3">{{ ($u->is_admin ?? false) ? 'YES' : 'NO' }}</td>
                            <td class="px-4 py-3 text-xs text-zinc-500">{{ $u->created_at?->toDateTimeString() }}</td>
                            <td class="px-4 py-3">
                                <flux:button href="{{ route('admin.users.show', $u->id) }}" variant="ghost">View</flux:button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
