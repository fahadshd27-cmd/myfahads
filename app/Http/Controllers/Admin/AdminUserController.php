<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(): View
    {
        $users = User::query()->latest()->limit(100)->get();

        return view('admin.users.index', ['users' => $users]);
    }

    public function show(int $id, WalletService $wallets): View
    {
        $user = User::query()->findOrFail($id);
        $wallet = $wallets->ensureWallet($user)->fresh();
        $tx = $user->walletTransactions()->latest()->limit(50)->get();
        $spins = $user->spins()->with(['box', 'resultItem'])->latest()->limit(20)->get();
        $progress = $user->boxProgress()->with('box')->latest()->limit(20)->get();

        return view('admin.users.show', [
            'user' => $user,
            'wallet' => $wallet,
            'transactions' => $tx,
            'spins' => $spins,
            'progressEntries' => $progress,
        ]);
    }

    public function topUp(Request $request, int $id, WalletService $wallets): RedirectResponse
    {
        $user = User::query()->findOrFail($id);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $wallets->credit(
            user: $user,
            amount: (float) $data['amount'],
            type: 'admin_topup',
            meta: ['reason' => $data['reason'] ?? null],
            idempotencyKey: 'admin_topup:'.$user->id.':'.md5(json_encode($data).microtime(true)),
            admin: $request->user(),
            referenceType: User::class,
            referenceId: $user->id,
            bucket: WalletService::BUCKET_PROMO,
            creditSource: WalletService::BUCKET_PROMO,
            originContext: ['kind' => 'admin_topup'],
        );

        return back()->with('status', 'Wallet topped up');
    }

    public function refund(Request $request, int $id, WalletService $wallets): RedirectResponse
    {
        $user = User::query()->findOrFail($id);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $wallets->debit(
            user: $user,
            amount: (float) $data['amount'],
            type: 'admin_refund_debit',
            meta: ['reason' => $data['reason'] ?? null],
            idempotencyKey: 'admin_refund:'.$user->id.':'.md5(json_encode($data).microtime(true)),
            referenceType: User::class,
            referenceId: $user->id,
        );

        return back()->with('status', 'Wallet debited');
    }

    public function toggleSuspend(int $id): RedirectResponse
    {
        $user = User::query()->findOrFail($id);
        $user->status = $user->status === 'active' ? 'suspended' : 'active';
        $user->save();

        return back()->with('status', 'Updated user status');
    }

    public function toggleAdmin(int $id): RedirectResponse
    {
        $user = User::query()->findOrFail($id);
        $user->is_admin = ! $user->is_admin;
        $user->save();

        return back()->with('status', 'Updated admin flag');
    }
}
