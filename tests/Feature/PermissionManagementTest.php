<?php

use App\Models\User;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function permissionManager(): User
{
    $user = User::factory()->create();
    Permission::findOrCreate('manage_permissions', 'web');
    $user->givePermissionTo('manage_permissions');

    return $user;
}

test('creating a permission displays a confirmation message', function () {
    $role = Role::create(['name' => 'Administrator']);
    $this->actingAs(permissionManager());

    Volt::test('admin.permissions.edit', ['role' => $role])
        ->set('createPermissionName', 'record_referee_aptitudes')
        ->call('createPermission')
        ->assertSet('createPermissionName', '')
        ->assertSet('feedbackType', 'success')
        ->assertSet('feedbackMessage', __('Permission created successfully.'))
        ->assertSee(__('Permission created successfully.'));

    $this->assertDatabaseHas('permissions', [
        'name' => 'record_referee_aptitudes',
        'guard_name' => 'web',
    ]);
});

test('creating an existing permission displays an error message', function () {
    $role = Role::create(['name' => 'Administrator']);
    Permission::findOrCreate('edit_referee', 'web');
    $this->actingAs(permissionManager());

    Volt::test('admin.permissions.edit', ['role' => $role])
        ->set('createPermissionName', 'edit_referee')
        ->call('createPermission')
        ->assertSet('feedbackType', 'error')
        ->assertSet('feedbackMessage', __('Permission already exists or invalid name.'))
        ->assertSee(__('Permission already exists or invalid name.'));

    expect(Permission::where('name', 'edit_referee')->count())->toBe(1);
});
