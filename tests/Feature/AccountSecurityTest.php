<?php

use App\Models\User;

test('an inactive authenticated user is logged out on the next request', function () {
    $user = User::factory()->create(['is_active' => false]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route('login'));
    $this->assertGuest();
});

test('an unverified user cannot access the dashboard', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('verification.notice'));
});
