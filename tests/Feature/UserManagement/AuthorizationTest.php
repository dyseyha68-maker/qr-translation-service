<?php

declare(strict_types=1);

use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('super admin can access user management index', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(RoleName::SUPER_ADMIN->value);

    $response = $this->actingAs($superAdmin)->get(route('users.index'));

    $response->assertOk();
});

test('admin can access user management index', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RoleName::ADMIN->value);

    $response = $this->actingAs($admin)->get(route('users.index'));

    $response->assertOk();
});

test('staff cannot access user management index', function () {
    $staff = User::factory()->create();
    $staff->assignRole(RoleName::STAFF->value);

    $response = $this->actingAs($staff)->get(route('users.index'));

    $response->assertForbidden();
});

test('finance cannot access user management index', function () {
    $finance = User::factory()->create();
    $finance->assignRole(RoleName::FINANCE->value);

    $response = $this->actingAs($finance)->get(route('users.index'));

    $response->assertForbidden();
});

test('all authenticated roles can access dashboard', function () {
    foreach (RoleName::cases() as $role) {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();
    }
});
