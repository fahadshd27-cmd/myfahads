<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DepositOrder;
use App\Services\DepositService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminDepositController extends Controller
{
    public function index(Request $request): View
    {
        $q = DepositOrder::query()->latest();

        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }

        $orders = $q->with('user')->limit(100)->get();

        return view('admin.deposits.index', ['orders' => $orders]);
    }

    public function expireNow(Request $request, DepositService $deposits): RedirectResponse
    {
        $count = $deposits->markExpiredDeposits();

        return back()->with('status', "Expired {$count} deposits.");
    }
}
