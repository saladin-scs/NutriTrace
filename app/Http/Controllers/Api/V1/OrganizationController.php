<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Organizations\AttachOrganizationMemberAction;
use App\Actions\Organizations\CreateOrganizationAction;
use App\Actions\Organizations\DetachOrganizationMemberAction;
use App\Actions\Organizations\UpdateOrganizationAction;
use App\Actions\Organizations\VerifyOrganizationAction;
use App\Enums\OrganizationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organizations\AttachOrganizationMemberRequest;
use App\Http\Requests\Organizations\StoreOrganizationRequest;
use App\Http\Requests\Organizations\UpdateOrganizationRequest;
use App\Http\Requests\Organizations\VerifyOrganizationRequest;
use App\Http\Resources\Api\V1\OrganizationResource;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrganizationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Organization::class);

        $organizations = ($request->user()->isAdmin()
            ? Organization::query()
            : $request->user()->organizations()
        )
            ->with(['primaryLocation'])
            ->withCount('users')
            ->latest()
            ->paginate(20);

        return OrganizationResource::collection($organizations);
    }

    public function store(StoreOrganizationRequest $request, CreateOrganizationAction $action): JsonResponse
    {
        $this->authorize('create', Organization::class);

        $organization = $action->execute($request->user(), $request->validated());

        return response()->json([
            'data' => OrganizationResource::make($organization)->resolve(),
        ], 201);
    }

    public function show(Organization $organization): OrganizationResource
    {
        $this->authorize('view', $organization);

        $organization->load(['primaryLocation', 'users'])->loadCount('users');

        return OrganizationResource::make($organization);
    }

    public function update(
        UpdateOrganizationRequest $request,
        Organization $organization,
        UpdateOrganizationAction $action,
    ): OrganizationResource {
        $this->authorize('update', $organization);

        $organization = $action->execute($request->user(), $organization, $request->validated());

        return OrganizationResource::make($organization->load(['primaryLocation', 'users'])->loadCount('users'));
    }

    public function verify(
        VerifyOrganizationRequest $request,
        Organization $organization,
        VerifyOrganizationAction $action,
    ): OrganizationResource {
        $this->authorize('verify', $organization);

        $status = OrganizationStatus::from($request->validated('status'));
        $organization = $action->execute($request->user(), $organization, $status);

        return OrganizationResource::make($organization);
    }

    public function attachMember(
        AttachOrganizationMemberRequest $request,
        Organization $organization,
        AttachOrganizationMemberAction $action,
    ): OrganizationResource {
        $this->authorize('manageMembers', $organization);

        $member = User::query()->where('email', $request->validated('email'))->firstOrFail();
        $role = isset($request->validated()['role_id'])
            ? Role::query()->find($request->validated('role_id'))
            : null;

        $organization = $action->execute(
            $request->user(),
            $organization,
            $member,
            $role,
            (bool) ($request->validated('is_primary') ?? false),
            $request->validated('job_title') ?? null,
        );

        return OrganizationResource::make($organization->load(['primaryLocation', 'users']));
    }

    public function detachMember(
        Request $request,
        Organization $organization,
        User $user,
        DetachOrganizationMemberAction $action,
    ): OrganizationResource {
        $this->authorize('manageMembers', $organization);

        $organization = $action->execute($request->user(), $organization, $user);

        return OrganizationResource::make($organization->load(['primaryLocation', 'users']));
    }
}
