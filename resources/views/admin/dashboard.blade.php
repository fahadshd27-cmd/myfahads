<x-layouts.app>
    <div class="mx-auto w-full max-w-6xl">
        <div class="grid gap-6">
            <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-sm uppercase tracking-[0.25em] text-zinc-500">Admin</p>
                <h1 class="mt-2 text-3xl font-semibold">Control center</h1>
                <p class="mt-2 max-w-2xl text-sm text-zinc-600 dark:text-zinc-300">
                    Manage box catalog, deposit settings, gateway behavior, and user balances from this dashboard.
                </p>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <a href="{{ route('admin.boxes') }}" class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="text-sm text-zinc-500">Catalog</div>
                    <div class="mt-2 text-xl font-semibold">Boxes & Items</div>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Create boxes, upload item artwork, and tune weights or sell values.</p>
                </a>

                <a href="{{ route('admin.deposits') }}" class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="text-sm text-zinc-500">Payments</div>
                    <div class="mt-2 text-xl font-semibold">Deposits</div>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Monitor payment orders, expire stale deposits, and review statuses.</p>
                </a>

                <a href="{{ route('admin.users') }}" class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="text-sm text-zinc-500">Accounts</div>
                    <div class="mt-2 text-xl font-semibold">Users</div>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Suspend users, grant admin access, and adjust wallet balances.</p>
                </a>
            </div>
        </div>
    </div>
</x-layouts.app>

