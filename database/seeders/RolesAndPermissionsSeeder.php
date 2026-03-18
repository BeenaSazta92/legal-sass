<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\LawFirm;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        $guard = 'sanctum'; // API guard

        // -----------------------------
        // Step 0: Clear cached roles & permissions
        // -----------------------------
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // -----------------------------
        // Step 1: Define Permissions (CRUD-style)
        // -----------------------------
        $permissions = [
            // Law Firms CRUD
            'create_law_firm','read_law_firm','update_law_firm','delete_law_firm',
            // Subscription Plans CRUD
            'create_subscription','read_subscription','update_subscription','delete_subscription',
            // Assign default subscription
            'assign_default_subscription',
            // Admin user creation
            'create_admin',
            // Admin role permissions
            'create_lawyer','create_client',
            // Lawyer permissions
            'upload_document','share_document',
            // Client permissions
            'view_shared_document',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate([
                'name' => $perm,
                'guard_name' => $guard,
            ]);
        }

        // -----------------------------
        // Step 2: Create Roles & assign permissions
        // -----------------------------
        $roles = [
            'SYSTEM_ADMIN' => [
                'create_law_firm','read_law_firm','update_law_firm','delete_law_firm',
                'create_subscription','read_subscription','update_subscription','delete_subscription',
                'assign_default_subscription',
                'create_admin',
            ],
            'ADMIN' => ['create_lawyer','create_client'],
            'LAWYER' => ['upload_document','share_document'],
            'CLIENT' => ['view_shared_document'],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => $guard,
            ]);

            // Assign permissions AFTER they exist
            $role->syncPermissions($rolePermissions);
        }

        // -----------------------------
        // Step 3: Create default firm (for tenant users)
        // -----------------------------
        $firm = LawFirm::firstOrCreate([
            'id' => 1,
        ], [
            'name' => 'Default Law Firm',
            'subscription_id' => 1, // assume default subscription exists
            'status' => 'active',
        ]);

        // -----------------------------
        // Step 4: Create default users and assign roles
        // -----------------------------
        $defaultUsers = [
            [
                'name' => 'Platform Admin',
                'email' => 'admin@platform.com',
                'password' => bcrypt('password123'),
                'firm_id' => null,
                'role' => 'SYSTEM_ADMIN',
            ],
            [
                'name' => 'Firm Admin',
                'email' => 'admin@lawfirm.com',
                'password' => bcrypt('password123'),
                'firm_id' => $firm->id,
                'role' => 'ADMIN',
            ],
            [
                'name' => 'Lawyer User',
                'email' => 'lawyer@lawfirm.com',
                'password' => bcrypt('password123'),
                'firm_id' => $firm->id,
                'role' => 'LAWYER',
            ],
            [
                'name' => 'Client User',
                'email' => 'client@lawfirm.com',
                'password' => bcrypt('password123'),
                'firm_id' => $firm->id,
                'role' => 'CLIENT',
            ],
        ];

        foreach ($defaultUsers as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => $userData['password'],
                    'firm_id' => $userData['firm_id'],
                    'role' => $userData['role'],
                ]
            );

            $user->assignRole($userData['role'], $guard);
        }

        $this->command->info('Permissions, roles, and default users seeded successfully for API (sanctum guard).');
    }
}