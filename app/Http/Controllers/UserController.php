<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(
        protected UserManagementService $userManagementService
    ) {}

    /**
     * Display a listing of the users.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        /** @var User $currentUser */
        $currentUser = $request->user();

        $users = User::with('roles')
            ->orderBy('id', 'asc')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status->value,
                'role' => $user->roles->first()?->name ?? 'None',
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
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = $request->user();

        $this->userManagementService->createUser($request->validated(), $currentUser);

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
                'status' => $user->status->value,
                'role' => $user->roles->first()?->name ?? 'STAFF',
            ],
            'roles' => $roles,
            'statuses' => UserStatus::values(),
        ]);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = $request->user();

        $this->userManagementService->updateUser($user, $request->validated(), $currentUser);

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

        $this->userManagementService->deleteUser($user, $currentUser);

        return redirect()->route('users.index')->with('success', 'User successfully deleted.');
    }
}
