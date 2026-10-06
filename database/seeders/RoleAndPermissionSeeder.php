<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use Illuminate\Database\Seeder;
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
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Create all Phase 1 permissions
        foreach (PermissionName::cases() as $permission) {
            Permission::firstOrCreate([
                'name' => $permission->value,
                'guard_name' => 'web',
            ]);
        }

        // 2. Create Roles and Assign Permissions
        // Super Admin: Full administrative privileges
        $superAdmin = Role::firstOrCreate([
            'name' => RoleName::SUPER_ADMIN->value,
            'guard_name' => 'web',
        ]);
        $superAdmin->syncPermissions(PermissionName::values());

        // Admin: User creation, updating, role assignment (cannot delete or assign Super Admin)
        $admin = Role::firstOrCreate([
            'name' => RoleName::ADMIN->value,
            'guard_name' => 'web',
        ]);
        $admin->syncPermissions([
            PermissionName::USERS_VIEW->value,
            PermissionName::USERS_CREATE->value,
            PermissionName::USERS_UPDATE->value,
            PermissionName::ROLES_VIEW->value,
            PermissionName::ROLES_ASSIGN->value,
            PermissionName::DASHBOARD_VIEW->value,
        ]);

        // Staff: Dashboard access only (leads and orders in later phases)
        $staff = Role::firstOrCreate([
            'name' => RoleName::STAFF->value,
            'guard_name' => 'web',
        ]);
        $staff->syncPermissions([
            PermissionName::DASHBOARD_VIEW->value,
        ]);

        // Finance: Dashboard access only (payments and payouts in later phases)
        $finance = Role::firstOrCreate([
            'name' => RoleName::FINANCE->value,
            'guard_name' => 'web',
        ]);
        $finance->syncPermissions([
            PermissionName::DASHBOARD_VIEW->value,
        ]);
    }
}
