<?php

namespace App\Http\Controllers;

use App\Actions\ColdRooms\CreateColdRoomAction;
use App\Actions\ColdRooms\RecordColdRoomMovementAction;
use App\Domain\ColdRoom\ColdRoomFlowInterpreter;
use App\Enums\ColdRoomMovementType;
use App\Enums\ColdRoomType;
use App\Facades\ColdChain;
use App\Http\Requests\ColdRooms\RecordColdRoomMovementRequest;
use App\Http\Requests\ColdRooms\StoreColdRoomRequest;
use App\Models\Batch;
use App\Models\ColdRoom;
use App\Models\Organization;
use App\View\Presenters\ColdRoomTwinPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ColdRoomController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ColdRoom::class);

        $rooms = ColdRoom::query()
            ->with(['organization', 'location', 'responsible'])
            ->withCount(['movements', 'openStorageRecords'])
            ->when(! $request->user()->isAdmin(), function ($query) use ($request) {
                $query->whereIn('organization_id', $request->user()->organizations()->pluck('organizations.id'));
            })
            ->latest()
            ->paginate(12);

        return view('cold-rooms.index', compact('rooms'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', ColdRoom::class);

        $organizations = $request->user()->isAdmin()
            ? Organization::query()->orderBy('name')->get()
            : $request->user()->organizations()->orderBy('name')->get();

        return view('cold-rooms.create', [
            'organizations' => $organizations,
            'types' => ColdRoomType::cases(),
        ]);
    }

    public function store(StoreColdRoomRequest $request, CreateColdRoomAction $action): RedirectResponse
    {
        $this->authorize('create', ColdRoom::class);

        $organization = Organization::query()->findOrFail($request->validated('organization_id'));
        abort_unless(
            $request->user()->isAdmin() || $request->user()->belongsToOrganization($organization),
            403
        );

        $room = $action->execute($request->user(), $organization, $request->validated());

        return redirect()
            ->route('cold-rooms.show', $room)
            ->with('success', 'Chambre froide créée — nœud stratégique du réseau prêt.');
    }

    public function show(ColdRoom $coldRoom, ColdRoomFlowInterpreter $interpreter): View
    {
        $this->authorize('view', $coldRoom);

        $coldRoom->load(['organization', 'ownerOrganization', 'location', 'responsible']);

        $twin = new ColdRoomTwinPresenter(ColdChain::twin($coldRoom));

        $movements = $coldRoom->movements()
            ->with([
                'batch', 'actor',
                'fromOrganization', 'toOrganization',
                'fromLocation', 'toLocation',
                'fromColdRoom', 'toColdRoom',
            ])
            ->limit(40)
            ->get();

        $flows = $movements->map(fn ($m) => [
            'movement' => $m,
            'snapshot' => $interpreter->interpret($m),
        ]);

        $batches = Batch::query()
            ->where(function ($query) use ($coldRoom) {
                $query->where('organization_id', $coldRoom->organization_id)
                    ->orWhereHas('distributions', fn ($q) => $q->where('to_organization_id', $coldRoom->organization_id));
            })
            ->latest()
            ->limit(50)
            ->get();

        return view('cold-rooms.show', [
            'coldRoom' => $coldRoom,
            'twin' => $twin,
            'flows' => $flows,
            'batches' => $batches,
            'movementTypes' => ColdRoomMovementType::cases(),
            'organizations' => Organization::query()->orderBy('name')->get(),
            'opsContext' => [
                'shipment' => session('ops.shipment_code'),
                'batch' => session('ops.batch_code'),
            ],
        ]);
    }

    public function recordMovement(
        RecordColdRoomMovementRequest $request,
        ColdRoom $coldRoom,
        RecordColdRoomMovementAction $action,
    ): RedirectResponse {
        $this->authorize('recordMovement', $coldRoom);

        $action->execute($request->user(), $coldRoom, $request->validated());

        return back()->with('success', 'Flux historisé — stock, occupation et traçabilité mis à jour.');
    }

    public function recordTemperature(Request $request, ColdRoom $coldRoom): RedirectResponse
    {
        $this->authorize('recordMovement', $coldRoom);

        $data = $request->validate([
            'temperature_c' => ['nullable', 'numeric'],
        ]);

        ColdChain::recordSensorReading(
            $coldRoom,
            $request->user(),
            isset($data['temperature_c']) ? (float) $data['temperature_c'] : null,
        );

        return back()->with('success', 'Relevé de température enregistré.');
    }
}
