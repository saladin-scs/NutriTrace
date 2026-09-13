@extends('layouts.front')

@section('title', 'Distribution Control Tower')

@section('wide')
<div
    class="nt-container py-6"
    x-data="ntControlTower(@js(['tower' => $tower]))"
>
    <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="nt-kicker">Ops live</p>
            <h1 class="nt-page-title mt-1">Distribution Control Tower</h1>
            <p class="nt-page-sub">Vue opérationnelle du réseau alimentaire — Tunisie</p>
        </div>
        <a href="{{ route('shipments.create') }}" class="nt-btn">Nouveau shipment</a>
    </div>

    <div class="mb-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
        <div class="rounded-2xl border border-[var(--nt-line)] bg-white/80 px-4 py-3">
            <p class="text-[11px] uppercase tracking-wide text-nt-ink/45">Shipments actifs</p>
            <p class="mt-1 font-display text-2xl font-semibold" x-text="kpis.active_shipments ?? 0"></p>
        </div>
        <div class="rounded-2xl border border-[var(--nt-line)] bg-white/80 px-4 py-3">
            <p class="text-[11px] uppercase tracking-wide text-nt-ink/45">Livraisons du jour</p>
            <p class="mt-1 font-display text-2xl font-semibold" x-text="kpis.deliveries_today ?? 0"></p>
        </div>
        <div class="rounded-2xl border border-[var(--nt-line)] bg-white/80 px-4 py-3">
            <p class="text-[11px] uppercase tracking-wide text-nt-ink/45">Retards</p>
            <p class="mt-1 font-display text-2xl font-semibold text-amber-700" x-text="kpis.delayed_shipments ?? 0"></p>
        </div>
        <div class="rounded-2xl border border-[var(--nt-line)] bg-white/80 px-4 py-3">
            <p class="text-[11px] uppercase tracking-wide text-nt-ink/45">Chambres froides</p>
            <p class="mt-1 font-display text-2xl font-semibold" x-text="kpis.cold_rooms_active ?? 0"></p>
        </div>
        <div class="rounded-2xl border border-[var(--nt-line)] bg-white/80 px-4 py-3">
            <p class="text-[11px] uppercase tracking-wide text-nt-ink/45">Véhicules en mission</p>
            <p class="mt-1 font-display text-2xl font-semibold" x-text="kpis.vehicles_in_transit ?? 0"></p>
        </div>
        <div class="rounded-2xl border border-[var(--nt-line)] bg-white/80 px-4 py-3">
            <p class="text-[11px] uppercase tracking-wide text-nt-ink/45">Alertes</p>
            <p class="mt-1 font-display text-2xl font-semibold text-rose-700" x-text="kpis.alerts_count ?? 0"></p>
        </div>
    </div>

    <div class="grid gap-4 xl:grid-cols-[240px_minmax(0,1fr)_320px]">
        {{-- Filters --}}
        <aside class="space-y-4 rounded-2xl border border-[var(--nt-line)] bg-white/80 p-4">
            <h2 class="font-display text-sm font-semibold">Filtres</h2>
            <div>
                <label class="nt-label">Type de nœud</label>
                <select class="nt-field" x-model="filterType" @change="applyFilters()">
                    <option value="all">Tous</option>
                    <option value="producer">Producteur</option>
                    <option value="distribution_center">Centre distribution</option>
                    <option value="cold_room">Chambre froide</option>
                    <option value="wholesaler">Grossiste</option>
                    <option value="retailer">Point de vente</option>
                    <option value="restaurant">Restaurant</option>
                </select>
            </div>
            <div>
                <label class="nt-label">Statut shipment</label>
                <select class="nt-field" x-model="filterStatus" @change="applyFilters()">
                    <option value="all">Tous</option>
                    <option value="in_transit">En transit</option>
                    <option value="delayed">Retardé</option>
                    <option value="delivered">Livré</option>
                    <option value="dispatched">Expédié</option>
                </select>
            </div>
            <div class="border-t border-[var(--nt-line)] pt-3">
                <p class="mb-2 text-xs font-medium uppercase tracking-wide text-nt-ink/45">Shipments</p>
                <ul class="max-h-72 space-y-1 overflow-y-auto text-sm">
                    <template x-for="s in filteredShipments" :key="s.id">
                        <li>
                            <button type="button"
                                    class="w-full rounded-xl px-2.5 py-2 text-left hover:bg-nt-mist"
                                    @click="selectShipment(s)">
                                <span class="font-medium" x-text="s.code"></span>
                                <span class="mt-0.5 block text-xs text-nt-ink/50" x-text="(s.origin || '—') + ' → ' + (s.destination || '—')"></span>
                            </button>
                        </li>
                    </template>
                </ul>
            </div>
        </aside>

        {{-- Map --}}
        <div class="overflow-hidden rounded-2xl border border-[var(--nt-line)] bg-[#e8eef2] shadow-sm">
            <div x-ref="map" class="h-[520px] w-full xl:h-[640px]"></div>
        </div>

        {{-- Side panel --}}
        <aside class="flex max-h-[640px] flex-col rounded-2xl border border-[var(--nt-line)] bg-white/90 p-4">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="font-display text-sm font-semibold">Détail</h2>
                <button type="button" class="text-xs text-nt-ink/50 hover:text-nt-ink" x-show="selected" @click="clearSelection()">Fermer</button>
            </div>

            <div x-show="!selected" class="flex flex-1 items-center justify-center text-center text-sm text-nt-ink/45">
                Sélectionnez un nœud, un véhicule ou un shipment sur la carte.
            </div>

            <div x-cloak x-show="selected?.kind === 'shipment'" class="space-y-3 overflow-y-auto text-sm">
                <p class="font-display text-lg font-semibold" x-text="selected?.data?.code"></p>
                <p><span class="text-nt-ink/45">Statut</span> — <span x-text="selected?.data?.status_label"></span></p>
                <p><span class="text-nt-ink/45">Origine</span> — <span x-text="selected?.data?.origin || '—'"></span></p>
                <p><span class="text-nt-ink/45">Destination</span> — <span x-text="selected?.data?.destination || '—'"></span></p>
                <p><span class="text-nt-ink/45">Véhicule</span> — <span x-text="selected?.data?.vehicle || '—'"></span></p>
                <p><span class="text-nt-ink/45">Chauffeur</span> — <span x-text="selected?.data?.driver || '—'"></span></p>
                <p><span class="text-nt-ink/45">Charge</span> — <span x-text="(selected?.data?.load_kg ?? '—') + ' kg'"></span></p>
                <p><span class="text-nt-ink/45">ETA</span> — <span x-text="selected?.data?.eta_at ? new Date(selected.data.eta_at).toLocaleString('fr-TN') : '—'"></span></p>
                <p><span class="text-nt-ink/45">Température</span> — <span x-text="selected?.data?.temperature_c != null ? selected.data.temperature_c + '°C' : '—'"></span></p>
                <p><span class="text-nt-ink/45">CO₂e estimé</span> — <span x-text="selected?.data?.estimated_co2e_kg != null ? selected.data.estimated_co2e_kg + ' kg' : '—'"></span></p>
                <template x-if="selected?.data?.id">
                    <a class="nt-btn mt-2 inline-flex !py-2 text-xs" :href="`/shipments/${selected.data.id}`">Ouvrir le shipment</a>
                </template>
            </div>

            <div x-cloak x-show="selected?.kind === 'vehicle'" class="space-y-3 text-sm">
                <p class="font-display text-lg font-semibold" x-text="'Véhicule ' + (selected?.data?.registration || '')"></p>
                <p><span class="text-nt-ink/45">Statut</span> — <span x-text="selected?.data?.status"></span></p>
                <p><span class="text-nt-ink/45">Chauffeur</span> — <span x-text="selected?.data?.driver || '—'"></span></p>
                <p><span class="text-nt-ink/45">Capacité</span> — <span x-text="(selected?.data?.capacity_kg ?? '—') + ' kg'"></span></p>
                <p><span class="text-nt-ink/45">Température</span> — <span x-text="selected?.data?.temperature_c != null ? selected.data.temperature_c + '°C' : '—'"></span></p>
                <p><span class="text-nt-ink/45">Vitesse</span> — <span x-text="selected?.data?.speed_kmh != null ? selected.data.speed_kmh + ' km/h' : '—'"></span></p>
            </div>

            <div x-cloak x-show="selected?.kind === 'node'" class="space-y-3 text-sm">
                <p class="font-display text-lg font-semibold" x-text="selected?.data?.name"></p>
                <p><span class="text-nt-ink/45">Type</span> — <span x-text="selected?.data?.type_label"></span></p>
                <p><span class="text-nt-ink/45">Code</span> — <span x-text="selected?.data?.code"></span></p>
                <p><span class="text-nt-ink/45">Organisation</span> — <span x-text="selected?.data?.organization || '—'"></span></p>
                <p><span class="text-nt-ink/45">Canal</span> — <span x-text="selected?.data?.channel || '—'"></span></p>
            </div>

            <div class="mt-auto border-t border-[var(--nt-line)] pt-3">
                <p class="mb-2 text-xs font-medium uppercase tracking-wide text-nt-ink/45">Alertes / retards</p>
                <ul class="max-h-40 space-y-2 overflow-y-auto text-sm">
                    <template x-for="s in (tower.shipments || []).filter(x => x.status === 'delayed')" :key="'alert-'+s.id">
                        <li class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2">
                            <button type="button" class="w-full text-left" @click="selectShipment(s)">
                                <span class="font-medium text-amber-900" x-text="s.code"></span>
                                <span class="mt-0.5 block text-xs text-amber-800/70" x-text="(s.origin || '') + ' → ' + (s.destination || '')"></span>
                            </button>
                        </li>
                    </template>
                    <li x-show="!(tower.shipments || []).some(x => x.status === 'delayed')" class="text-xs text-nt-ink/40">Aucune alerte retard.</li>
                </ul>
            </div>
        </aside>
    </div>

    {{-- Activity strip --}}
    <div class="mt-4 rounded-2xl border border-[var(--nt-line)] bg-white/80 p-4">
        <h2 class="font-display text-sm font-semibold">Activité opérationnelle</h2>
        <div class="mt-3 flex gap-3 overflow-x-auto pb-1">
            <template x-for="a in (tower.activity || [])" :key="a.id">
                <div class="min-w-[220px] rounded-xl border border-[var(--nt-line)] bg-nt-mist/40 px-3 py-2 text-sm">
                    <p class="font-medium" x-text="a.code"></p>
                    <p class="text-xs text-nt-ink/55" x-text="a.status_label"></p>
                    <p class="mt-1 text-xs text-nt-ink/45" x-text="(a.from || '—') + ' → ' + (a.to || '—')"></p>
                </div>
            </template>
        </div>
    </div>
</div>
@endsection
