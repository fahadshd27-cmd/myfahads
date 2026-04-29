<?php

use App\Http\Controllers\Admin\AdminBoxController;
use App\Http\Controllers\Admin\AdminBoxItemController;
use App\Http\Controllers\Admin\AdminDepositController;
use App\Http\Controllers\Admin\AdminPaymentSettingsController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\BoxController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PublicMediaController;
use App\Http\Controllers\SpinController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('media/{path}', [PublicMediaController::class, 'show'])
    ->where('path', '.*')
    ->name('media.public');

Route::get('dashboard', function () {
    if (auth()->user()->is_admin) {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('boxes.index');
})->middleware(['auth', 'verified', 'active'])->name('dashboard');

Route::middleware(['auth', 'active'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Route::get('wallet', [WalletController::class, 'show'])->name('wallet');
    Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');

    Route::post('deposits', [DepositController::class, 'create'])
        ->middleware('throttle:deposit')
        ->name('deposits.create');
    Route::get('deposits/{reference}/status', [DepositController::class, 'status'])->name('deposits.status');
    Route::get('deposits/{reference}/checkout', [DepositController::class, 'testingCheckout'])->name('deposits.checkout');
    Route::post('deposits/{reference}/simulate', [DepositController::class, 'simulate'])->name('deposits.simulate');

    Route::get('boxes', [BoxController::class, 'index'])->name('boxes.index');
    Route::get('boxes/{slug}', [BoxController::class, 'show'])->name('boxes.show');
    Route::post('boxes/{slug}/spins', [SpinController::class, 'store'])
        ->middleware('throttle:spin')
        ->name('spins.create');

    Route::get('spins/history', [SpinController::class, 'history'])->name('spins.history');
    Route::get('fairness/spins/{spinId}', [SpinController::class, 'fairness'])->name('spins.fairness');

    Route::post('inventory/{id}/keep', [InventoryController::class, 'keep'])->name('inventory.keep');
    Route::post('inventory/{id}/save', [InventoryController::class, 'save'])->name('inventory.save');
    Route::post('inventory/{id}/claim', [InventoryController::class, 'claim'])->name('inventory.claim');
    Route::post('inventory/{id}/sell', [InventoryController::class, 'sell'])->name('inventory.sell');
});

Route::post('webhooks/paylink', [WebhookController::class, 'paylink'])->name('webhooks.paylink');
Route::post('webhooks/bitpay', [WebhookController::class, 'bitpay'])->name('webhooks.bitpay');

Route::middleware(['auth', 'verified', 'active', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::view('dashboard', 'admin.dashboard')->name('dashboard');

    Route::get('payment-settings', [AdminPaymentSettingsController::class, 'show'])->name('payment-settings');
    Route::post('payment-settings', [AdminPaymentSettingsController::class, 'update'])->name('payment-settings.update');

    Route::get('deposits', [AdminDepositController::class, 'index'])->name('deposits');
    Route::post('deposits/expire-now', [AdminDepositController::class, 'expireNow'])->name('deposits.expire');

    Route::get('boxes', [AdminBoxController::class, 'index'])->name('boxes');
    Route::get('boxes/create', [AdminBoxController::class, 'create'])->name('boxes.create');
    Route::post('boxes', [AdminBoxController::class, 'store'])->name('boxes.store');
    Route::get('boxes/{id}', [AdminBoxController::class, 'edit'])->name('boxes.edit');
    Route::post('boxes/{id}', [AdminBoxController::class, 'update'])->name('boxes.update');
    Route::post('boxes/{id}/toggle', [AdminBoxController::class, 'toggle'])->name('boxes.toggle');
    Route::post('boxes/{id}/duplicate', [AdminBoxController::class, 'duplicate'])->name('boxes.duplicate');

    Route::get('boxes/{boxId}/items', [AdminBoxItemController::class, 'index'])->name('items');
    Route::post('boxes/{boxId}/items', [AdminBoxItemController::class, 'store'])->name('items.store');
    Route::post('boxes/{boxId}/items/duplicate-from', [AdminBoxItemController::class, 'duplicateFromBox'])->name('items.duplicate-from');
    Route::post('boxes/{boxId}/items/{itemId}', [AdminBoxItemController::class, 'update'])->name('items.update');
    Route::post('boxes/{boxId}/items/{itemId}/duplicate', [AdminBoxItemController::class, 'duplicate'])->name('items.duplicate');
    Route::post('boxes/{boxId}/items/{itemId}/delete', [AdminBoxItemController::class, 'delete'])->name('items.delete');

    Route::get('users', [AdminUserController::class, 'index'])->name('users');
    Route::get('users/{id}', [AdminUserController::class, 'show'])->name('users.show');
    Route::post('users/{id}/top-up', [AdminUserController::class, 'topUp'])->name('users.top-up');
    Route::post('users/{id}/refund', [AdminUserController::class, 'refund'])->name('users.refund');
    Route::post('users/{id}/toggle-suspend', [AdminUserController::class, 'toggleSuspend'])->name('users.toggle-suspend');
    Route::post('users/{id}/toggle-admin', [AdminUserController::class, 'toggleAdmin'])->name('users.toggle-admin');
});

require __DIR__.'/auth.php';
