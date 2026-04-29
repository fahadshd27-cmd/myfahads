<x-layouts.app>
    <div class="mx-auto w-full max-w-4xl">
        <div class="flex items-end justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Payment Settings</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">Testing vs production + gateway templates + webhook secrets.</p>
            </div>
            <flux:button href="{{ route('admin.deposits') }}" variant="outline">Deposits</flux:button>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.payment-settings.update') }}" class="mt-6 grid gap-6">
            @csrf
            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold">Global</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-sm text-zinc-600 dark:text-zinc-300">Mode</label>
                        <select name="mode" class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                            <option value="testing" @selected($mode === 'testing')>Testing</option>
                            <option value="production" @selected($mode === 'production')>Production</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm text-zinc-600 dark:text-zinc-300">Pending expiry minutes</label>
                        <input name="pending_expiry_minutes" type="number" min="1" max="1440" value="{{ $pending_expiry_minutes }}" class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900" />
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold">Paylink (Card)</h2>
                <p class="mt-1 text-xs text-zinc-500">Template supports placeholders: <span class="font-mono">{reference}</span> <span class="font-mono">{amount}</span> <span class="font-mono">{user_email}</span></p>
                <div class="mt-4 grid gap-4">
                    <div>
                        <label class="text-sm text-zinc-600 dark:text-zinc-300">Checkout URL template</label>
                        <input name="paylink_checkout_url_template" value="{{ $paylink_checkout_url_template }}" class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900" placeholder="https://paylink.lightningpay.me/XXXX?amount={amount}&reference={reference}" />
                    </div>
                    <div>
                        <label class="text-sm text-zinc-600 dark:text-zinc-300">Webhook secret</label>
                        <input name="paylink_webhook_secret" value="{{ $paylink_webhook_secret }}" class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900" placeholder="HMAC secret" />
                        <p class="mt-1 text-xs text-zinc-500">Webhook expects header <span class="font-mono">X-Signature</span> = HMAC-SHA256(raw body, secret).</p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold">BitPay (Crypto)</h2>
                <p class="mt-1 text-xs text-zinc-500">Template supports placeholders: <span class="font-mono">{reference}</span> <span class="font-mono">{amount}</span> <span class="font-mono">{user_email}</span></p>
                <div class="mt-4 grid gap-4">
                    <div>
                        <label class="text-sm text-zinc-600 dark:text-zinc-300">Checkout URL template</label>
                        <input name="bitpay_checkout_url_template" value="{{ $bitpay_checkout_url_template }}" class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900" placeholder="https://bitpay.com/invoice?amount={amount}&reference={reference}" />
                    </div>
                    <div>
                        <label class="text-sm text-zinc-600 dark:text-zinc-300">Webhook secret</label>
                        <input name="bitpay_webhook_secret" value="{{ $bitpay_webhook_secret }}" class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900" placeholder="HMAC secret" />
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </div>
</x-layouts.app>
