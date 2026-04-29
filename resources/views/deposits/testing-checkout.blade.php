<x-layouts.site>
    <div class="mx-auto w-full max-w-xl">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <h1 class="text-xl font-semibold">Testing Checkout</h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                Reference: <span class="font-mono">{{ $order->reference }}</span>
            </p>

            <div class="mt-4 grid gap-2 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-zinc-500">Amount</span>
                    <span class="font-semibold">${{ number_format((float) $order->amount_credits, 2) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-zinc-500">Gateway</span>
                    <span class="font-semibold">{{ strtoupper($order->gateway) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-zinc-500">Status</span>
                    <span class="font-semibold">{{ strtoupper($order->status) }}</span>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <form method="POST" action="{{ route('deposits.simulate', $order->reference) }}">
                    @csrf
                    <input type="hidden" name="outcome" value="paid">
                    <flux:button type="submit" variant="primary">Simulate Paid</flux:button>
                </form>
                <form method="POST" action="{{ route('deposits.simulate', $order->reference) }}">
                    @csrf
                    <input type="hidden" name="outcome" value="pending">
                    <flux:button type="submit" variant="ghost">Simulate Pending</flux:button>
                </form>
                <form method="POST" action="{{ route('deposits.simulate', $order->reference) }}">
                    @csrf
                    <input type="hidden" name="outcome" value="failed">
                    <flux:button type="submit" variant="danger">Simulate Failed</flux:button>
                </form>
            </div>

            <div class="mt-6">
                <flux:button href="{{ route('wallet') }}" variant="outline">Back to Wallet</flux:button>
            </div>
        </div>
    </div>
</x-layouts.site>
