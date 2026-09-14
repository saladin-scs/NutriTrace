<?php

namespace App\Http\Controllers;

use App\Actions\Anomalies\UpdateAnomalyStatusAction;
use App\Domain\Analytics\AlertCenterQuery;
use App\Enums\AnomalyCategory;
use App\Enums\AnomalySeverity;
use App\Enums\AnomalyStatus;
use App\Facades\Analytics;
use App\Models\Anomaly;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AlertCenterController extends Controller
{
    public function index(Request $request, AlertCenterQuery $query): View
    {
        $this->authorize('viewAny', Anomaly::class);

        $filters = $request->only(['severity', 'category', 'status', 'q']);

        return view('alert-center.index', [
            'payload' => $query->payload($filters),
            'anomalies' => $query->paginate($filters),
            'severities' => AnomalySeverity::cases(),
            'categories' => AnomalyCategory::cases(),
            'statuses' => AnomalyStatus::cases(),
            'filters' => $filters,
        ]);
    }

    public function show(Anomaly $anomaly): View
    {
        $this->authorize('view', $anomaly);

        $anomaly->load([
            'coldRoom', 'shipment', 'batch.product', 'organization',
            'vehicle', 'detector', 'acknowledger',
        ]);

        return view('alert-center.show', compact('anomaly'));
    }

    public function updateStatus(
        Request $request,
        Anomaly $anomaly,
        UpdateAnomalyStatusAction $action,
    ): RedirectResponse {
        $this->authorize('update', $anomaly);

        $data = $request->validate([
            'status' => ['required', 'in:acknowledged,investigating,resolved,dismissed'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $action->execute(
            $request->user(),
            $anomaly,
            AnomalyStatus::from($data['status']),
            $data['note'] ?? null,
        );

        return back()->with('success', 'Statut d’alerte mis à jour.');
    }

    public function scan(Request $request): RedirectResponse
    {
        $this->authorize('scan', Anomaly::class);

        $created = Analytics::scanAnomalies($request->user());

        return redirect()
            ->route('alert-center.index')
            ->with('success', count($created).' anomalie(s) détectée(s) / rafraîchie(s).');
    }
}
