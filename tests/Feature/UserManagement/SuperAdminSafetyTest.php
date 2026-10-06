<?php

declare(strict_types=1);

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('final active super admin cannot be deleted', function () {
    $soleSuperAdmin = User::factory()->create([
        'status' => UserStatus::ACTIVE,
    ]);
    $soleSuperAdmin->assignRole(RoleName::SUPER_ADMIN->value);

    // 1. Admin attempts to delete -> Forbidden (403)
    $admin = User::factory()->create([
        'status' => UserStatus::ACTIVE,
    ]);
    $admin->assignRole(RoleName::ADMIN->value);

    $adminDeleteResponse = $this->actingAs($admin)->delete(route('users.destroy', $soleSuperAdmin));
    $adminDeleteResponse->assertForbidden();

    // 2. Self deletion attempt -> Forbidden (403)
    $selfDeleteResponse = $this->actingAs($soleSuperAdmin)->delete(route('users.destroy', $soleSuperAdmin));
    $selfDeleteResponse->assertForbidden();

    // 3. User remains intact in database
    expect(User::find($soleSuperAdmin->id))->not->toBeNull();
});

test('inactive super admin session is immediately rejected', function () {
    $inactiveSuperAdmin = User::factory()->create([
        'status' => UserStatus::INACTIVE,
    ]);
    $inactiveSuperAdmin->assignRole(RoleName::SUPER_ADMIN->value);

    $target = User::factory()->create();

    $response = $this->actingAs($inactiveSuperAdmin)->delete(route('users.destroy', $target));
    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors('email');
});

test('final active super admin cannot be deactivated', function () {
    $soleSuperAdmin = User::factory()->create([
        'status' => UserStatus::ACTIVE,
    ]);
    $soleSuperAdmin->assignRole(RoleName::SUPER_ADMIN->value);

    $response = $this->actingAs($soleSuperAdmin)->put(route('users.update', $soleSuperAdmin), [
        'name' => $soleSuperAdmin->name,
        'email' => $soleSuperAdmin->email,
        'role' => RoleName::SUPER_ADMIN->value,
        'status' => UserStatus::INACTIVE->value,
    ]);

    $response->assertSessionHasErrors('status');
    $soleSuperAdmin->refresh();
    expect($soleSuperAdmin->status)->toBe(UserStatus::ACTIVE);
});

test('final active super admin cannot have super admin role removed', function () {
    $soleSuperAdmin = User::factory()->create([
        'status' => UserStatus::ACTIVE,
    ]);
    $soleSuperAdmin->assignRole(RoleName::SUPER_ADMIN->value);

    $response = $this->actingAs($soleSuperAdmin)->put(route('users.update', $soleSuperAdmin), [
        'name' => $soleSuperAdmin->name,
        'email' => $soleSuperAdmin->email,
        'role' => RoleName::STAFF->value,
        'status' => UserStatus::ACTIVE->value,
    ]);

    $response->assertSessionHasErrors('role');
    $soleSuperAdmin->refresh();
    expect($soleSuperAdmin->hasRole(RoleName::SUPER_ADMIN->value))->toBeTrue();
});

test('unauthorized admin cannot assign super admin role on create', function () {
    $admin = User::factory()->create([
        'status' => UserStatus::ACTIVE,
    ]);
    $admin->assignRole(RoleName::ADMIN->value);

    $response = $this->actingAs($admin)->post(route('users.store'), [
        'name' => 'Escalated User',
        'email' => 'escalated@translation.local',
        'password' => 'Password123!',
        'role' => RoleName::SUPER_ADMIN->value,
        'status' => UserStatus::ACTIVE->value,
    ]);

    $response->assertForbidden();
    $this->assertDatabaseMissing('users', ['email' => 'escalated@translation.local']);
});

test('unauthorized admin cannot assign super admin role on update', function () {
    $admin = User::factory()->create([
        'status' => UserStatus::ACTIVE,
    ]);
    $admin->assignRole(RoleName::ADMIN->value);

    $targetStaff = User::factory()->create([
        'status' => UserStatus::ACTIVE,
    ]);
    $targetStaff->assignRole(RoleName::STAFF->value);

    $response = $this->actingAs($admin)->put(route('users.update', $targetStaff), [
        'name' => $targetStaff->name,
        'email' => $targetStaff->email,
        'role' => RoleName::SUPER_ADMIN->value,
        'status' => UserStatus::ACTIVE->value,
    ]);

    $response->assertForbidden();
    $targetStaff->refresh();
    expect($targetStaff->hasRole(RoleName::SUPER_ADMIN->value))->toBeFalse();
});

test('unauthorized admin cannot edit or modify a super admin user', function () {
    $admin = User::factory()->create([
        'status' => UserStatus::ACTIVE,
    ]);
    $admin->assignRole(RoleName::ADMIN->value);

    $superAdmin = User::factory()->create([
        'status' => UserStatus::ACTIVE,
    ]);
    $superAdmin->assignRole(RoleName::SUPER_ADMIN->value);

    $response = $this->actingAs($admin)->put(route('users.update', $superAdmin), [
        'name' => 'Modified Name',
        'email' => $superAdmin->email,
        'role' => RoleName::STAFF->value,
        'status' => UserStatus::ACTIVE->value,
    ]);

    $response->assertForbidden();
    $superAdmin->refresh();
    expect($superAdmin->hasRole(RoleName::SUPER_ADMIN->value))->toBeTrue();
});
