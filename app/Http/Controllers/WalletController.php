<?php

namespace App\Http\Controllers;

use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function __construct(private readonly WalletService $wallets) {}

    public function show(Request $request): View
    {
        $user = $request->user();
        $wallet = $this->wallets->ensureWallet($user)->fresh();
        $transactions = $user->walletTransactions()->latest()->limit(50)->get();
        $deposits = $user->depositOrders()->latest()->limit(20)->get();

        return view('wallet.show', [
            'wallet' => $wallet,
            'transactions' => $transactions,
            'deposits' => $deposits,
        ]);
    }
}
