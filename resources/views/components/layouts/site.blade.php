<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-100 text-zinc-900 dark:bg-[#0f1020] dark:text-white">
        <div class="min-h-screen bg-[radial-gradient(circle_at_top,#28234d_0%,#15172a_35%,#11131f_100%)]">
            <header class="sticky top-0 z-30 border-b border-white/10 bg-[#141628]/90 backdrop-blur">
                <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                    <a href="{{ route('home') }}" class="flex items-center gap-3 text-lg font-semibold tracking-wide">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-white text-zinc-950 shadow-sm">
                            <x-app-logo-icon class="size-6 fill-current" />
                        </span>
                        <span>GIVEAWAYS.COM</span>
                    </a>

                    <nav class="hidden items-center gap-6 text-sm text-white/75 md:flex">
                        <a href="{{ route('boxes.index') }}" class="{{ request()->routeIs('boxes.*') ? 'text-white' : 'hover:text-white' }}">Boxes</a>
                        @auth
                            <a href="{{ route('wallet') }}" class="{{ request()->routeIs('wallet') ? 'text-white' : 'hover:text-white' }}">Wallet</a>
                            <a href="{{ route('inventory.index') }}" class="{{ request()->routeIs('inventory.*') ? 'text-white' : 'hover:text-white' }}">Inventory</a>
                            <a href="{{ route('settings.profile') }}" class="{{ request()->routeIs('settings.*') ? 'text-white' : 'hover:text-white' }}">Settings</a>
                        @endauth
                    </nav>

                    <div class="flex items-center gap-3">
                        @auth
                            @if ((auth()->user()->is_admin ?? false) && \Illuminate\Support\Facades\Route::has('admin.dashboard'))
                                <flux:button href="{{ route('admin.dashboard') }}" variant="ghost">Admin</flux:button>
                            @endif

                            <div class="hidden items-center gap-3 rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm md:flex">
                                <span class="font-medium">{{ auth()->user()->display_name ?: auth()->user()->name }}</span>
                                <span class="text-white/50">|</span>
                                <span>${{ number_format((float) (auth()->user()->wallet?->balance_credits ?? 0), 2) }}</span>
                            </div>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <flux:button as="button" type="submit" variant="primary">Logout</flux:button>
                            </form>
                        @else
                            <flux:button href="{{ route('login') }}" variant="ghost">Sign In</flux:button>
                            <flux:button href="{{ route('register') }}" variant="primary">Register</flux:button>
                        @endauth
                    </div>
                </div>
            </header>

            <main class="mx-auto max-w-6xl px-6 py-8">
                {{ $slot }}
            </main>
        </div>

        @fluxScripts
    </body>
</html>
