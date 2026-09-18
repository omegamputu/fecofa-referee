<?php

use App\Http\Controllers\Referee\ExportController;
use App\Http\Controllers\Referee\RefereeImportTemplateController;
use App\Livewire\Auth\InviteSetPassword;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('livewire.auth.login');
})->name('login');

Route::get('/lang/{lang}', function ($lang) {
    $availableLangs = ['fr', 'en'];

    if (in_array($lang, $availableLangs)) {
        session(['locale' => $lang]);
    }

    return redirect()->back();
})->name('lang.switch');

Volt::route('/invite/accept/{token}', InviteSetPassword::class)->name('invite.accept');

Route::middleware(['auth', 'verified', 'must_set_password'])->group(function () {
    Volt::route('/dashboard', 'dashboard')
        ->name('dashboard');
});

Route::middleware(['auth', 'permission:admin_access', 'must_set_password'])
    ->prefix('admin')->name('admin')->as('admin.')
    ->group(function () {
        Volt::route('/dashboard', 'admin.dashboard')->name('dashboard');
        // Volt::route('roles', 'admin.roles')->name('roles.index');
        // Volt::route('permissions', 'admin.permissions')->name('permissions.index');
        Volt::route('/users', 'admin.users.index')->name('users.index');
        Volt::route('/leagues', 'admin.leagues.index')->name('leagues.index');
        Volt::route('/exam-periods', 'admin.exam-periods.index')->name('exam-periods.index');
        Volt::route('/roles', 'admin.permissions.index')
            ->name('roles.index')
            ->middleware(['permission:manage_roles']);
        Volt::route('/roles/{role}/edit', 'admin.permissions.edit')
            ->name('roles.edit')
            ->middleware(['permission:manage_permissions']);
    });

Route::middleware(['auth', 'must_set_password'])->group(function () {
    Volt::route('/instructors/roles', 'instructors.roles')
        ->name('instructors.roles')
        ->middleware(['permission:manage_instructor_roles']);
    Volt::route('/instructors/list', 'instructors.index')
        ->name('instructors.index')
        ->middleware(['permission:view_instructor']);
    Volt::route('/instructors/create', 'instructors.create')
        ->name('instructors.create')
        ->middleware(['permission:create_instructor']);

    Volt::route('/instructors/{instructor}/edit', 'instructors.edit')
        ->name('instructors.edit')
        ->whereNumber('instructor')
        ->middleware(['permission:edit_instructor']);
});
// //////////////////
// / Referee routes
Route::middleware(['auth', 'must_set_password'])->group(function () {
    Volt::route('/referees/designations', 'referees.designations.index')
        ->name('referees.designations.index')
        ->middleware(['permission:manage_seasons']);

    Volt::route('/referees/categories', 'referees.categories.index')
        ->name('referees.categories.index')
        ->middleware(['permission:manage_referee_categories']);

    Volt::route('/referees/list', 'referees.index')
        ->name('referees.index')
        ->middleware(['permission:view_referee']);

    Volt::route('/referees/create', 'referees.create')
        ->name('referees.create')
        ->middleware(['permission:create_referee']);

    Volt::route('/referees/{referee}', 'referees.show')
        ->name('referees.show')
        ->whereNumber('referee')
        ->middleware(['permission:view_referee']);

    Volt::route('/referees/{referee}/edit', 'referees.edit')
        ->name('referees.edit')
        ->whereNumber('referee')
        ->middleware(['permission:edit_referee']);
});

// Export PDF
Route::get('/referees/export', [ExportController::class, 'refereeExportPdf'])
    ->name('referees.export')
    ->middleware(['auth', 'permission:export_referee_data']);

Route::get('/referees/eligible/export', [ExportController::class, 'eligibleRefereesExportPdf'])
    ->name('referees.eligible.export')
    ->middleware(['auth', 'must_set_password', 'permission:export_referee_data']);

Route::get('/referees/import/template', RefereeImportTemplateController::class)
    ->name('referees.import.template')
    ->middleware(['auth', 'must_set_password', 'permission:import_referee_data']);

Route::get('/instructors/export', [ExportController::class, 'instructorExportPdf'])
    ->name('instructors.export')
    ->middleware(['auth', 'permission:export_referee_data']);

// //////////////////

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
Route::match(['get', 'post'], '/register', function () {
    return redirect()->route('login')
        ->withErrors(['email' => 'L’inscription publique est désactivée. Demandez une invitation à l’administrateur (support@fecofa.cd).']);
})->name('register')->middleware('guest');
