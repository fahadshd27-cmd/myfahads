<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get('/dashboard');
    $response->assertRedirect('/login');
});

test('authenticated users are redirected to boxes from the dashboard route', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'status' => 'active',
    ]);

    $this->actingAs($user);

    $response = $this->get('/dashboard');
    $response->assertRedirect(route('boxes.index'));
});

test('admin users are redirected to the admin dashboard route', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'is_admin' => true,
        'status' => 'active',
    ]);

    $this->actingAs($admin);

    $response = $this->get('/dashboard');
    $response->assertRedirect(route('admin.dashboard'));
});

test('admin users can open the admin dashboard', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'is_admin' => true,
        'status' => 'active',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Control center');
});
