<x-layouts.site>
    <div class="mx-auto w-full max-w-5xl">
        <div class="flex flex-col gap-6">
            <div class="flex flex-col gap-2 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold">Wallet</h1>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">Your credits balance and history.</p>
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

                @if (request()->boolean('deposit'))
                    <div class="mt-4 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900 dark:border-sky-700/60 dark:bg-sky-900/20 dark:text-sky-100">
                        Add credits to continue opening {{ request('box') ? 'the '.request('box').' box' : 'your selected box' }}.
                    </div>
                @endif

                <div class="mt-4 rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800/50">
                    <div class="mb-4 flex flex-wrap gap-3">
                        <flux:button href="{{ route('inventory.index') }}" variant="outline">Open inventory</flux:button>
                    </div>
                    <form id="deposit-form" class="grid gap-3 sm:grid-cols-3" method="POST" action="{{ route('deposits.create') }}">
                        @csrf
                        <div class="sm:col-span-1">
                            <label class="text-sm text-zinc-600 dark:text-zinc-300">Amount</label>
                            <input name="amount" type="number" step="1" min="1" value="25" class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900" />
                        </div>
                        <div class="sm:col-span-1">
                            <label class="text-sm text-zinc-600 dark:text-zinc-300">Method</label>
                            <select name="gateway" class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                                <option value="paylink">Card (Paylink)</option>
                                <option value="bitpay">Crypto (BitPay)</option>
                            </select>
                        </div>
                        <div class="sm:col-span-1 flex items-end">
                            <flux:button type="submit" variant="primary" class="w-full">Deposit</flux:button>
                        </div>
                    </form>
                    <p class="mt-2 text-xs text-zinc-500">
                        In testing mode, you’ll be sent to an internal checkout page where you can simulate Paid/Pending/Failed.
                    </p>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <h2 class="text-lg font-semibold">Recent Deposits</h2>
                    <div class="mt-4 grid gap-3">
                        @forelse ($deposits as $d)
                            <div class="flex items-center justify-between rounded-lg border border-zinc-200 px-4 py-3 text-sm dark:border-zinc-700">
                                <div class="flex flex-col">
                                    <span class="font-mono text-xs text-zinc-500">{{ $d->reference }}</span>
                                    <span class="text-zinc-600 dark:text-zinc-300">{{ strtoupper($d->gateway) }} · {{ strtoupper($d->status) }}</span>
                                </div>
                                <div class="font-semibold">${{ number_format((float) $d->amount_credits, 2) }}</div>
                            </div>
                        @empty
                            <p class="text-sm text-zinc-500">No deposits yet.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <h2 class="text-lg font-semibold">Transactions</h2>
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
                            <p class="text-sm text-zinc-500">No transactions yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.site>
