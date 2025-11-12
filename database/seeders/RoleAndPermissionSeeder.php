<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Nettoyage (facultatif)
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'manage_users','manage_roles','manage_permissions',
            'manage_settings','view_audit_logs','backup_database',
            'restore_database', 'generate_reports','export_internal_list',
            'import_referee_data', 'view_referee', 'edit_referee',
            'delete_referee', 'assign_referee', 'edit_assignment',
            'view_assignment', 'delete_assignment','record_evaluation',
            'view_evaluation','manage_trainings', 'admin_access', 'manage_admins',

            // Dept Arbitrage
            'create_referee','edit_referee','view_referee',
            'assign_match','edit_assignment','view_assignment',
            'record_evaluation','view_evaluation', 'manage_trainings',
            'generate_reports', 'export_internal_list', 'import_referee_data'
        ];

        foreach ($permissions as $p) {
            Permission::findOrCreate($p, 'web');
        }

        // Role
        $superAdmin = Role::findOrCreate('Super_Admin', 'web');
        $admin = Role::findOrCreate('Admin', 'web');
        $deptArbitrage = Role::findOrCreate('Dept_Arbitrage', 'web');

        $admin->syncPermissions([
            'manage_users','manage_roles','manage_permissions',
            'manage_settings','view_audit_logs','backup_database',
            'restore_database', 'generate_reports','export_internal_list',
            'import_referee_data', 'admin_access'
        ]);

        $deptArbitrage->syncPermissions([
            'create_referee','edit_referee','view_referee',
            'assign_match','edit_assignment','view_assignment',
            'record_evaluation','view_evaluation', 'manage_trainings',
            'generate_reports', 'export_internal_list', 'import_referee_data'
        ]);

        // ---- Super-Admin par défaut (via variables d'env si dispos)
        $email = env('SUPER_ADMIN_EMAIL', 'administrator@fecofa.cd');
        $pass  = env('SUPER_ADMIN_PASSWORD', 'Ref@dmin#2025');

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Super Admin',
                'password' => Hash::make($pass),
            ]
        );

        $user->forceFill(['password_set_at' => now()])->save();
        $user->syncRoles(['Super_Admin']);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
