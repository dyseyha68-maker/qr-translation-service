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
            // Phase 3 permissions
            PermissionName::REFERRAL_CODES_VIEW->value,
            PermissionName::REFERRAL_CODES_CREATE->value,
            PermissionName::REFERRAL_CODES_UPDATE->value,
            PermissionName::REFERRAL_CODES_DELETE->value,
            PermissionName::REFERRAL_CODES_ACTIVATE->value,
            PermissionName::REFERRAL_CODES_DEACTIVATE->value,
            PermissionName::REFERRAL_CODES_QR_VIEW->value,
            PermissionName::REFERRAL_CODES_QR_DOWNLOAD->value,
            // Phase 4 permissions
            PermissionName::LEADS_VIEW->value,
            PermissionName::LEADS_CREATE->value,
            PermissionName::LEADS_UPDATE->value,
            PermissionName::LEADS_ASSIGN->value,
            PermissionName::LEADS_CHANGE_ATTRIBUTION->value,
            PermissionName::LEADS_ADD_NOTE->value,
            PermissionName::CUSTOMERS_VIEW->value,
            // Phase 5 permissions
            PermissionName::ORDERS_VIEW->value,
            PermissionName::ORDERS_CREATE->value,
            PermissionName::ORDERS_UPDATE->value,
            PermissionName::ORDERS_CONFIRM->value,
            PermissionName::ORDERS_CANCEL->value,
            PermissionName::ORDERS_COMPLETE->value,
            PermissionName::ORDERS_DELETE->value,
            PermissionName::PAYMENTS_VIEW->value,
            PermissionName::PAYMENTS_CREATE->value,
            PermissionName::PAYMENTS_UPDATE->value,
            PermissionName::PAYMENTS_REFUND->value,
            // Phase 6 permissions
            PermissionName::COMMISSIONS_VIEW->value,
            PermissionName::COMMISSIONS_CREATE->value,
            PermissionName::COMMISSIONS_UPDATE->value,
            PermissionName::COMMISSIONS_APPROVE->value,
            PermissionName::COMMISSIONS_PAY->value,
            PermissionName::COMMISSIONS_REVERSE->value,
            PermissionName::COMMISSIONS_DELETE->value,
            PermissionName::COMMISSIONS_REPORTS->value,
            PermissionName::COMMISSION_PAYMENTS_VIEW->value,
            PermissionName::COMMISSION_PAYMENTS_CREATE->value,
            PermissionName::COMMISSION_PAYMENTS_UPDATE->value,
            // Phase 7 permissions
            PermissionName::REPORTS_VIEW->value,
            PermissionName::REPORTS_EXPORT->value,
        ]);

        // 4. Staff: Operational view & intake of leads, drivers, vehicles, codes, orders & recording payments
        $staff = Role::firstOrCreate([
            'name' => RoleName::STAFF->value,
            'guard_name' => 'web',
        ]);
        $staff->syncPermissions([
            PermissionName::DASHBOARD_VIEW->value,
            PermissionName::PARTNERS_VIEW->value,
            PermissionName::DRIVERS_VIEW->value,
            PermissionName::VEHICLES_VIEW->value,
            // Phase 3 read & QR permissions
            PermissionName::REFERRAL_CODES_VIEW->value,
            PermissionName::REFERRAL_CODES_QR_VIEW->value,
            PermissionName::REFERRAL_CODES_QR_DOWNLOAD->value,
            // Phase 4 staff operations (cannot change attribution!)
            PermissionName::LEADS_VIEW->value,
            PermissionName::LEADS_CREATE->value,
            PermissionName::LEADS_UPDATE->value,
            PermissionName::LEADS_ASSIGN->value,
            PermissionName::LEADS_ADD_NOTE->value,
            PermissionName::CUSTOMERS_VIEW->value,
            // Phase 5 staff operations
            PermissionName::ORDERS_VIEW->value,
            PermissionName::ORDERS_CREATE->value,
            PermissionName::ORDERS_UPDATE->value,
            PermissionName::PAYMENTS_VIEW->value,
            PermissionName::PAYMENTS_CREATE->value,
            // Phase 6 view-only
            PermissionName::COMMISSIONS_VIEW->value,
        ]);

        // 5. Finance: Orders & Payment view, recording payments, refunding, and commission payouts
        $finance = Role::firstOrCreate([
            'name' => RoleName::FINANCE->value,
            'guard_name' => 'web',
        ]);
        $finance->syncPermissions([
            PermissionName::DASHBOARD_VIEW->value,
            PermissionName::ORDERS_VIEW->value,
            PermissionName::PAYMENTS_VIEW->value,
            PermissionName::PAYMENTS_CREATE->value,
            PermissionName::PAYMENTS_UPDATE->value,
            PermissionName::PAYMENTS_REFUND->value,
            // Phase 6 finance operations
            PermissionName::COMMISSIONS_VIEW->value,
            PermissionName::COMMISSIONS_APPROVE->value,
            PermissionName::COMMISSIONS_PAY->value,
            PermissionName::COMMISSIONS_REVERSE->value,
            PermissionName::COMMISSIONS_REPORTS->value,
            PermissionName::COMMISSION_PAYMENTS_VIEW->value,
            PermissionName::COMMISSION_PAYMENTS_CREATE->value,
            // Phase 7 permissions
            PermissionName::REPORTS_VIEW->value,
            PermissionName::REPORTS_EXPORT->value,
        ]);
    }
}
