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

        // 1. Create all registered permissions
        foreach (PermissionName::cases() as $permission) {
            Permission::firstOrCreate([
                'name' => $permission->value,
                'guard_name' => 'web',
            ]);
        }

        // 2. Super Admin: Full administrative privileges
        $superAdmin = Role::firstOrCreate([
            'name' => RoleName::SUPER_ADMIN->value,
            'guard_name' => 'web',
        ]);
        $superAdmin->syncPermissions(PermissionName::values());

        // 3. Admin: Full user management (except deleting/escalating Super Admin) & full Phase 2 partner/driver/vehicle management
        $admin = Role::firstOrCreate([
            'name' => RoleName::ADMIN->value,
            'guard_name' => 'web',
        ]);
        $admin->syncPermissions([
            PermissionName::DASHBOARD_VIEW->value,
            PermissionName::USERS_VIEW->value,
            PermissionName::USERS_CREATE->value,
            PermissionName::USERS_UPDATE->value,
            PermissionName::ROLES_VIEW->value,
            PermissionName::ROLES_ASSIGN->value,
            // Phase 2 permissions
            PermissionName::PARTNERS_VIEW->value,
            PermissionName::PARTNERS_CREATE->value,
            PermissionName::PARTNERS_UPDATE->value,
            PermissionName::PARTNERS_DELETE->value,
            PermissionName::DRIVERS_VIEW->value,
            PermissionName::DRIVERS_CREATE->value,
            PermissionName::DRIVERS_UPDATE->value,
            PermissionName::DRIVERS_DELETE->value,
            PermissionName::VEHICLES_VIEW->value,
            PermissionName::VEHICLES_CREATE->value,
            PermissionName::VEHICLES_UPDATE->value,
            PermissionName::VEHICLES_DELETE->value,
        ]);

        // 4. Staff: Operational read-only view of partners, drivers, vehicles + dashboard
        $staff = Role::firstOrCreate([
            'name' => RoleName::STAFF->value,
            'guard_name' => 'web',
        ]);
        $staff->syncPermissions([
            PermissionName::DASHBOARD_VIEW->value,
            PermissionName::PARTNERS_VIEW->value,
            PermissionName::DRIVERS_VIEW->value,
            PermissionName::VEHICLES_VIEW->value,
        ]);

        // 5. Finance: Dashboard access only in Phase 2
        $finance = Role::firstOrCreate([
            'name' => RoleName::FINANCE->value,
            'guard_name' => 'web',
        ]);
        $finance->syncPermissions([
            PermissionName::DASHBOARD_VIEW->value,
        ]);
    }
}
