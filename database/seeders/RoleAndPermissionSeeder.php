<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Nettoyage (facultatif)
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        /*
        |--------------------------------------------------------------------------
        | Définition des permissions
        |--------------------------------------------------------------------------
        */
        $permissions = [
            // Core / Admin système
            'manage_users',
            'manage_roles',
            'manage_permissions',
            'manage_settings',
            'view_audit_logs',
            'backup_database',
            'restore_database',
            'generate_reports',
            'admin_access',

            // Gestion des données FECOFA
            'manage_leagues',
            'manage_matches',
            'manage_seasons',

            // Import / export
            // 'export_internal_list',
            'export_referee_data',
            'import_referee_data',

            // Arbitres (granulaire)
            'view_referee',
            'create_referee',
            'edit_referee',
            'delete_referee',

            // Instructeurs
            'view_instructor',
            'create_instructor',
            'edit_instructor',
            'delete_instructor',
            'manage_instructor_roles',

            // Affectations
            'assign_match',
            'edit_assignment',
            'view_assignment',
            'delete_assignment',

            // Suivi & formations
            'record_evaluation',
            'view_evaluation',
            'manage_trainings',
            'manage_referee_categories',

            // Permission "chapeau"
            'manage_referees', // => donne accès global à la gestion des arbitres
            'referee_access', // => accès aux écrans arbitres
        ];

        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        /*
        |--------------------------------------------------------------------------
        | Rôles
        |--------------------------------------------------------------------------
        */
        $owner = Role::findOrCreate('Owner', 'web');
        $admin = Role::findOrCreate('Administrator', 'web');
        $member = Role::findOrCreate('Member', 'web');
        $viewer = Role::findOrCreate('Viewer', 'web');

        // --- ADMINISTRATOR ---
        $admin->syncPermissions([
            'manage_users',
            'manage_roles',
            'manage_permissions',
            'manage_settings',
            'view_audit_logs',
            'backup_database',
            'restore_database',
            'generate_reports',
            'export_referee_data',
            'import_referee_data',
            'manage_leagues',
            'manage_matches',
            'manage_seasons',
            'manage_referee_categories', // accès aux catégories d'arbitres
            'manage_referees',  // accès complet aux arbitres
            'referee_access',  // accès aux écrans arbitres
            'admin_access',
            'view_referee',
            'view_instructor',
            'create_instructor',
            'edit_instructor',
            'delete_instructor',
            'manage_instructor_roles',
        ]);

        // --- MEMBER (Département arbitrage, CNA, etc.) ---
        $member->syncPermissions([
            'manage_referees',      // clé pour tous les écrans arbitres
            'view_referee',
            'create_referee',
            'edit_referee',
            // 'delete_referee',

            'view_instructor',
            'create_instructor',
            'edit_instructor',

            'assign_match',
            'edit_assignment',
            'view_assignment',
            'delete_assignment',

            'record_evaluation',
            'view_evaluation',
            'manage_trainings',

            'generate_reports',
            'export_referee_data',
            'import_referee_data',
        ]);

        // --- VIEWER (consultation uniquement) ---
        $viewer->syncPermissions([
            'view_referee',
            'view_assignment',
            'view_evaluation',
            'generate_reports',
            'export_referee_data',
            'referee_access',
            'view_instructor',
        ]);

        // --- OWNER = tous les droits ---
        $owner->syncPermissions(Permission::all());

        /*
        |--------------------------------------------------------------------------
        | Super Admin par défaut
        |--------------------------------------------------------------------------
        */
        $email = env('SUPER_ADMIN_EMAIL', 'superadmin@fecofa.cd');
        $user = User::where('email', $email)->first();

        if (! $user) {
            $pass = env('SUPER_ADMIN_PASSWORD');

            if (blank($pass)) {
                throw new \RuntimeException(
                    'SUPER_ADMIN_PASSWORD must be defined before running RoleAndPermissionSeeder.'
                );
            }

            $user = User::create([
                'name' => 'Super Admin',
                'email' => $email,
                'password' => Hash::make($pass),
            ]);
        }

        $user->forceFill([
            'password_set_at' => now(),
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();
        $user->syncRoles(['Owner']);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
