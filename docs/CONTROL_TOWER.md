# Control Tower — Phase 1

## Lien avec l’existant

| Existant | Rôle Control Tower |
|----------|-------------------|
| `Organization` / `Location` | Acteurs + géoloc des nœuds |
| `Batch` / `TraceabilityEvent` | Lots + timeline événementielle |
| `Distribution` | Transfert métier lot (inchangé) |
| `ColdRoom` | Nœud cold_room sur le canal |
| **Nouveau** `Shipment` | Unité ops logistique (véhicule, route, ETA, statut) |
| **Nouveau** `DistributionChannel` / `Node` / `Link` | Graphe du réseau |
| **Nouveau** `Vehicle` / `Route` / `VehiclePosition` | Tracking prêt IoT |

## Formule CO₂e (estimation)

`CO2e = distance_km × emission_factor × load_factor`

où `load_factor = clamp(load_kg / capacity_kg, 0.3, 1.2)` (sinon 1.0).

## UI

- Web : `/control-tower` (carte Leaflet + KPIs + panneau détail)
- Shipments : `/shipments`
- API : `/api/v1/dashboard/control-tower`, `/api/v1/shipments`, `/api/v1/vehicles`, `/api/v1/batches/{batch}/timeline`

## Seed

`ControlTowerDemoSeeder` — Bizerte → Tunis DC → Sousse → Sfax + resto Médina.
