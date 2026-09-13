<?php

use App\Models\User;

test('public registration redirects to login', function () {
    $response = $this->get(route('register'));

    $response->assertRedirect(route('login'));
});

test('new users cannot register publicly', function () {
    $response = $this->post('/register', [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect(route('login'));
    $this->assertGuest();
    $this->assertDatabaseMissing(User::class, ['email' => 'test@example.com']);
});
