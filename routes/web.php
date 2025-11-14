<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'permission:admin_access', 'must_set_password'])
    ->prefix('admin')->name('admin')->as('admin.')
    ->group(function () {
        Volt::route('/dashboard', 'admin.dashboard')->name('dashboard');
        //Volt::route('roles', 'admin.roles')->name('roles.index');
        //Volt::route('permissions', 'admin.permissions')->name('permissions.index');
        Volt::route('/users', 'admin.users.index')->name('users.index');
});

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('user-password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});

// Invitation-only : bloquer /register s’il reste un lien perdu
Route::match(['get','post'], '/register', function () {
    return redirect()->route('login')
        ->withErrors(['email' => "L’inscription publique est désactivée. Demandez une invitation à l’administrateur (support@fecofa.cd)."]);
})->name('register')->middleware('guest');
