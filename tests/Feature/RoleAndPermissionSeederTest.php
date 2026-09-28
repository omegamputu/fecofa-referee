<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('referee management permissions are assigned according to the role matrix', function () {
    $superAdmin = User::factory()->create([
        'email' => env('SUPER_ADMIN_EMAIL', 'superadmin@fecofa.cd'),
    ]);

    $this->seed(RoleAndPermissionSeeder::class);

    $administrator = Role::findByName('Administrator');
    $member = Role::findByName('Member');
    $viewer = Role::findByName('Viewer');
    $owner = Role::findByName('Owner');

    $administratorPermissions = [
        'manage_referees',
        'referee_access',
        'view_referee',
        'create_referee',
        'edit_referee',
        'delete_referee',
        'manage_seasons',
        'manage_referee_categories',
        'assign_match',
        'edit_assignment',
        'view_assignment',
        'delete_assignment',
        'record_evaluation',
        'view_evaluation',
        'manage_trainings',
        'export_referee_data',
        'import_referee_data',
    ];

    $memberPermissions = [
        'manage_referees',
        'referee_access',
        'view_referee',
        'create_referee',
        'edit_referee',
        'manage_seasons',
        'assign_match',
        'edit_assignment',
        'view_assignment',
        'delete_assignment',
        'record_evaluation',
        'view_evaluation',
        'manage_trainings',
        'export_referee_data',
        'import_referee_data',
    ];

    $viewerPermissions = [
        'referee_access',
        'view_referee',
        'view_assignment',
        'view_evaluation',
        'generate_reports',
        'export_referee_data',
    ];

    foreach ($administratorPermissions as $permission) {
        expect($administrator->hasPermissionTo($permission))->toBeTrue();
    }

    foreach ($memberPermissions as $permission) {
        expect($member->hasPermissionTo($permission))->toBeTrue();
    }

    foreach ($viewerPermissions as $permission) {
        expect($viewer->hasPermissionTo($permission))->toBeTrue();
    }

    foreach (['delete_referee', 'manage_referee_categories', 'admin_access'] as $permission) {
        expect($member->hasPermissionTo($permission))->toBeFalse();
    }

    foreach (['create_referee', 'edit_referee', 'delete_referee', 'manage_seasons', 'assign_match', 'edit_assignment', 'delete_assignment', 'import_referee_data'] as $permission) {
        expect($viewer->hasPermissionTo($permission))->toBeFalse();
    }

    expect($owner->permissions)->toHaveCount(Permission::count());
    expect($superAdmin->fresh()->hasRole('Owner'))->toBeTrue();
});
