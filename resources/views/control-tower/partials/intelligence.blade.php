@php
    $d = $dashboard;
    $h = $d['health'];
@endphp

{{-- Health Score --}}
<div class="grid gap-4 lg:grid-cols-[260px_minmax(0,1fr)]">
    <div class="rounded-3xl border border-[var(--nt-line)] bg-gradient-to-br from-emerald-900 to-emerald-700 p-6 text-white shadow-sm">
        <p class="text-xs uppercase tracking-[0.16em] text-white/60">Supply Chain Health</p>
        <p class="mt-3 font-display text-6xl font-semibold leading-none">{{ $h['score'] }}</p>
        <p class="mt-1 text-lg text-white/80">/ 100 — {{ $h['label'] }}</p>
        <p class="mt-4 text-xs text-white/55">{{ $h['methodology']['disclaimer'] }}</p>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($h['pillars'] as $key => $pillar)
            <div class="rounded-2xl border border-[var(--nt-line)] bg-white/85 px-4 py-3">
                <p class="text-[11px] uppercase tracking-wide text-nt-ink/45">{{ str_replace('_', ' ', $key) }}</p>
                <p class="mt-1 font-display text-2xl font-semibold">{{ $pillar['score'] }}</p>
                <p class="text-xs text-nt-ink/45">poids {{ $pillar['weight'] }}% · contrib. {{ $pillar['contribution'] }}</p>
                <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-nt-mist">
                    <div class="h-full rounded-full bg-emerald-700" style="width: {{ min(100, $pillar['score']) }}%"></div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
    @foreach ($d['cards'] as $card)
        <div class="rounded-2xl border border-[var(--nt-line)] bg-white/85 px-4 py-3">
            <p class="text-[11px] uppercase tracking-wide text-nt-ink/45">{{ $card['label'] }}</p>
            <p class="mt-1 font-display text-xl font-semibold">{{ $card['value'] }}</p>
        </div>
    @endforeach
</div>

