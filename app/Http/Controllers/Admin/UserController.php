<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Users\AssignPlatformRoleAction;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Users\AssignPlatformRoleRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->withCount('organizations')
            ->when($request->string('q')->toString(), function ($query, string $q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->when($request->string('role')->toString(), fn ($q, $role) => $q->where('role', $role))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => UserRole::cases(),
        ]);
    }

    public function show(User $user): View
    {
        $this->authorize('view', $user);

        $user->load(['organizations.primaryLocation']);

        return view('admin.users.show', [
            'user' => $user,
            'roles' => UserRole::cases(),
        ]);
    }

    public function assignRole(
        AssignPlatformRoleRequest $request,
        User $user,
        AssignPlatformRoleAction $action,
    ): RedirectResponse {
        $this->authorize('updateRole', $user);

        $role = UserRole::from($request->validated('role'));
        $action->execute($request->user(), $user, $role);

        return back()->with('success', 'Rôle plateforme mis à jour : '.$role->label());
    }
}
