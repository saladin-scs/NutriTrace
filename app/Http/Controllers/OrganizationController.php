<?php

namespace App\Http\Controllers;

use App\Actions\Organizations\AttachOrganizationMemberAction;
use App\Actions\Organizations\CreateOrganizationAction;
use App\Actions\Organizations\DetachOrganizationMemberAction;
use App\Actions\Organizations\UpdateOrganizationAction;
use App\Enums\OrganizationType;
use App\Http\Requests\Organizations\AttachOrganizationMemberRequest;
use App\Http\Requests\Organizations\StoreOrganizationRequest;
use App\Http\Requests\Organizations\UpdateOrganizationRequest;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Organization::class);

        $organizations = $request->user()
            ->organizations()
            ->with(['primaryLocation', 'users'])
            ->latest()
            ->paginate(12);

        return view('organizations.index', compact('organizations'));
    }

    public function create(): View
    {
        $this->authorize('create', Organization::class);

        return view('organizations.create', [
            'types' => OrganizationType::cases(),
        ]);
    }

    public function store(StoreOrganizationRequest $request, CreateOrganizationAction $action): RedirectResponse
    {
        $this->authorize('create', Organization::class);

        $organization = $action->execute($request->user(), $request->validated());

        return redirect()
            ->route('organizations.show', $organization)
            ->with('success', 'Organisation créée. Elle sera vérifiée par un administrateur.');
    }

    public function show(Organization $organization): View
    {
        $this->authorize('view', $organization);

        $organization->load(['primaryLocation', 'locations', 'users', 'verifier']);

        return view('organizations.show', [
            'organization' => $organization,
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    public function edit(Organization $organization): View
    {
        $this->authorize('update', $organization);

        $organization->load('primaryLocation');

        return view('organizations.edit', [
            'organization' => $organization,
            'types' => OrganizationType::cases(),
        ]);
    }

    public function update(
        UpdateOrganizationRequest $request,
        Organization $organization,
        UpdateOrganizationAction $action,
    ): RedirectResponse {
        $this->authorize('update', $organization);

        $action->execute($request->user(), $organization, $request->validated());

        return redirect()
            ->route('organizations.show', $organization)
            ->with('success', 'Organisation mise à jour.');
    }

    public function attachMember(
        AttachOrganizationMemberRequest $request,
        Organization $organization,
        AttachOrganizationMemberAction $action,
    ): RedirectResponse {
        $this->authorize('manageMembers', $organization);

        $member = User::query()->where('email', $request->validated('email'))->firstOrFail();
        $role = isset($request->validated()['role_id'])
            ? Role::query()->find($request->validated('role_id'))
            : null;

        $action->execute(
            $request->user(),
            $organization,
            $member,
            $role,
            (bool) ($request->validated('is_primary') ?? false),
            $request->validated('job_title'),
        );

        return back()->with('success', 'Membre ajouté à l’organisation.');
    }

    public function detachMember(
        Request $request,
        Organization $organization,
        User $user,
        DetachOrganizationMemberAction $action,
    ): RedirectResponse {
        $this->authorize('manageMembers', $organization);

        $action->execute($request->user(), $organization, $user);

        return back()->with('success', 'Membre retiré de l’organisation.');
    }
}
