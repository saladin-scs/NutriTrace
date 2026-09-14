@extends('layouts.front')

@section('title', 'Control Tower')

@section('wide')
@php
    $isOps = ($tab ?? 'ops') === 'ops';
    $isIntel = ($tab ?? 'ops') === 'intelligence';
@endphp

<div class="nt-container py-6">
    {{-- Header + tabs --}}
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="nt-kicker">Ops live · Tunisie</p>
            <h1 class="nt-page-title mt-1">Control Tower</h1>
            <p class="nt-page-sub">Distribution, cold chain et intelligence dans une seule vue.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('shipments.create') }}" class="nt-btn">Nouveau shipment</a>
            <a href="{{ route('alert-center.index') }}" class="nt-btn-secondary">Alert Center</a>
        </div>
    </div>

    <div class="mb-6 flex flex-wrap gap-1 rounded-2xl border border-[var(--nt-line)] bg-white/70 p-1.5">
        <a href="{{ route('control-tower.index', ['tab' => 'ops']) }}"
           class="rounded-xl px-4 py-2.5 text-sm font-medium transition {{ $isOps ? 'bg-emerald-900 text-white shadow-sm' : 'text-nt-ink/70 hover:bg-nt-mist' }}">
            Opérations
        </a>
        <a href="{{ route('control-tower.index', ['tab' => 'intelligence']) }}"
           class="rounded-xl px-4 py-2.5 text-sm font-medium transition {{ $isIntel ? 'bg-emerald-900 text-white shadow-sm' : 'text-nt-ink/70 hover:bg-nt-mist' }}">
            Intelligence
        </a>
    </div>

    @if ($isOps)
        <div x-data="ntControlTower(@js(['tower' => $tower]))">
            <div class="mb-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                <div class="rounded-2xl border border-[var(--nt-line)] bg-white/85 px-4 py-3">
                    <p class="text-[11px] uppercase tracking-wide text-nt-ink/45">Shipments actifs</p>
                    <p class="mt-1 font-display text-2xl font-semibold" x-text="kpis.active_shipments ?? 0"></p>
                </div>
                <div class="rounded-2xl border border-[var(--nt-line)] bg-white/85 px-4 py-3">
                    <p class="text-[11px] uppercase tracking-wide text-nt-ink/45">Livraisons du jour</p>
                    <p class="mt-1 font-display text-2xl font-semibold" x-text="kpis.deliveries_today ?? 0"></p>
                </div>
                <div class="rounded-2xl border border-[var(--nt-line)] bg-white/85 px-4 py-3">
                    <p class="text-[11px] uppercase tracking-wide text-nt-ink/45">Retards</p>
                    <p class="mt-1 font-display text-2xl font-semibold text-amber-700" x-text="kpis.delayed_shipments ?? 0"></p>
                </div>
                <div class="rounded-2xl border border-[var(--nt-line)] bg-white/85 px-4 py-3">
                    <p class="text-[11px] uppercase tracking-wide text-nt-ink/45">Chambres froides</p>
                    <p class="mt-1 font-display text-2xl font-semibold" x-text="kpis.cold_rooms_active ?? 0"></p>
                </div>
                <div class="rounded-2xl border border-[var(--nt-line)] bg-white/85 px-4 py-3">
                    <p class="text-[11px] uppercase tracking-wide text-nt-ink/45">Véhicules en mission</p>
                    <p class="mt-1 font-display text-2xl font-semibold" x-text="kpis.vehicles_in_transit ?? 0"></p>
                </div>
                <div class="rounded-2xl border border-[var(--nt-line)] bg-white/85 px-4 py-3">
                    <p class="text-[11px] uppercase tracking-wide text-nt-ink/45">Alertes</p>
                    <p class="mt-1 font-display text-2xl font-semibold text-rose-700" x-text="kpis.alerts_count ?? 0"></p>
                </div>
            </div>

            <div class="grid gap-4 xl:grid-cols-[220px_minmax(0,1fr)_300px]">
                <aside class="space-y-4 rounded-2xl border border-[var(--nt-line)] bg-white/85 p-4">
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

                <div class="overflow-hidden rounded-2xl border border-[var(--nt-line)] bg-[#e8eef2] shadow-sm">
                    <div x-ref="map" class="h-[480px] w-full xl:h-[600px]"></div>
                </div>

                <aside class="flex max-h-[600px] flex-col gap-4">
                    <div class="flex flex-1 flex-col rounded-2xl border border-[var(--nt-line)] bg-white/90 p-4">
                        <div class="mb-3 flex items-center justify-between">
                            <h2 class="font-display text-sm font-semibold">Détail</h2>
                            <button type="button" class="text-xs text-nt-ink/50 hover:text-nt-ink" x-show="selected" @click="clearSelection()">Fermer</button>
                        </div>

                        <div x-show="!selected" class="flex flex-1 items-center justify-center text-center text-sm text-nt-ink/45">
                            Sélectionnez un nœud, un véhicule ou un shipment.
                        </div>

                        <div x-cloak x-show="selected?.kind === 'shipment'" class="space-y-2 overflow-y-auto text-sm">
                            <p class="font-display text-lg font-semibold" x-text="selected?.data?.code"></p>
                            <p><span class="text-nt-ink/45">Statut</span> — <span x-text="selected?.data?.status_label"></span></p>
                            <p><span class="text-nt-ink/45">Origine</span> — <span x-text="selected?.data?.origin || '—'"></span></p>
                            <p><span class="text-nt-ink/45">Destination</span> — <span x-text="selected?.data?.destination || '—'"></span></p>
                            <p><span class="text-nt-ink/45">Véhicule</span> — <span x-text="selected?.data?.vehicle || '—'"></span></p>
                            <p><span class="text-nt-ink/45">ETA</span> — <span x-text="selected?.data?.eta_at ? new Date(selected.data.eta_at).toLocaleString('fr-TN') : '—'"></span></p>
                            <template x-if="selected?.data?.id">
                                <a class="nt-btn mt-2 inline-flex !py-2 text-xs" :href="`/shipments/${selected.data.id}`">Ouvrir</a>
                            </template>
                        </div>

                        <div x-cloak x-show="selected?.kind === 'vehicle'" class="space-y-2 text-sm">
                            <p class="font-display text-lg font-semibold" x-text="'Véhicule ' + (selected?.data?.registration || '')"></p>
                            <p><span class="text-nt-ink/45">Statut</span> — <span x-text="selected?.data?.status"></span></p>
                            <p><span class="text-nt-ink/45">Chauffeur</span> — <span x-text="selected?.data?.driver || '—'"></span></p>
                            <p><span class="text-nt-ink/45">Température</span> — <span x-text="selected?.data?.temperature_c != null ? selected.data.temperature_c + '°C' : '—'"></span></p>
                        </div>

                        <div x-cloak x-show="selected?.kind === 'node'" class="space-y-2 text-sm">
                            <p class="font-display text-lg font-semibold" x-text="selected?.data?.name"></p>
                            <p><span class="text-nt-ink/45">Type</span> — <span x-text="selected?.data?.type_label"></span></p>
                            <p><span class="text-nt-ink/45">Organisation</span> — <span x-text="selected?.data?.organization || '—'"></span></p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-[var(--nt-line)] bg-white/90 p-4">
                        <div class="mb-2 flex items-center justify-between">
                            <h2 class="font-display text-sm font-semibold">Alertes ouvertes</h2>
                            <a href="{{ route('alert-center.index') }}" class="text-[11px] text-emerald-800 underline">Tout voir</a>
                        </div>
                        <ul class="max-h-48 space-y-2 overflow-y-auto text-sm">
                            @forelse ($alertFeed as $anomaly)
                                <li class="rounded-xl border px-3 py-2
                                    {{ $anomaly->severity->value === 'critical' ? 'border-rose-200 bg-rose-50/80' : ($anomaly->severity->value === 'warning' ? 'border-amber-200 bg-amber-50/70' : 'border-[var(--nt-line)] bg-nt-mist/40') }}">
                                    <a href="{{ route('alert-center.show', $anomaly) }}" class="block">
                                        <span class="text-[10px] uppercase tracking-wide text-nt-ink/45">{{ $anomaly->severity->label() }}</span>
                                        <span class="mt-0.5 block font-medium leading-snug">{{ \Illuminate\Support\Str::limit($anomaly->title, 48) }}</span>
                                    </a>
                                </li>
                            @empty
                                <li class="text-xs text-nt-ink/40">Aucune alerte active.</li>
                            @endforelse
                        </ul>
                    </div>
                </aside>
            </div>

            <div class="mt-4 rounded-2xl border border-[var(--nt-line)] bg-white/85 p-4">
                <h2 class="font-display text-sm font-semibold">Activité opérationnelle</h2>
                <div class="mt-3 flex gap-3 overflow-x-auto pb-1">
                    <template x-for="a in (tower.activity || [])" :key="a.id">
                        <div class="min-w-[200px] rounded-xl border border-[var(--nt-line)] bg-nt-mist/40 px-3 py-2 text-sm">
                            <p class="font-medium" x-text="a.code"></p>
                            <p class="text-xs text-nt-ink/55" x-text="a.status_label"></p>
                            <p class="mt-1 text-xs text-nt-ink/45" x-text="(a.from || '—') + ' → ' + (a.to || '—')"></p>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    @else
        @include('control-tower.partials.intelligence', ['dashboard' => $analytics])
    @endif
</div>
@endsection
