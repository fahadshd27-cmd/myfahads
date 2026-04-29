<x-layouts.app>
    <div class="mx-auto w-full max-w-6xl">
        <div class="flex items-end justify-between">
            <div>
                <h1 class="text-2xl font-semibold">{{ $user->email }}</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">Status: {{ strtoupper($user->status ?? 'active') }} · Admin: {{ ($user->is_admin ?? false) ? 'YES' : 'NO' }}</p>
            </div>
            <flux:button href="{{ route('admin.users') }}" variant="outline">Back</flux:button>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">
                {{ session('status') }}
            </div>
        @endif

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold">Wallet</h2>
                <div class="mt-3 flex items-center justify-between">
                    <div class="text-sm text-zinc-500">Balance</div>
                    <div class="text-2xl font-semibold">${{ number_format((float) $wallet->balance_credits, 2) }}</div>
                </div>
                <div class="mt-2 text-xs text-zinc-500">
                    Promo ${{ number_format((float) $wallet->promo_credits, 2) }}
                    · Sale ${{ number_format((float) $wallet->sale_credits, 2) }}
                    · Real ${{ number_format((float) $wallet->real_money_credits, 2) }}
                </div>
                <div class="mt-5 grid gap-4">
                    <form method="POST" action="{{ route('admin.users.top-up', $user->id) }}" class="grid gap-2 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        @csrf
                        <div class="text-sm font-medium">Top up promo credits</div>
                        <input name="amount" type="number" step="0.01" min="0.01" placeholder="Amount" class="rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900" />
                        <input name="reason" type="text" placeholder="Reason" class="rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900" />
                        <div>
                            <flux:button type="submit" variant="primary">Top up</flux:button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('admin.users.refund', $user->id) }}" class="grid gap-2 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        @csrf
                        <div class="text-sm font-medium">Debit spendable credits</div>
                        <input name="amount" type="number" step="0.01" min="0.01" placeholder="Amount" class="rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900" />
                        <input name="reason" type="text" placeholder="Reason" class="rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900" />
                        <div>
                            <flux:button type="submit" variant="danger">Debit</flux:button>
                        </div>
                    </form>
                </div>
                <div class="mt-5 flex flex-wrap gap-3">
                    <form method="POST" action="{{ route('admin.users.toggle-suspend', $user->id) }}">
                        @csrf
                        <flux:button type="submit" variant="{{ ($user->status ?? 'active') === 'active' ? 'danger' : 'primary' }}">
                            {{ ($user->status ?? 'active') === 'active' ? 'Suspend' : 'Unsuspend' }}
                        </flux:button>
                    </form>
                    <form method="POST" action="{{ route('admin.users.toggle-admin', $user->id) }}">
                        @csrf
                        <flux:button type="submit" variant="outline">{{ ($user->is_admin ?? false) ? 'Remove admin' : 'Make admin' }}</flux:button>
                    </form>
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold">Recent Transactions</h2>
                <div class="mt-4 grid gap-3">
                    @forelse ($transactions as $t)
                        <div class="flex items-center justify-between rounded-lg border border-zinc-200 px-4 py-3 text-sm dark:border-zinc-700">
                            <div class="flex flex-col">
                                <span class="text-zinc-600 dark:text-zinc-300">{{ $t->type }}</span>
                                <span class="font-mono text-xs text-zinc-500">{{ $t->created_at->toDateTimeString() }} · {{ strtoupper($t->bucket ?? 'mixed') }}</span>
                            </div>
                            <div class="{{ (float) $t->amount >= 0 ? 'text-emerald-600' : 'text-rose-600' }} font-semibold">
                                {{ (float) $t->amount >= 0 ? '+' : '' }}${{ number_format((float) $t->amount, 2) }}
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500">No transactions.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold">Recent Spins</h2>
                <div class="mt-4 grid gap-3">
                    @forelse ($spins as $spin)
                        <div class="rounded-lg border border-zinc-200 px-4 py-3 text-sm dark:border-zinc-700">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="font-medium">{{ $spin->box?->name ?? 'Unknown box' }}</div>
                                    <div class="text-xs text-zinc-500">{{ $spin->created_at->toDateTimeString() }}</div>
                                </div>
                                <div class="text-right text-xs text-zinc-500">
                                    <div>{{ strtoupper((string) data_get($spin->meta, 'funding.primary_bucket', 'n/a')) }}</div>
                                    <div>{{ $spin->resultItem?->name }}</div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500">No spins yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold">Box Progress</h2>
                <div class="mt-4 grid gap-3">
                    @forelse ($progressEntries as $entry)
                        <div class="rounded-lg border border-zinc-200 px-4 py-3 text-sm dark:border-zinc-700">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="font-medium">{{ $entry->box?->name ?? 'Unknown box' }}</div>
                                    <div class="text-xs text-zinc-500">{{ $entry->local_day ?? 'No local day' }}</div>
                                </div>
                                <div class="text-right text-xs text-zinc-500">
                                    <div>Daily spins: {{ $entry->daily_spin_count }}</div>
                                    <div>Pity: {{ $entry->consecutive_low_tier_spins }}</div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500">No progress yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
