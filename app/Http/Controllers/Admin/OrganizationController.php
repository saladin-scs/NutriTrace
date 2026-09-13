<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Organizations\CreateOrganizationAction;
use App\Actions\Organizations\UpdateOrganizationAction;
use App\Actions\Organizations\VerifyOrganizationAction;
use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organizations\StoreOrganizationRequest;
use App\Http\Requests\Organizations\UpdateOrganizationRequest;
use App\Http\Requests\Organizations\VerifyOrganizationRequest;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function index(Request $request): View
    {
        $organizations = Organization::query()
            ->with(['primaryLocation', 'users'])
            ->when($request->string('q')->toString(), function ($query, string $q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('slug', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->when($request->string('status')->toString(), fn ($q, $status) => $q->where('status', $status))
            ->when($request->string('type')->toString(), fn ($q, $type) => $q->where('type', $type))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.organizations.index', [
            'organizations' => $organizations,
            'types' => OrganizationType::cases(),
            'statuses' => OrganizationStatus::cases(),
        ]);
    }

    public function create(): View
    {
        return view('admin.organizations.create', [
            'types' => OrganizationType::cases(),
        ]);
    }

    public function store(StoreOrganizationRequest $request, CreateOrganizationAction $action): RedirectResponse
    {
        $organization = $action->execute($request->user(), $request->validated());

        if ($request->boolean('verify_now')) {
            app(VerifyOrganizationAction::class)->execute(
                $request->user(),
                $organization,
                OrganizationStatus::Verified,
            );
        }

        return redirect()
            ->route('admin.organizations.show', $organization)
            ->with('success', 'Organisation créée.');
    }

    public function show(Organization $organization): View
    {
        $organization->load(['primaryLocation', 'locations', 'users', 'verifier']);

        return view('admin.organizations.show', [
            'organization' => $organization,
            'statuses' => OrganizationStatus::cases(),
        ]);
    }

    public function edit(Organization $organization): View
    {
        $organization->load('primaryLocation');

        return view('admin.organizations.edit', [
            'organization' => $organization,
            'types' => OrganizationType::cases(),
        ]);
    }

    public function update(
        UpdateOrganizationRequest $request,
        Organization $organization,
        UpdateOrganizationAction $action,
    ): RedirectResponse {
        $action->execute($request->user(), $organization, $request->validated());

        return redirect()
            ->route('admin.organizations.show', $organization)
            ->with('success', 'Organisation mise à jour.');
    }

    public function verify(
        VerifyOrganizationRequest $request,
        Organization $organization,
        VerifyOrganizationAction $action,
    ): RedirectResponse {
        $status = OrganizationStatus::from($request->validated('status'));
        $action->execute($request->user(), $organization, $status);

        return back()->with('success', 'Statut organisation mis à jour : '.$status->label());
    }

    public function destroy(Organization $organization): RedirectResponse
    {
        $this->authorize('delete', $organization);
        $organization->delete();

        return redirect()
            ->route('admin.organizations.index')
            ->with('success', 'Organisation archivée.');
    }
}
