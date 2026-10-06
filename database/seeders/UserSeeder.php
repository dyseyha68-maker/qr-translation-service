<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Development User Seeder
 *
 * CAUTION: These default credentials are for local development and QA only.
 * In production, passwords must be securely randomized or generated during deployment.
 */
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultPassword = Hash::make('Password123!');

        // 1. Super Admin
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@translation.local'],
            [
                'name' => 'General Manager',
                'password' => $defaultPassword,
                'status' => UserStatus::ACTIVE,
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->syncRoles([RoleName::SUPER_ADMIN->value]);

        // 2. Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@translation.local'],
            [
                'name' => 'Operations Admin',
                'password' => $defaultPassword,
                'status' => UserStatus::ACTIVE,
                'email_verified_at' => now(),
            ]
        );
        $admin->syncRoles([RoleName::ADMIN->value]);

        // 3. Staff
        $staff = User::firstOrCreate(
            ['email' => 'staff@translation.local'],
            [
                'name' => 'Counter Receptionist',
                'password' => $defaultPassword,
                'status' => UserStatus::ACTIVE,
                'email_verified_at' => now(),
            ]
        );
        $staff->syncRoles([RoleName::STAFF->value]);

        // 4. Finance
        $finance = User::firstOrCreate(
            ['email' => 'finance@translation.local'],
            [
                'name' => 'Company Cashier',
                'password' => $defaultPassword,
                'status' => UserStatus::ACTIVE,
                'email_verified_at' => now(),
            ]
        );
        $finance->syncRoles([RoleName::FINANCE->value]);
    }
}