<div class="mt-8 grid gap-4 lg:grid-cols-2">
    <section class="rounded-2xl border border-[var(--nt-line)] bg-white/85 p-5 text-sm">
        <h2 class="font-display text-lg font-semibold">Logistique</h2>
        <dl class="mt-3 grid grid-cols-2 gap-3">
            <div><dt class="text-xs text-nt-ink/40">Shipments actifs</dt><dd class="font-medium">{{ $d['logistics']['active_shipments'] }}</dd></div>
            <div><dt class="text-xs text-nt-ink/40">Livrés (période)</dt><dd class="font-medium">{{ $d['logistics']['completed_shipments'] }}</dd></div>
            <div><dt class="text-xs text-nt-ink/40">Retardés</dt><dd class="font-medium text-amber-700">{{ $d['logistics']['delayed_shipments'] }}</dd></div>
            <div><dt class="text-xs text-nt-ink/40">À l’heure</dt><dd class="font-medium">{{ $d['logistics']['on_time_delivery_rate'] }}%</dd></div>
            <div><dt class="text-xs text-nt-ink/40">Délai moyen</dt><dd class="font-medium">{{ $d['logistics']['average_delivery_label'] }}</dd></div>
            <div><dt class="text-xs text-nt-ink/40">Utilisation véhicules</dt><dd class="font-medium">{{ $d['logistics']['vehicle_utilization_rate'] }}%</dd></div>
        </dl>
    </section>

    <section class="rounded-2xl border border-[var(--nt-line)] bg-white/85 p-5 text-sm">
        <h2 class="font-display text-lg font-semibold">Cold Chain</h2>
        <dl class="mt-3 grid grid-cols-2 gap-3">
            <div><dt class="text-xs text-nt-ink/40">Chambres actives</dt><dd class="font-medium">{{ $d['cold_chain']['active_cold_rooms'] }}</dd></div>
            <div><dt class="text-xs text-nt-ink/40">Occupation moy.</dt><dd class="font-medium">{{ $d['cold_chain']['avg_occupancy_pct'] }}%</dd></div>
            <div><dt class="text-xs text-nt-ink/40">Conformité T°</dt><dd class="font-medium">{{ $d['cold_chain']['cold_chain_compliance_rate'] }}%</dd></div>
            <div><dt class="text-xs text-nt-ink/40">Breaches T°</dt><dd class="font-medium text-rose-700">{{ $d['cold_chain']['temperature_breaches'] }}</dd></div>
            <div><dt class="text-xs text-nt-ink/40">Stock ouvert</dt><dd class="font-medium">{{ number_format($d['cold_chain']['open_storage_kg'], 0, ',', ' ') }} kg</dd></div>
            <div><dt class="text-xs text-nt-ink/40">Durée moy.</dt><dd class="font-medium">{{ $d['cold_chain']['average_storage_days'] }} j</dd></div>
        </dl>
    </section>

    <section class="rounded-2xl border border-[var(--nt-line)] bg-white/85 p-5 text-sm">
        <h2 class="font-display text-lg font-semibold">Pertes & valorisation</h2>
        <dl class="mt-3 grid grid-cols-2 gap-3">
            <div><dt class="text-xs text-nt-ink/40">Pertes</dt><dd class="font-medium">{{ number_format($d['waste']['food_loss_kg'], 0, ',', ' ') }} kg</dd></div>
            <div><dt class="text-xs text-nt-ink/40">Taux de perte</dt><dd class="font-medium">{{ $d['waste']['loss_rate_pct'] }}%</dd></div>
            <div><dt class="text-xs text-nt-ink/40">Expiré</dt><dd class="font-medium">{{ number_format($d['waste']['expired_kg'], 0, ',', ' ') }} kg</dd></div>
            <div><dt class="text-xs text-nt-ink/40">Recovery</dt><dd class="font-medium">{{ $d['waste']['recovery_rate_pct'] }}%</dd></div>
        </dl>
    </section>

    <section class="rounded-2xl border border-[var(--nt-line)] bg-white/85 p-5 text-sm">
        <h2 class="font-display text-lg font-semibold">Environnement & traçabilité</h2>
        <dl class="mt-3 grid grid-cols-2 gap-3">
            <div><dt class="text-xs text-nt-ink/40">CO₂e estimé</dt><dd class="font-medium">{{ number_format($d['environmental']['co2e_kg'], 1, ',', ' ') }} kg</dd></div>
            <div><dt class="text-xs text-nt-ink/40">CO₂e / shipment</dt><dd class="font-medium">{{ $d['environmental']['co2e_per_shipment'] ?? '—' }}</dd></div>
            <div><dt class="text-xs text-nt-ink/40">Couverture trace</dt><dd class="font-medium">{{ $d['traceability']['traceability_coverage_pct'] }}%</dd></div>
            <div><dt class="text-xs text-nt-ink/40">Alertes ouvertes</dt><dd class="font-medium text-rose-700">{{ $d['alerts']['open'] }} ({{ $d['alerts']['critical'] }} crit.)</dd></div>
        </dl>
        <p class="mt-3 text-xs text-nt-ink/40">{{ $d['environmental']['disclaimer'] }}</p>
    </section>
</div>

@if ($alertFeed->isNotEmpty())
    <div class="mt-8 rounded-2xl border border-[var(--nt-line)] bg-white/85 p-5">
        <div class="flex items-center justify-between gap-2">
            <h2 class="font-display text-lg font-semibold">Alertes à traiter</h2>
            <a href="{{ route('alert-center.index') }}" class="nt-link text-sm">Alert Center</a>
        </div>
        <ul class="mt-4 divide-y divide-[var(--nt-line)] text-sm">
            @foreach ($alertFeed as $anomaly)
                <li class="flex flex-wrap items-center justify-between gap-2 py-3">
                    <div>
                        <a href="{{ route('alert-center.show', $anomaly) }}" class="font-medium text-emerald-900 hover:underline">{{ $anomaly->title }}</a>
                        <p class="text-xs text-nt-ink/50">{{ $anomaly->severity->label() }} · {{ $anomaly->detected_at?->format('d/m H:i') }}</p>
                    </div>
                    <span class="rounded-full bg-nt-mist px-2.5 py-1 text-[11px]">{{ $anomaly->code }}</span>
                </li>
            @endforeach
        </ul>
    </div>
@endif
