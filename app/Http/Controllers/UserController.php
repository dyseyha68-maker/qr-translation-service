<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(
        protected UserManagementService $userManagementService
    ) {}

    /**
     * Group registered permissions into human-friendly categories.
     *
     * @return array<string, array<int, array{name: string, label: string}>>
     */
    public static function getCategorizedPermissions(): array
    {
        return [
            'User Accounts & Access' => [
                ['name' => PermissionName::USERS_VIEW->value, 'label' => 'View Users'],
                ['name' => PermissionName::USERS_CREATE->value, 'label' => 'Create Users'],
                ['name' => PermissionName::USERS_UPDATE->value, 'label' => 'Edit Users'],
                ['name' => PermissionName::USERS_DELETE->value, 'label' => 'Delete Users'],
                ['name' => PermissionName::ROLES_VIEW->value, 'label' => 'View Roles'],
                ['name' => PermissionName::ROLES_ASSIGN->value, 'label' => 'Assign Roles'],
            ],
            'Referral Fleet & QR' => [
                ['name' => PermissionName::PARTNERS_VIEW->value, 'label' => 'View Partners'],
                ['name' => PermissionName::PARTNERS_CREATE->value, 'label' => 'Create Partners'],
                ['name' => PermissionName::PARTNERS_UPDATE->value, 'label' => 'Edit Partners'],
                ['name' => PermissionName::PARTNERS_DELETE->value, 'label' => 'Delete Partners'],
                ['name' => PermissionName::DRIVERS_VIEW->value, 'label' => 'View Drivers'],
                ['name' => PermissionName::DRIVERS_CREATE->value, 'label' => 'Create Drivers'],
                ['name' => PermissionName::DRIVERS_UPDATE->value, 'label' => 'Edit Drivers'],
                ['name' => PermissionName::DRIVERS_DELETE->value, 'label' => 'Delete Drivers'],
                ['name' => PermissionName::VEHICLES_VIEW->value, 'label' => 'View Vehicles'],
                ['name' => PermissionName::VEHICLES_CREATE->value, 'label' => 'Create Vehicles'],
                ['name' => PermissionName::VEHICLES_UPDATE->value, 'label' => 'Edit Vehicles'],
                ['name' => PermissionName::VEHICLES_DELETE->value, 'label' => 'Delete Vehicles'],
                ['name' => PermissionName::REFERRAL_CODES_VIEW->value, 'label' => 'View Referral Codes'],
                ['name' => PermissionName::REFERRAL_CODES_CREATE->value, 'label' => 'Create QR Codes'],
                ['name' => PermissionName::REFERRAL_CODES_UPDATE->value, 'label' => 'Edit Referral Codes'],
                ['name' => PermissionName::REFERRAL_CODES_DELETE->value, 'label' => 'Delete Referral Codes'],
                ['name' => PermissionName::REFERRAL_CODES_ACTIVATE->value, 'label' => 'Activate QR Codes'],
                ['name' => PermissionName::REFERRAL_CODES_DEACTIVATE->value, 'label' => 'Deactivate QR Codes'],
                ['name' => PermissionName::REFERRAL_CODES_QR_VIEW->value, 'label' => 'Display QR Codes'],
                ['name' => PermissionName::REFERRAL_CODES_QR_DOWNLOAD->value, 'label' => 'Download QR Vectors'],
            ],
            'Leads & Telegram Intake' => [
                ['name' => PermissionName::LEADS_VIEW->value, 'label' => 'View Leads'],
                ['name' => PermissionName::LEADS_CREATE->value, 'label' => 'Intake New Leads'],
                ['name' => PermissionName::LEADS_UPDATE->value, 'label' => 'Update Lead Status'],
                ['name' => PermissionName::LEADS_ASSIGN->value, 'label' => 'Assign Leads to Staff'],
                ['name' => PermissionName::LEADS_CHANGE_ATTRIBUTION->value, 'label' => 'Re-attribute Referral Drivers'],
                ['name' => PermissionName::LEADS_ADD_NOTE->value, 'label' => 'Add Internal Lead Notes'],
                ['name' => PermissionName::CUSTOMERS_VIEW->value, 'label' => 'View Telegram Customers'],
            ],
            'Orders & Customer Payments' => [
                ['name' => PermissionName::ORDERS_VIEW->value, 'label' => 'View Orders'],
                ['name' => PermissionName::ORDERS_CREATE->value, 'label' => 'Create Orders'],
                ['name' => PermissionName::ORDERS_UPDATE->value, 'label' => 'Edit Orders'],
                ['name' => PermissionName::ORDERS_CONFIRM->value, 'label' => 'Confirm Orders'],
                ['name' => PermissionName::ORDERS_CANCEL->value, 'label' => 'Cancel Orders'],
                ['name' => PermissionName::ORDERS_COMPLETE->value, 'label' => 'Complete Orders'],
                ['name' => PermissionName::ORDERS_DELETE->value, 'label' => 'Delete Orders'],
                ['name' => PermissionName::PAYMENTS_VIEW->value, 'label' => 'View Customer Payments'],
                ['name' => PermissionName::PAYMENTS_CREATE->value, 'label' => 'Record Payments'],
                ['name' => PermissionName::PAYMENTS_UPDATE->value, 'label' => 'Edit Payment Records'],
                ['name' => PermissionName::PAYMENTS_REFUND->value, 'label' => 'Process Payment Refunds'],
            ],
            'Commissions & Driver Payouts' => [
                ['name' => PermissionName::COMMISSIONS_VIEW->value, 'label' => 'View Commissions'],
                ['name' => PermissionName::COMMISSIONS_CREATE->value, 'label' => 'Generate Commissions'],
                ['name' => PermissionName::COMMISSIONS_UPDATE->value, 'label' => 'Edit Commission Rates'],
                ['name' => PermissionName::COMMISSIONS_APPROVE->value, 'label' => 'Approve Commissions'],
                ['name' => PermissionName::COMMISSIONS_PAY->value, 'label' => 'Disburse Driver Payouts'],
                ['name' => PermissionName::COMMISSIONS_REVERSE->value, 'label' => 'Reverse Commissions'],
                ['name' => PermissionName::COMMISSIONS_DELETE->value, 'label' => 'Delete Commissions'],
                ['name' => PermissionName::COMMISSIONS_REPORTS->value, 'label' => 'Access Commission Reports'],
                ['name' => PermissionName::COMMISSION_PAYMENTS_VIEW->value, 'label' => 'View Payout Transactions'],
                ['name' => PermissionName::COMMISSION_PAYMENTS_CREATE->value, 'label' => 'Record Payout Transactions'],
                ['name' => PermissionName::COMMISSION_PAYMENTS_UPDATE->value, 'label' => 'Update Payout Details'],
            ],
            'Reports & Analytics' => [
                ['name' => PermissionName::REPORTS_VIEW->value, 'label' => 'View Performance Reports'],
                ['name' => PermissionName::REPORTS_EXPORT->value, 'label' => 'Export CSV Data'],
            ],
            'System Settings & Branding' => [
                ['name' => PermissionName::SETTINGS_VIEW->value, 'label' => 'View System Branding & Settings'],
                ['name' => PermissionName::SETTINGS_MANAGE->value, 'label' => 'Manage Colors, Logo & Settings'],
            ],
        ];
    }

    /**
     * Map of each role to its default permissions.
     *
     * @return array<string, array<int, string>>
     */
    public static function getRolePermissionsMap(): array
    {
        return Role::with('permissions')->get()->mapWithKeys(fn (Role $r) => [
            $r->name => $r->permissions->pluck('name')->values()->all(),
        ])->all();
    }

    /**
     * Display a listing of the users.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        /** @var User $currentUser */
        $currentUser = $request->user();

        $users = User::with(['roles', 'permissions'])
            ->orderBy('id', 'asc')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $user->avatar_url,
                'status' => $user->status->value,
                'role' => $user->roles->first()?->name ?? 'None',
                'permissions_count' => $user->getAllPermissions()->count(),
                'created_at' => $user->created_at?->format('Y-m-d H:i') ?? '',
                'can' => [
                    'edit' => $currentUser->can('update', $user),
                    'delete' => $currentUser->can('delete', $user),
                ],
            ]);

        return Inertia::render('Users/Index', [
            'users' => $users,
            'can' => [
                'create_user' => $currentUser->can('create', User::class),
            ],
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(Request $request): Response
    {
        Gate::authorize('create', User::class);

        /** @var User $currentUser */
        $currentUser = $request->user();

        $roles = collect(RoleName::values())
            ->filter(fn (string $role) => $currentUser->hasRole(RoleName::SUPER_ADMIN->value) || $role !== RoleName::SUPER_ADMIN->value)
            ->values();

        return Inertia::render('Users/Create', [
            'roles' => $roles,
            'statuses' => UserStatus::values(),
            'availablePermissions' => self::getCategorizedPermissions(),
            'rolePermissionsMap' => self::getRolePermissionsMap(),
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = $request->user();

        $validated = $request->validated();

        if ($request->hasFile('avatar')) {
            $validated['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
        }

        $this->userManagementService->createUser($validated, $currentUser);

        return redirect()->route('users.index')->with('success', 'User successfully created.');
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(Request $request, User $user): Response
    {
        Gate::authorize('update', $user);

        /** @var User $currentUser */
        $currentUser = $request->user();

        $roles = collect(RoleName::values())
            ->filter(fn (string $role) => $currentUser->hasRole(RoleName::SUPER_ADMIN->value) || $role !== RoleName::SUPER_ADMIN->value)
            ->values();

        return Inertia::render('Users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $user->avatar_url,
                'status' => $user->status->value,
                'role' => $user->roles->first()?->name ?? 'STAFF',
                'direct_permissions' => $user->permissions->pluck('name')->values()->all(),
                'all_permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
            ],
            'roles' => $roles,
            'statuses' => UserStatus::values(),
            'availablePermissions' => self::getCategorizedPermissions(),
            'rolePermissionsMap' => self::getRolePermissionsMap(),
        ]);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = $request->user();

        $validated = $request->validated();

        if ($request->boolean('remove_avatar')) {
            if ($user->avatar_path && Storage::disk('public')->exists($user->avatar_path)) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $validated['avatar_path'] = null;
        } elseif ($request->hasFile('avatar')) {
            if ($user->avatar_path && Storage::disk('public')->exists($user->avatar_path)) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $validated['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
        }

        $this->userManagementService->updateUser($user, $validated, $currentUser);

        return redirect()->route('users.index')->with('success', 'User successfully updated.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);

        /** @var User $currentUser */
        $currentUser = $request->user();

        if ($user->avatar_path && Storage::disk('public')->exists($user->avatar_path)) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $this->userManagementService->deleteUser($user, $currentUser);

        return redirect()->route('users.index')->with('success', 'User successfully deleted.');
    }
}
