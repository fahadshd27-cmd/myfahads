<?php

use App\Models\User;
use App\Services\WalletService;

it('allows admins to top up a user wallet', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'is_admin' => true,
        'status' => 'active',
    ]);

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'status' => 'active',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.users.top-up', $user->id), [
            'amount' => 15,
            'reason' => 'Manual adjustment',
        ])
        ->assertRedirect();

    $user->refresh();

    expect((float) $user->wallet->balance_credits)->toBe(15.0);
});

it('allows admins to debit a user wallet', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'is_admin' => true,
        'status' => 'active',
    ]);

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'status' => 'active',
    ]);

    app(WalletService::class)->credit($user, 25, 'seed_credit');

    $this->actingAs($admin)
        ->post(route('admin.users.refund', $user->id), [
            'amount' => 10,
            'reason' => 'Correction',
        ])
        ->assertRedirect();

    $user->refresh();

    expect((float) $user->wallet->balance_credits)->toBe(15.0);
});
