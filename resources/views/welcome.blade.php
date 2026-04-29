<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[radial-gradient(circle_at_top,#2c2356_0%,#151329_45%,#0f1020_100%)] text-white">
        <div class="relative overflow-hidden">
            <div class="pointer-events-none absolute inset-0 opacity-30">
                <div class="absolute -left-24 top-12 h-72 w-72 rounded-full bg-violet-500/30 blur-3xl"></div>
                <div class="absolute right-0 top-0 h-96 w-96 rounded-full bg-fuchsia-400/20 blur-3xl"></div>
                <div class="absolute bottom-0 left-1/3 h-80 w-80 rounded-full bg-cyan-400/15 blur-3xl"></div>
            </div>

            <div class="relative mx-auto flex min-h-screen max-w-6xl flex-col px-6 py-8">
                <header class="flex items-center justify-between rounded-2xl border border-white/10 bg-white/5 px-5 py-4 backdrop-blur">
                    <div class="text-xl font-semibold tracking-wide">GIVEAWAYS.COM</div>
                    <nav class="hidden items-center gap-6 text-sm text-white/80 md:flex">
                        <a href="{{ route('boxes.index') }}" class="hover:text-white">Boxes</a>
                        @auth
                            <a href="{{ route('wallet') }}" class="hover:text-white">Wallet</a>
                            <a href="{{ route('settings.profile') }}" class="hover:text-white">Settings</a>
                            @if (auth()->user()->is_admin && \Illuminate\Support\Facades\Route::has('admin.dashboard'))
                                <a href="{{ route('admin.dashboard') }}" class="hover:text-white">Admin</a>
                            @endif
                        @endauth
                    </nav>
                    <div class="flex items-center gap-3">
                        @auth
                            @if (auth()->user()->is_admin && \Illuminate\Support\Facades\Route::has('admin.dashboard'))
                                <flux:button href="{{ route('admin.dashboard') }}" variant="primary">Open Admin</flux:button>
                            @else
                                <flux:button href="{{ route('boxes.index') }}" variant="primary">Open Boxes</flux:button>
                            @endif
                        @else
                            <flux:button href="{{ route('login') }}" variant="ghost">Sign In</flux:button>
                            <flux:button href="{{ route('register') }}" variant="primary">Create Account</flux:button>
                        @endauth
                    </div>
                </header>

                <main class="flex flex-1 items-center py-12">
                    <div class="grid w-full gap-10 lg:grid-cols-[1.15fr_0.85fr] lg:items-center">
                        <section>
                            <div class="inline-flex rounded-full border border-violet-300/20 bg-violet-400/10 px-4 py-2 text-xs uppercase tracking-[0.3em] text-violet-100">
                                Mystery Box Platform
                            </div>
                            <h1 class="mt-6 max-w-3xl text-5xl font-semibold leading-tight md:text-6xl">
                                Deposit credits, open boxes, and cash out your wins.
                            </h1>
                            <p class="mt-5 max-w-2xl text-base leading-7 text-white/70">
                                The app now supports authenticated wallets, testing and production deposit modes, provably-fair style spins, inventory actions, and admin controls for boxes and item pools.
                            </p>

                            <div class="mt-8 flex flex-wrap gap-4">
                                @auth
                                    <flux:button href="{{ route('boxes.index') }}" variant="primary">Browse Boxes</flux:button>
                                    <flux:button href="{{ route('wallet') }}" variant="outline">View Wallet</flux:button>
                                @else
                                    <flux:button href="{{ route('register') }}" variant="primary">Start Free</flux:button>
                                    <flux:button href="{{ route('login') }}" variant="outline">I Have An Account</flux:button>
                                @endauth
                            </div>
                        </section>

                        <section class="rounded-[2rem] border border-white/10 bg-white/5 p-6 shadow-2xl backdrop-blur">
                            <div class="rounded-[1.5rem] border border-white/10 bg-[#16172d] p-5">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-sm text-white/60">Live demo flow</div>
                                        <div class="mt-1 text-2xl font-semibold">Open a box</div>
                                    </div>
                                    <div class="rounded-xl bg-violet-500/20 px-3 py-2 text-sm font-semibold text-violet-100">$25 credit</div>
                                </div>

                            <div class="mt-5 rounded-2xl bg-[#111327] p-4">
                                <div class="grid grid-cols-4 gap-3">
                                    @for ($i = 0; $i < 8; $i++)
                                        <div class="flex aspect-square items-center justify-center rounded-xl border border-white/10 bg-gradient-to-br from-violet-500/20 to-fuchsia-500/10 text-lg font-semibold text-white/70">
                                            {{ $i === 5 ? '$50' : 'G' }}
                                        </div>
                                    @endfor
                                </div>
                                    <div class="mt-4 flex items-center justify-between rounded-xl bg-violet-500/15 px-4 py-3 text-sm">
                                        <span class="text-white/70">Winner revealed item</span>
                                        <span class="font-semibold">$50 reward</span>
                                    </div>
                                </div>

                                <div class="mt-5 grid gap-3 text-sm text-white/70">
                                    <div class="flex items-center justify-between rounded-xl border border-white/10 px-4 py-3">
                                        <span>Deposit modes</span>
                                        <span class="font-semibold text-white">Testing / Production</span>
                                    </div>
                                    <div class="flex items-center justify-between rounded-xl border border-white/10 px-4 py-3">
                                        <span>Post-win actions</span>
                                        <span class="font-semibold text-white">Keep / Save / Sell</span>
                                    </div>
                                    <div class="flex items-center justify-between rounded-xl border border-white/10 px-4 py-3">
                                        <span>Security</span>
                                        <span class="font-semibold text-white">Verified accounts</span>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                </main>
            </div>
        </div>
    </body>
</html>
