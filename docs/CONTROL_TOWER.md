# Control Tower — Phases 1–3

## Lien avec l’existant

| Existant | Rôle Control Tower |
|----------|-------------------|
| `Organization` / `Location` | Acteurs + géoloc des nœuds |
| `Batch` / `TraceabilityEvent` | Lots + timeline événementielle |
| `Distribution` | Transfert métier lot (inchangé) |
| `ColdRoom` + `ColdRoomMovement` | Nœud stratégique + historique flux |
| **Phase 1** `Shipment` / `Vehicle` / `Route` | Unité ops logistique |
| **Phase 2** `StorageRecord` / `TemperatureRecord` / `StockMovement` | Ledger stock + T° + grand livre |
| **Facade** `ColdChain` | Twin / FEFO / mass-balance / sensor |
| **Phase 3** `Anomaly` + Analytics domain | KPI engine, Health Score, Alert Center |
| **Facade** `Analytics` | `dashboard()`, `health()`, `scanAnomalies()`, `alertCenter()` |

## Supply Chain Health Score

Pondération documentée (configurable) :

| Pilier | Poids |
|--------|------:|
| Delivery performance | 30% |
| Cold chain compliance | 25% |
| Traceability coverage | 20% |
| Waste performance | 15% |
| Environmental | 10% |

Score 0–100 + label (Excellent → Critique). **Aide à la décision uniquement.**

## Anomalies (éthique)

Flux : **anomalie → preuve → alerte → vérification humaine**  
Pas d’accusation automatique (spéculation, rétention, fraude…).

Catégories : delay, temperature, capacity, storage duration, near expiry, mass balance, stock concentration, excessive loss…

## UI / API

- Web : `/control-tower` (onglets Opérations + Intelligence), `/alert-center`, `/cold-rooms/{id}`, `/shipments`
- `/analytics` redirige vers Control Tower → Intelligence
- API : `/api/v1/analytics/kpis|health|waste|cold-chain|environment`
- API : `/api/v1/alerts`, `POST /alerts/scan`, `PATCH /alerts/{id}`
- Artisan : `php artisan nutritrace:scan-anomalies` (planifié toutes les 15 min)

## Cache

`KpiService` cache le dashboard ~60s (`Cache` — Redis si `CACHE_STORE=redis`).

## Docker Desktop

```bash
docker compose up -d mysql redis
docker compose up -d --build app
```
