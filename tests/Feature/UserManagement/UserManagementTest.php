<?php

declare(strict_types=1);

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('authorized super admin can create a user', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(RoleName::SUPER_ADMIN->value);

    $response = $this->actingAs($superAdmin)->post(route('users.store'), [
        'name' => 'Sokha Chan',
        'email' => 'sokha@translation.local',
        'password' => 'Password123!',
        'role' => RoleName::STAFF->value,
        'status' => UserStatus::ACTIVE->value,
    ]);

    $response->assertRedirect(route('users.index'));
    $this->assertDatabaseHas('users', [
        'name' => 'Sokha Chan',
        'email' => 'sokha@translation.local',
        'status' => UserStatus::ACTIVE->value,
    ]);

    $created = User::where('email', 'sokha@translation.local')->first();
    expect($created)->not->toBeNull();
    expect($created->hasRole(RoleName::STAFF->value))->toBeTrue();
});

test('authorized admin can update a user', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RoleName::ADMIN->value);

    $target = User::factory()->create([
        'name' => 'Old Name',
        'status' => UserStatus::ACTIVE,
    ]);
    $target->assignRole(RoleName::STAFF->value);

    $response = $this->actingAs($admin)->put(route('users.update', $target), [
        'name' => 'Updated Name',
        'email' => $target->email,
        'role' => RoleName::STAFF->value,
        'status' => UserStatus::INACTIVE->value,
    ]);

    $response->assertRedirect(route('users.index'));
    $target->refresh();
    expect($target->name)->toBe('Updated Name');
    expect($target->status)->toBe(UserStatus::INACTIVE);
});

test('duplicate email is rejected during creation', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(RoleName::SUPER_ADMIN->value);

    $existing = User::factory()->create(['email' => 'existing@translation.local']);

    $response = $this->actingAs($superAdmin)->post(route('users.store'), [
        'name' => 'New User',
        'email' => 'existing@translation.local',
        'password' => 'Password123!',
        'role' => RoleName::STAFF->value,
        'status' => UserStatus::ACTIVE->value,
    ]);

    $response->assertSessionHasErrors('email');
});

test('invalid email is rejected', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(RoleName::SUPER_ADMIN->value);

    $response = $this->actingAs($superAdmin)->post(route('users.store'), [
        'name' => 'New User',
        'email' => 'not-a-valid-email',
        'password' => 'Password123!',
        'role' => RoleName::STAFF->value,
        'status' => UserStatus::ACTIVE->value,
    ]);

    $response->assertSessionHasErrors('email');
});

test('invalid role is rejected', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(RoleName::SUPER_ADMIN->value);

    $response = $this->actingAs($superAdmin)->post(route('users.store'), [
        'name' => 'New User',
        'email' => 'valid@translation.local',
        'password' => 'Password123!',
        'role' => 'HACKER_ROLE',
        'status' => UserStatus::ACTIVE->value,
    ]);

    $response->assertSessionHasErrors('role');
});

test('invalid status is rejected', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(RoleName::SUPER_ADMIN->value);

    $response = $this->actingAs($superAdmin)->post(route('users.store'), [
        'name' => 'New User',
        'email' => 'valid@translation.local',
        'password' => 'Password123!',
        'role' => RoleName::STAFF->value,
        'status' => 'UNKNOWN_STATUS',
    ]);

    $response->assertSessionHasErrors('status');
});

test('unauthorized user cannot create user', function () {
    $staff = User::factory()->create();
    $staff->assignRole(RoleName::STAFF->value);

    $response = $this->actingAs($staff)->post(route('users.store'), [
        'name' => 'Staff Created',
        'email' => 'staffcreated@translation.local',
        'password' => 'Password123!',
        'role' => RoleName::STAFF->value,
        'status' => UserStatus::ACTIVE->value,
    ]);

    $response->assertForbidden();
});

test('unauthorized user cannot update user', function () {
    $staff = User::factory()->create();
    $staff->assignRole(RoleName::STAFF->value);

    $target = User::factory()->create();
    $target->assignRole(RoleName::FINANCE->value);

    $response = $this->actingAs($staff)->put(route('users.update', $target), [
        'name' => 'Hacked Name',
        'email' => $target->email,
        'role' => RoleName::FINANCE->value,
        'status' => UserStatus::ACTIVE->value,
    ]);

    $response->assertForbidden();
});
