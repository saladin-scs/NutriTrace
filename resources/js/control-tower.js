import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

const NODE_COLORS = {
    producer: '#166534',
    transformer: '#0f766e',
    warehouse: '#1e3a5f',
    distribution_center: '#1d4ed8',
    cold_room: '#0369a1',
    distributor: '#7c3aed',
    wholesaler: '#b45309',
    retailer: '#be123c',
    restaurant: '#c2410c',
    hotel: '#a16207',
    collector: '#475569',
};

const STATUS_COLORS = {
    draft: '#64748b',
    dispatched: '#2563eb',
    in_transit: '#059669',
    arrived: '#0d9488',
    delivered: '#166534',
    delayed: '#d97706',
    cancelled: '#dc2626',
    planned: '#64748b',
    active: '#059669',
    critical: '#dc2626',
    completed: '#166534',
    normal: '#059669',
};

export function createControlTower(config = {}) {
    return {
        tower: config.tower || {},
        feedUrl: config.feedUrl || null,
        selected: null,
        filterType: 'all',
        filterStatus: 'all',
        hideDelivered: true,
        map: null,
        refreshTimer: null,
        refreshing: false,
        layerGroups: {
            nodes: null,
            vehicles: null,
            routes: null,
            cargo: null,
        },

        init() {
            this.$nextTick(() => this.initMap());
            this.startRefresh();

            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    this.stopRefresh();
                    return;
                }
                this.refreshTower();
                this.startRefresh();
            });
        },

        destroy() {
            this.stopRefresh();
        },

        get kpis() {
            return this.tower.kpis || {};
        },

        get filteredShipments() {
            return (this.tower.shipments || []).filter((s) => {
                if (this.hideDelivered && s.status === 'delivered') {
                    return false;
                }
                if (this.filterStatus !== 'all' && s.status !== this.filterStatus) {
                    return false;
                }
                return true;
            });
        },

        get filteredNodes() {
            return (this.tower.nodes || []).filter((n) => {
                if (this.filterType !== 'all' && n.type !== this.filterType) {
                    return false;
                }
                return true;
            });
        },

        startRefresh() {
            this.stopRefresh();
            if (!this.feedUrl) {
                return;
            }
            this.refreshTimer = setInterval(() => {
                if (!document.hidden) {
                    this.refreshTower();
                }
            }, config.refreshMs || 20000);
        },

        stopRefresh() {
            if (this.refreshTimer) {
                clearInterval(this.refreshTimer);
                this.refreshTimer = null;
            }
        },

        async refreshTower() {
            if (!this.feedUrl || this.refreshing) {
                return;
            }
            this.refreshing = true;
            try {
                const res = await fetch(this.feedUrl, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                if (!res.ok) {
                    return;
                }
                const json = await res.json();
                if (json?.data) {
                    this.tower = json.data;
                    this.renderMapLayers();
                    if (this.selected?.kind === 'shipment') {
                        const updated = (this.tower.shipments || []).find((s) => s.id === this.selected.data.id);
                        if (updated) {
                            this.selected = { kind: 'shipment', data: updated };
                        }
                    }
                }
            } catch (e) {
                // keep last payload
            } finally {
                this.refreshing = false;
            }
        },

        initMap() {
            const el = this.$refs.map;
            if (!el || this.map) {
                return;
            }

            this.map = L.map(el, {
                zoomControl: true,
                scrollWheelZoom: true,
            }).setView([34.0, 9.5], 7);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap',
                maxZoom: 18,
            }).addTo(this.map);

            this.layerGroups.nodes = L.layerGroup().addTo(this.map);
            this.layerGroups.routes = L.layerGroup().addTo(this.map);
            this.layerGroups.cargo = L.layerGroup().addTo(this.map);
            this.layerGroups.vehicles = L.layerGroup().addTo(this.map);

            this.renderMapLayers();
            setTimeout(() => this.map.invalidateSize(), 80);
        },

        renderMapLayers() {
            if (!this.map) {
                return;
            }

            this.layerGroups.nodes.clearLayers();
            this.layerGroups.vehicles.clearLayers();
            this.layerGroups.routes.clearLayers();
            this.layerGroups.cargo.clearLayers();

            this.filteredNodes.forEach((node) => {
                const color = NODE_COLORS[node.type] || '#334155';
                const marker = L.circleMarker([node.lat, node.lng], {
                    radius: 8,
                    color: '#fff',
                    weight: 2,
                    fillColor: color,
                    fillOpacity: 0.95,
                });
                marker.bindTooltip(`${node.name}<br><span style="opacity:.7">${node.type_label}</span>`, {
                    direction: 'top',
                });
                marker.on('click', () => {
                    this.selected = { kind: 'node', data: node };
                });
                marker.addTo(this.layerGroups.nodes);
            });

            this.filteredShipments.forEach((shipment) => {
                if (shipment.path && shipment.path.length >= 2) {
                    const latlngs = shipment.path.map((p) => [p.lat, p.lng]);
                    const color = STATUS_COLORS[shipment.status] || STATUS_COLORS[shipment.route_status] || '#059669';
                    const line = L.polyline(latlngs, {
                        color,
                        weight: shipment.status === 'delayed' ? 5 : 3,
                        opacity: shipment.status === 'delivered' ? 0.35 : 0.85,
                        dashArray: shipment.status === 'delayed' ? '8 6' : null,
                    });
                    line.bindTooltip(
                        `${shipment.code}<br>${shipment.product_summary || ''}<br>${shipment.origin || ''} → ${shipment.destination || ''}`,
                        { sticky: true }
                    );
                    line.on('click', () => {
                        this.selected = { kind: 'shipment', data: shipment };
                    });
                    line.addTo(this.layerGroups.routes);
                }

                if (shipment.is_active && shipment.current_lat != null && shipment.current_lng != null) {
                    const cargo = L.circleMarker([shipment.current_lat, shipment.current_lng], {
                        radius: 7,
                        color: '#fff',
                        weight: 2,
                        fillColor: STATUS_COLORS[shipment.status] || '#059669',
                        fillOpacity: 1,
                    });
                    cargo.bindTooltip(
                        `<strong>${shipment.code}</strong><br>${shipment.product_summary || 'Cargaison'}`,
                        { direction: 'top' }
                    );
                    cargo.on('click', () => {
                        this.selected = { kind: 'shipment', data: shipment };
                    });
                    cargo.addTo(this.layerGroups.cargo);
                }
            });

            (this.tower.vehicles || []).forEach((vehicle) => {
                if (vehicle.lat == null || vehicle.lng == null) {
                    return;
                }
                const marker = L.marker([vehicle.lat, vehicle.lng]);
                marker.bindTooltip(`Véhicule ${vehicle.registration}`);
                marker.on('click', () => {
                    this.selected = { kind: 'vehicle', data: vehicle };
                });
                marker.addTo(this.layerGroups.vehicles);
            });
        },

        applyFilters() {
            this.renderMapLayers();
        },

        selectShipment(shipment) {
            this.selected = { kind: 'shipment', data: shipment };
            if (shipment.path?.length && this.map) {
                const bounds = L.latLngBounds(shipment.path.map((p) => [p.lat, p.lng]));
                this.map.fitBounds(bounds.pad(0.25));
            } else if (shipment.current_lat != null && this.map) {
                this.map.setView([shipment.current_lat, shipment.current_lng], 9);
            }
        },

        selectVehicle(vehicle) {
            this.selected = { kind: 'vehicle', data: vehicle };
            if (vehicle.lat != null && this.map) {
                this.map.setView([vehicle.lat, vehicle.lng], 9);
            }
        },

        clearSelection() {
            this.selected = null;
        },

        statusBadge(status) {
            return STATUS_COLORS[status] || '#64748b';
        },
    };
}
