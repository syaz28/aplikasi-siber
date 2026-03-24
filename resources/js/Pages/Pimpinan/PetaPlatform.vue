<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import PimpinanLayout from '@/Layouts/PimpinanLayout.vue';

// ── ECharts tree-shakable imports ──────────────────────────────────
import VChart from 'vue-echarts';
import { use, registerMap } from 'echarts/core';
import { CanvasRenderer } from 'echarts/renderers';
import {
    MapChart,
    BarChart,
    EffectScatterChart,
    ScatterChart,
    HeatmapChart,
} from 'echarts/charts';
import {
    TitleComponent,
    TooltipComponent,
    LegendComponent,
    GridComponent,
    VisualMapComponent,
    GeoComponent,
} from 'echarts/components';

use([
    CanvasRenderer,
    MapChart,
    BarChart,
    EffectScatterChart,
    ScatterChart,
    HeatmapChart,
    TitleComponent,
    TooltipComponent,
    LegendComponent,
    GridComponent,
    VisualMapComponent,
    GeoComponent,
]);

// ── Props ──────────────────────────────────────────────────────────
const props = defineProps({
    keyStats: Object,
    regionalMap: Array,
    platformCategory: Array,
    platformKategoriTreemap: Array,
    regionalLeaderboard: Array,
    platformLeaderboard: Array,
    filterOptions: Object,
    appliedFilters: Object,
});

// ══════════════════════════════════════════════════════════════════════
// MAP STATE
// ══════════════════════════════════════════════════════════════════════
const mapReady = ref(false);
const mapChartRef = ref(null);

// ══════════════════════════════════════════════════════════════════════
// FILTER DRAWER STATE
// ══════════════════════════════════════════════════════════════════════
const isFilterDrawerOpen = ref(false);

// ══════════════════════════════════════════════════════════════════════
// CROSS-FILTER INTERACTIVITY
// ══════════════════════════════════════════════════════════════════════
const activeFilters = reactive({
    kategori_id: props.appliedFilters?.kategori_id ?? null,
    bulan: props.appliedFilters?.bulan ?? null,
    gender: props.appliedFilters?.gender ?? null,
    pendidikan: props.appliedFilters?.pendidikan ?? null,
    usia_group: props.appliedFilters?.usia_group ?? null,
    tahun: props.appliedFilters?.tahun ?? null,
    wilayah_kode: props.appliedFilters?.wilayah_kode ?? null,
    platform_name: props.appliedFilters?.platform_name ?? null,
});

watch(() => props.appliedFilters, (val) => {
    if (!val) return;
    Object.keys(activeFilters).forEach(k => {
        activeFilters[k] = val[k] ?? null;
    });
}, { deep: true });

function toggleFilter(key, value) {
    activeFilters[key] = activeFilters[key] === value ? null : value;
    navigateWithFilters();
}

function clearAllFilters() {
    Object.keys(activeFilters).forEach(k => activeFilters[k] = null);
    navigateWithFilters();
}

function navigateWithFilters() {
    const params = {};
    Object.entries(activeFilters).forEach(([k, v]) => {
        if (v !== null && v !== undefined) params[k] = v;
    });
    router.get('/pimpinan/peta-platform', params, {
        preserveState: true,
        preserveScroll: true,
    });
}

const hasAnyFilter = computed(() =>
    Object.values(activeFilters).some(v => v !== null && v !== undefined)
);

const activeFilterCount = computed(() =>
    Object.values(activeFilters).filter(v => v !== null && v !== undefined).length
);

// ── Helpers ────────────────────────────────────────────────────────
const formatRupiah = (v) => {
    if (!v && v !== 0) return 'Rp 0';
    return 'Rp ' + Number(v).toLocaleString('id-ID', { maximumFractionDigits: 0 });
};

const formatCompact = (v) => {
    if (!v && v !== 0) return '0';
    const n = Number(v);
    if (n >= 1_000_000_000_000) return (n / 1_000_000_000_000).toFixed(1).replace(/\.0$/, '') + 'T';
    if (n >= 1_000_000_000) return (n / 1_000_000_000).toFixed(1).replace(/\.0$/, '') + 'M';
    if (n >= 1_000_000) return (n / 1_000_000).toFixed(1).replace(/\.0$/, '') + 'Jt';
    if (n >= 1_000) return (n / 1_000).toFixed(1).replace(/\.0$/, '') + 'rb';
    return n.toLocaleString('id-ID');
};

const formatNumber = (v) => {
    if (!v && v !== 0) return '0';
    return Number(v).toLocaleString('id-ID');
};

// ── Color constants ────────────────────────────────────────────────
const CYAN     = '#06B6D4';
const ROSE     = '#F43F5E';
const AMBER    = '#F59E0B';

const DARK_TOOLTIP = {
    backgroundColor: 'rgba(2, 6, 23, 0.96)',
    borderColor: 'rgba(6, 182, 212, 0.4)',
    borderWidth: 1,
    textStyle: { color: '#e2e8f0', fontSize: 12, fontFamily: 'ui-monospace, monospace' },
    padding: [10, 14],
    extraCssText: 'box-shadow: 0 0 20px rgba(6,182,212,0.15);',
};

// ══════════════════════════════════════════════════════════════════════
// GeoJSON Registration — cached globally (survives Inertia navigation)
// ══════════════════════════════════════════════════════════════════════

// Module-level cache: persists across component re-mounts / Inertia visits
let _geoCache = null;   // { centroids, ready: true }
let _geoPromise = null; // prevents duplicate fetches

// Centroid lookup: GeoJSON WADMKK name → [lng, lat]
const geoCentroids = ref({});

/**
 * DB wilayah names (e.g. "Kabupaten Banjarnegara") → GeoJSON WADMKK (e.g. "Banjarnegara")
 * Kota names match exactly. Kabupaten names need prefix stripping.
 */
function dbNameToGeoName(dbName) {
    return dbName.replace(/^Kabupaten\s+/, '');
}

/**
 * GeoJSON WADMKK (e.g. "Banjarnegara") → DB wilayah kode via reverse lookup
 */
function geoNameToKode(geoName) {
    const match = (props.regionalMap || []).find(d => dbNameToGeoName(d.nama) === geoName);
    return match?.kode ?? null;
}

/**
 * Compute visual centroid of a GeoJSON geometry using the
 * signed-area weighted method (accurate for irregular polygons).
 * Uses the largest polygon ring for MultiPolygon.
 */
function computeCentroid(geometry) {
    let ring = [];
    if (geometry.type === 'Polygon') {
        ring = geometry.coordinates[0];
    } else if (geometry.type === 'MultiPolygon') {
        let maxLen = 0;
        geometry.coordinates.forEach(poly => {
            if (poly[0].length > maxLen) {
                maxLen = poly[0].length;
                ring = poly[0];
            }
        });
    }
    if (ring.length < 3) return null;

    let area = 0, cx = 0, cy = 0;
    for (let i = 0, len = ring.length; i < len; i++) {
        const [x0, y0] = ring[i];
        const [x1, y1] = ring[(i + 1) % len];
        const cross = x0 * y1 - x1 * y0;
        area += cross;
        cx += (x0 + x1) * cross;
        cy += (y0 + y1) * cross;
    }
    if (Math.abs(area) < 1e-10) {
        // Degenerate polygon — fall back to simple average
        let sx = 0, sy = 0;
        for (const [x, y] of ring) { sx += x; sy += y; }
        return [sx / ring.length, sy / ring.length];
    }
    area *= 0.5;
    cx /= (6 * area);
    cy /= (6 * area);
    return [cx, cy];
}

/**
 * Fetch & register GeoJSON only once. Returns cached result on subsequent calls.
 */
function loadGeoJsonOnce() {
    if (_geoCache) return Promise.resolve(_geoCache);
    if (_geoPromise) return _geoPromise;

    _geoPromise = fetch('/geojson/Kabupaten.json').then(r => {
        if (!r.ok) throw new Error('Kabupaten.json fetch failed: ' + r.status);
        return r.json();
    }).then(kabRes => {
        // Filter to only Jawa Tengah features
        const jatengGeo = {
            type: 'FeatureCollection',
            features: kabRes.features.filter(f => f.properties.WADMPR === 'Jawa Tengah'),
        };

        // Build centroid lookup from actual GeoJSON polygons
        const centroids = {};
        for (const feature of jatengGeo.features) {
            const name = feature.properties.WADMKK;
            const kode = feature.properties.KDPKAB;
            if (name && feature.geometry) {
                const c = computeCentroid(feature.geometry);
                if (c) {
                    centroids[name] = c;
                    if (kode) centroids[kode] = c;
                }
            }
        }

        registerMap('jateng-kab', jatengGeo);

        _geoCache = { centroids, ready: true };
        return _geoCache;
    }).catch(err => {
        console.error('[PetaPlatform] GeoJSON load failed:', err);
        _geoPromise = null;
        throw err;
    });

    return _geoPromise;
}

onMounted(async () => {
    if (_geoCache) {
        geoCentroids.value = _geoCache.centroids;
        mapReady.value = true;
        return;
    }

    try {
        const cache = await loadGeoJsonOnce();
        geoCentroids.value = cache.centroids;
        mapReady.value = true;
    } catch {
        // error already logged
    }
});

// ══════════════════════════════════════════════════════════════════════
// MAP OPTION — Jawa Tengah Kabupaten/Kota
// ══════════════════════════════════════════════════════════════════════
const mapOption = computed(() => {
    if (!mapReady.value) return {};
    return buildJatengOption();
});

// ── Jawa Tengah Kabupaten View ─────────────────────────────────────
function buildJatengOption() {
    const rawData = props.regionalMap || [];
    const maxReports = Math.max(...rawData.map(d => d.total_reports), 1);
    const maxLoss    = Math.max(...rawData.map(d => d.total_losses), 1);

    // Choropleth data
    const choroplethData = rawData.map(d => ({
        name: dbNameToGeoName(d.nama),
        value: d.total_reports,
        kode: d.kode,
        total_losses: d.total_losses,
        itemStyle: d.active ? {
            areaColor: '#0e7490',
            borderColor: '#22d3ee',
            borderWidth: 2.5,
            shadowColor: 'rgba(6,182,212,0.6)',
            shadowBlur: 15,
        } : undefined,
    }));

    // effectScatter data: [lng, lat, reports, losses]
    // Use GeoJSON polygon centroids for precise placement, fallback to controller coords
    const scatterData = rawData
        .map(d => {
            const geoName = dbNameToGeoName(d.nama);
            const centroid = geoCentroids.value[d.kode] || geoCentroids.value[geoName];
            const lng = centroid ? centroid[0] : d.lng;
            const lat = centroid ? centroid[1] : d.lat;
            if (!lng || !lat) return null;
            return {
                name: geoName,
                value: [lng, lat, d.total_reports, d.total_losses],
                kode: d.kode,
                fullName: d.nama,
            };
        })
        .filter(Boolean);

    return {
        tooltip: {
            ...DARK_TOOLTIP,
            trigger: 'item',
            formatter: (p) => {
                if (p.seriesType === 'effectScatter') {
                    const [, , reports, losses] = p.value;
                    const isActive = activeFilters.wilayah_kode === p.data.kode;
                    return '<div style="font-weight:700;color:#22d3ee;margin-bottom:6px;font-size:13px;letter-spacing:0.5px">' + p.data.fullName + '</div>'
                        + '<div style="display:flex;justify-content:space-between;gap:24px;margin:3px 0"><span style="color:#94a3b8">LAPORAN</span><b style="color:#22d3ee">' + formatNumber(reports) + '</b></div>'
                        + '<div style="display:flex;justify-content:space-between;gap:24px;margin:3px 0"><span style="color:#94a3b8">KERUGIAN</span><b style="color:#f43f5e">' + formatRupiah(losses) + '</b></div>'
                        + (isActive ? '<div style="margin-top:6px;color:#22d3ee;font-size:10px;letter-spacing:1px">● FILTER AKTIF</div>' : '<div style="margin-top:8px;color:#475569;font-size:10px;letter-spacing:1px">KLIK UNTUK FILTER</div>');
                }
                if (p.value != null) {
                    return '<div style="font-weight:700;color:#22d3ee;margin-bottom:4px">' + p.name + '</div>'
                        + '<div>Laporan: <b>' + formatNumber(p.value) + '</b></div>';
                }
                return '<div style="color:#64748b">' + p.name + '</div>';
            },
        },
        geo: {
            map: 'jateng-kab',
            nameProperty: 'WADMKK',
            roam: false,
            zoom: 1.3,
            layoutCenter: ['50%', '45%'],
            layoutSize: '100%',
            label: { show: false },
            emphasis: {
                label: {
                    show: true,
                    color: '#e2e8f0',
                    fontSize: 11,
                    fontWeight: 600,
                    fontFamily: 'ui-monospace, monospace',
                    textShadowColor: '#000',
                    textShadowBlur: 4,
                },
                itemStyle: {
                    areaColor: '#164e63',
                    borderColor: '#22d3ee',
                    borderWidth: 1.5,
                },
            },
            itemStyle: {
                areaColor: '#0f172a',
                borderColor: 'rgba(6,182,212,0.25)',
                borderWidth: 0.8,
            },
            regions: choroplethData.map(d => ({
                name: d.name,
                value: d.value,
                itemStyle: d.itemStyle || {
                    areaColor: getReportsColor(d.value, maxReports),
                },
            })),
        },
        visualMap: {
            show: true,
            type: 'continuous',
            min: 0,
            max: maxLoss,
            text: ['KRITIS', 'AMAN'],
            textStyle: { color: '#64748b', fontSize: 10, fontFamily: 'ui-monospace, monospace' },
            orient: 'vertical',
            right: 16,
            bottom: 40,
            itemWidth: 12,
            itemHeight: 120,
            dimension: 3,
            seriesIndex: 0,
            inRange: {
                color: ['#06b6d4', '#fbbf24', '#f43f5e'],
            },
            formatter: (v) => formatCompact(v),
        },
        series: [
            {
                name: 'Polres Hotspot',
                type: 'effectScatter',
                coordinateSystem: 'geo',
                data: scatterData,
                symbolSize: (val) => {
                    const digits = String(val[2]).length;
                    if (digits <= 1) return 16;
                    if (digits <= 2) return 20;
                    if (digits <= 3) return 24;
                    return 30;
                },
                showEffectOn: 'render',
                rippleEffect: {
                    brushType: 'stroke',
                    scale: 3,
                    period: 4,
                    number: 2,
                },
                itemStyle: {
                    shadowBlur: 12,
                    shadowColor: 'rgba(6, 182, 212, 0.4)',
                },
                emphasis: {
                    scale: 1.4,
                    itemStyle: {
                        shadowBlur: 30,
                        shadowColor: 'rgba(6, 182, 212, 0.8)',
                        borderColor: '#22d3ee',
                        borderWidth: 2,
                    },
                },
                label: {
                    show: true,
                    formatter: (p) => p.value[2],
                    position: 'inside',
                    color: '#ffffff',
                    fontSize: 8,
                    fontWeight: 700,
                    fontFamily: 'ui-monospace, monospace',
                    textShadowColor: 'rgba(0,0,0,0.9)',
                    textShadowBlur: 4,
                },
                zlevel: 2,
            },
        ],
    };
}

// Color interpolation for choropleth fill
function getReportsColor(value, max) {
    if (!value || !max) return '#0f172a';
    const ratio = Math.min(value / max, 1);
    if (ratio < 0.2) return '#0c1425';
    if (ratio < 0.4) return '#0c2d3f';
    if (ratio < 0.6) return '#134e5e';
    if (ratio < 0.8) return '#155e75';
    return '#0e7490';
}

// ── Map Click Handler ──────────────────────────────────────────────
function onMapClick(params) {
    if (params.seriesType === 'effectScatter' && params.data?.kode) {
        toggleFilter('wilayah_kode', params.data.kode);
        return;
    }

    if (params.seriesType === 'map' || params.componentType === 'geo') {
        const kode = geoNameToKode(params.name);
        if (kode) {
            toggleFilter('wilayah_kode', kode);
        }
    }
}

// ══════════════════════════════════════════════════════════════════════
// BOTTOM CHARTS
// ══════════════════════════════════════════════════════════════════════

// ── Platform Horizontal Bar ────────────────────────────────────────
const platformBarOption = computed(() => {
    const raw = props.platformLeaderboard || [];
    if (!raw.length) return {};

    const sorted = [...raw].sort((a, b) => a.total - b.total);

    return {
        tooltip: {
            ...DARK_TOOLTIP,
            trigger: 'axis',
            axisPointer: { type: 'shadow' },
            formatter: (params) => {
                const p = params[0];
                return '<div style="font-weight:700;color:#f59e0b;margin-bottom:4px;letter-spacing:0.5px">' + p.name + '</div>'
                    + '<div>Laporan: <b style="color:#f59e0b">' + formatNumber(p.value) + '</b></div>';
            },
        },
        grid: { top: 8, right: 60, bottom: 8, left: 8, containLabel: true },
        xAxis: {
            type: 'value',
            axisLabel: { color: '#475569', fontSize: 10, fontFamily: 'ui-monospace, monospace' },
            splitLine: { lineStyle: { color: '#1e293b', type: 'dashed' } },
            axisLine: { show: false },
        },
        yAxis: {
            type: 'category',
            data: sorted.map(p => p.platform),
            axisLabel: {
                color: '#94a3b8', fontSize: 11, width: 110, overflow: 'truncate',
                fontFamily: 'ui-monospace, monospace',
            },
            axisTick: { show: false },
            axisLine: { show: false },
        },
        series: [{
            type: 'bar',
            data: sorted.map((p) => {
                const isActive = p.active;
                const hasFilter = activeFilters.platform_name !== null;
                return {
                    value: p.total,
                    itemStyle: {
                        color: {
                            type: 'linear', x: 0, y: 0, x2: 1, y2: 0,
                            colorStops: isActive
                                ? [{ offset: 0, color: '#06b6d4' }, { offset: 1, color: '#22d3ee' }]
                                : [{ offset: 0, color: '#92400e' }, { offset: 1, color: '#f59e0b' }],
                        },
                        borderRadius: [0, 3, 3, 0],
                        opacity: hasFilter && !isActive ? 0.25 : 1,
                    },
                };
            }),
            barMaxWidth: 18,
            label: {
                show: true,
                position: 'right',
                color: '#cbd5e1',
                fontSize: 10,
                fontWeight: 600,
                fontFamily: 'ui-monospace, monospace',
                formatter: (p) => formatNumber(p.value),
            },
        }],
    };
});

function onPlatformBarClick(params) {
    const sorted = [...(props.platformLeaderboard || [])].sort((a, b) => a.total - b.total);
    const item = sorted[params.dataIndex];
    if (item?.platform) toggleFilter('platform_name', item.platform);
}

// ── Regional Leaderboard (Top Wilayah by Loss) ─────────────────────
const regionalBarOption = computed(() => {
    const raw = props.regionalLeaderboard || [];
    if (!raw.length) return {};

    const sorted = [...raw].sort((a, b) => a.total_losses - b.total_losses);

    return {
        tooltip: {
            ...DARK_TOOLTIP,
            trigger: 'axis',
            axisPointer: { type: 'shadow' },
            formatter: (params) => {
                const p = params[0];
                const item = sorted[p.dataIndex];
                return '<div style="font-weight:700;color:#f43f5e;margin-bottom:4px;letter-spacing:0.5px">' + item.nama + '</div>'
                    + '<div>Kerugian: <b style="color:#f43f5e">' + formatRupiah(item.total_losses) + '</b></div>'
                    + '<div>Laporan: <b style="color:#94a3b8">' + formatNumber(item.total_reports) + '</b></div>';
            },
        },
        grid: { top: 8, right: 90, bottom: 8, left: 8, containLabel: true },
        xAxis: {
            type: 'value',
            axisLabel: {
                color: '#475569', fontSize: 10, fontFamily: 'ui-monospace, monospace',
                formatter: (v) => formatCompact(v),
            },
            splitLine: { lineStyle: { color: '#1e293b', type: 'dashed' } },
            axisLine: { show: false },
        },
        yAxis: {
            type: 'category',
            data: sorted.map(r => dbNameToGeoName(r.nama)),
            axisLabel: {
                color: '#94a3b8', fontSize: 11, width: 110, overflow: 'truncate',
                fontFamily: 'ui-monospace, monospace',
            },
            axisTick: { show: false },
            axisLine: { show: false },
        },
        series: [{
            type: 'bar',
            data: sorted.map((r) => {
                const isActive = activeFilters.wilayah_kode === r.kode;
                const hasFilter = activeFilters.wilayah_kode !== null;
                return {
                    value: r.total_losses,
                    _kode: r.kode,
                    itemStyle: {
                        color: {
                            type: 'linear', x: 0, y: 0, x2: 1, y2: 0,
                            colorStops: isActive
                                ? [{ offset: 0, color: '#06b6d4' }, { offset: 1, color: '#22d3ee' }]
                                : [{ offset: 0, color: '#7f1d1d' }, { offset: 1, color: '#ef4444' }],
                        },
                        borderRadius: [0, 3, 3, 0],
                        opacity: hasFilter && !isActive ? 0.25 : 1,
                    },
                };
            }),
            barMaxWidth: 18,
            label: {
                show: true,
                position: 'right',
                color: '#cbd5e1',
                fontSize: 10,
                fontWeight: 600,
                fontFamily: 'ui-monospace, monospace',
                formatter: (p) => formatCompact(p.value),
            },
        }],
    };
});

function onRegionalBarClick(params) {
    const sorted = [...(props.regionalLeaderboard || [])].sort((a, b) => a.total_losses - b.total_losses);
    const item = sorted[params.dataIndex];
    if (item?.kode) toggleFilter('wilayah_kode', item.kode);
}

// ══════════════════════════════════════════════════════════════════════
// HEATMAP — Cyber Threat Matrix (Platform × Kategori)
// ══════════════════════════════════════════════════════════════════════

const heatmapData = computed(() => {
    const raw = props.platformKategoriTreemap || [];
    if (!raw.length) return { platforms: [], categories: [], data: [], max: 0 };

    const platformSet = new Set();
    const categorySet = new Set();
    const cellMap = new Map(); // 'platform|category' → { value, kategori_id }

    for (const platform of raw) {
        platformSet.add(platform.name);
        for (const child of (platform.children || [])) {
            categorySet.add(child.name);
            const key = platform.name + '|' + child.name;
            cellMap.set(key, { value: child.value, kategori_id: child.kategori_id });
        }
    }

    const platforms = [...platformSet];
    const categories = [...categorySet];
    let max = 0;

    const data = [];
    for (const [key, cell] of cellMap) {
        const [pName, cName] = key.split('|');
        const xIdx = platforms.indexOf(pName);
        const yIdx = categories.indexOf(cName);
        if (xIdx >= 0 && yIdx >= 0) {
            data.push([xIdx, yIdx, cell.value, cell.kategori_id, pName]);
            if (cell.value > max) max = cell.value;
        }
    }

    return { platforms, categories, data, max };
});

const heatmapOption = computed(() => {
    const { platforms, categories, data, max } = heatmapData.value;
    if (!platforms.length || !categories.length) return {};

    return {
        tooltip: {
            ...DARK_TOOLTIP,
            formatter: (params) => {
                const d = params.data;
                if (!d) return '';
                const platform = platforms[d[0]] || '';
                const category = categories[d[1]] || '';
                const value = d[2];
                return '<div style="font-weight:700;color:#06b6d4;margin-bottom:4px;letter-spacing:0.5px">'
                    + platform + '</div>'
                    + '<div style="color:#e2e8f0">' + category + '</div>'
                    + '<div style="margin-top:4px">Kasus: <b style="color:#f59e0b">' + formatNumber(value) + '</b></div>';
            },
        },
        grid: { top: 30, right: 20, bottom: 60, left: 140 },
        xAxis: {
            type: 'category',
            data: platforms,
            splitArea: { show: false },
            axisLabel: {
                color: '#94a3b8',
                fontSize: 10,
                fontFamily: 'ui-monospace, monospace',
                rotate: 45,
                interval: 0,
            },
            axisTick: { show: false },
            axisLine: { lineStyle: { color: '#1e293b' } },
        },
        yAxis: {
            type: 'category',
            data: categories,
            splitArea: { show: false },
            axisLabel: {
                color: '#cbd5e1',
                fontSize: 11,
                fontFamily: 'ui-monospace, monospace',
                width: 130,
                overflow: 'truncate',
            },
            axisTick: { show: false },
            axisLine: { lineStyle: { color: '#1e293b' } },
        },
        visualMap: {
            show: true,
            type: 'continuous',
            min: 0,
            max: max || 1,
            calculable: true,
            orient: 'horizontal',
            right: 0,
            top: -10,
            itemWidth: 14,
            itemHeight: 120,
            text: ['Tinggi', 'Rendah'],
            textStyle: { color: '#94a3b8', fontSize: 10, fontFamily: 'ui-monospace, monospace' },
            inRange: {
                color: ['#0f172a', '#06b6d4', '#f43f5e'],
            },
        },
        series: [{
            type: 'heatmap',
            data: data,
            label: {
                show: true,
                color: '#fff',
                fontSize: 10,
                fontWeight: 600,
                fontFamily: 'ui-monospace, monospace',
                formatter: (p) => p.data[2] > 0 ? formatNumber(p.data[2]) : '',
            },
            emphasis: {
                itemStyle: {
                    borderColor: '#22d3ee',
                    borderWidth: 2,
                    shadowBlur: 10,
                    shadowColor: 'rgba(6, 182, 212, 0.5)',
                },
            },
            itemStyle: {
                borderColor: '#1e293b',
                borderWidth: 2,
                borderRadius: 2,
            },
        }],
    };
});

function onMatrixClick(params) {
    if (!params.data) return;
    const d = params.data; // [xIdx, yIdx, value, kategori_id, platform_name]
    const platformName = d[4];
    const kategoriId = d[3];

    if (platformName) toggleFilter('platform_name', platformName);
    if (kategoriId) toggleFilter('kategori_id', kategoriId);
}

// ── Active filter badges ───────────────────────────────────────────
const activeFilterBadges = computed(() => {
    const badges = [];
    if (activeFilters.wilayah_kode) {
        const wil = props.filterOptions?.wilayah?.find(w => w.kode === activeFilters.wilayah_kode);
        badges.push({ key: 'wilayah_kode', label: wil?.nama || activeFilters.wilayah_kode, color: 'teal' });
    }
    if (activeFilters.platform_name) {
        badges.push({ key: 'platform_name', label: activeFilters.platform_name, color: 'amber' });
    }
    if (activeFilters.kategori_id) {
        const kat = props.filterOptions?.kategori?.find(k => k.id == activeFilters.kategori_id);
        badges.push({ key: 'kategori_id', label: kat?.nama || 'Kategori #' + activeFilters.kategori_id, color: 'cyan' });
    }
    if (activeFilters.bulan) {
        const bulanOpt = props.filterOptions?.bulan?.find(b => b.value == activeFilters.bulan);
        badges.push({ key: 'bulan', label: bulanOpt?.label || 'Bulan ' + activeFilters.bulan, color: 'emerald' });
    }
    if (activeFilters.gender) {
        badges.push({ key: 'gender', label: activeFilters.gender, color: 'pink' });
    }
    if (activeFilters.pendidikan) {
        badges.push({ key: 'pendidikan', label: activeFilters.pendidikan, color: 'indigo' });
    }
    if (activeFilters.usia_group) {
        badges.push({ key: 'usia_group', label: 'Usia ' + activeFilters.usia_group, color: 'rose' });
    }
    if (activeFilters.tahun) {
        badges.push({ key: 'tahun', label: 'Tahun ' + activeFilters.tahun, color: 'violet' });
    }
    return badges;
});

const badgeColors = {
    cyan:    'bg-cyan-500/15 border-cyan-500/30 text-cyan-300',
    emerald: 'bg-emerald-500/15 border-emerald-500/30 text-emerald-300',
    pink:    'bg-pink-500/15 border-pink-500/30 text-pink-300',
    indigo:  'bg-indigo-500/15 border-indigo-500/30 text-indigo-300',
    amber:   'bg-amber-500/15 border-amber-500/30 text-amber-300',
    violet:  'bg-violet-500/15 border-violet-500/30 text-violet-300',
    rose:    'bg-rose-500/15 border-rose-500/30 text-rose-300',
    teal:    'bg-teal-500/15 border-teal-500/30 text-teal-300',
};
</script>

<template>
    <Head title="Peta & Platform — Geospatial Command Center" />

    <PimpinanLayout title="Threat Radar">

        <!-- RIGHT FILTER DRAWER — Backdrop -->
        <Transition
            enter-active-class="transition-opacity duration-300"
            leave-active-class="transition-opacity duration-300"
            enter-from-class="opacity-0"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isFilterDrawerOpen"
                class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm"
                @click="isFilterDrawerOpen = false"
            />
        </Transition>

        <!-- RIGHT FILTER DRAWER — Panel -->
        <aside
            class="fixed inset-y-0 right-0 z-50 w-80 bg-slate-950 border-l border-cyan-500/20 shadow-2xl transform transition-transform duration-300 flex flex-col"
            :class="isFilterDrawerOpen ? 'translate-x-0' : 'translate-x-full'"
        >
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-800 flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-cyan-500/10 rounded-lg border border-cyan-500/20">
                        <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-white uppercase tracking-widest font-mono">Filters</h2>
                        <p class="text-[10px] text-slate-600 font-mono tracking-wider">CROSS-FILTER PANEL</p>
                    </div>
                </div>
                <button @click="isFilterDrawerOpen = false" class="p-1.5 text-slate-500 hover:text-white hover:bg-slate-800 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-5 py-4 space-y-5 drawer-scroll">
                <!-- Active badges in drawer -->
                <div v-if="hasAnyFilter" class="space-y-3">
                    <div class="flex flex-wrap gap-1.5">
                        <span
                            v-for="badge in activeFilterBadges" :key="badge.key"
                            class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-medium rounded-md border cursor-pointer transition-all hover:opacity-80 font-mono"
                            :class="badgeColors[badge.color]"
                            @click="toggleFilter(badge.key, activeFilters[badge.key])"
                        >
                            {{ badge.label }}
                            <svg class="w-3 h-3 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        </span>
                    </div>
                    <button @click="clearAllFilters" class="w-full flex items-center justify-center gap-2 px-3 py-2 bg-red-500/10 hover:bg-red-500/20 border border-red-500/25 rounded-lg text-xs text-red-400 font-bold font-mono uppercase tracking-wider transition-all">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Hapus Semua
                    </button>
                </div>

                <!-- Tahun -->
                <div>
                    <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.15em] mb-2.5 font-mono">Tahun</h3>
                    <div class="space-y-1.5">
                        <button v-for="yr in (filterOptions?.tahun || [])" :key="yr" @click="toggleFilter('tahun', yr)"
                            class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-all text-left font-mono"
                            :class="activeFilters.tahun === yr ? 'bg-violet-500/15 border border-violet-500/30 text-violet-300' : 'text-slate-500 hover:bg-slate-900 hover:text-slate-300 border border-transparent'">
                            <span class="w-3.5 h-3.5 rounded border-2 flex items-center justify-center flex-shrink-0 transition-all"
                                :class="activeFilters.tahun === yr ? 'border-violet-400 bg-violet-500/30' : 'border-slate-700'">
                                <svg v-if="activeFilters.tahun === yr" class="w-2 h-2 text-violet-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            </span>
                            {{ yr }}
                        </button>
                    </div>
                </div>

                <!-- Wilayah -->
                <div>
                    <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.15em] mb-2.5 font-mono">Wilayah / Polres</h3>
                    <div class="space-y-1 max-h-48 overflow-y-auto scrollbar-thin">
                        <button v-for="w in (filterOptions?.wilayah || [])" :key="w.kode" @click="toggleFilter('wilayah_kode', w.kode)"
                            class="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-lg text-sm font-medium transition-all text-left"
                            :class="activeFilters.wilayah_kode === w.kode ? 'bg-teal-500/15 border border-teal-500/30 text-teal-300' : 'text-slate-500 hover:bg-slate-900 hover:text-slate-300 border border-transparent'">
                            <span class="w-3.5 h-3.5 rounded border-2 flex items-center justify-center flex-shrink-0 transition-all"
                                :class="activeFilters.wilayah_kode === w.kode ? 'border-teal-400 bg-teal-500/30' : 'border-slate-700'">
                                <svg v-if="activeFilters.wilayah_kode === w.kode" class="w-2 h-2 text-teal-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            </span>
                            <span class="truncate text-xs font-mono">{{ w.nama }}</span>
                        </button>
                    </div>
                </div>

                <!-- Platform -->
                <div>
                    <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.15em] mb-2.5 font-mono">Platform</h3>
                    <div class="space-y-1 max-h-48 overflow-y-auto scrollbar-thin">
                        <button v-for="plat in (filterOptions?.platform || [])" :key="plat" @click="toggleFilter('platform_name', plat)"
                            class="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-lg text-sm font-medium transition-all text-left"
                            :class="activeFilters.platform_name === plat ? 'bg-amber-500/15 border border-amber-500/30 text-amber-300' : 'text-slate-500 hover:bg-slate-900 hover:text-slate-300 border border-transparent'">
                            <span class="w-3.5 h-3.5 rounded border-2 flex items-center justify-center flex-shrink-0 transition-all"
                                :class="activeFilters.platform_name === plat ? 'border-amber-400 bg-amber-500/30' : 'border-slate-700'">
                                <svg v-if="activeFilters.platform_name === plat" class="w-2 h-2 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            </span>
                            <span class="truncate text-xs font-mono">{{ plat }}</span>
                        </button>
                    </div>
                </div>

                <!-- Gender -->
                <div>
                    <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.15em] mb-2.5 font-mono">Jenis Kelamin</h3>
                    <div class="space-y-1.5">
                        <button v-for="g in (filterOptions?.gender || [])" :key="g" @click="toggleFilter('gender', g)"
                            class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-all text-left font-mono"
                            :class="activeFilters.gender === g ? 'bg-pink-500/15 border border-pink-500/30 text-pink-300' : 'text-slate-500 hover:bg-slate-900 hover:text-slate-300 border border-transparent'">
                            <span class="w-3.5 h-3.5 rounded border-2 flex items-center justify-center flex-shrink-0 transition-all"
                                :class="activeFilters.gender === g ? 'border-pink-400 bg-pink-500/30' : 'border-slate-700'">
                                <svg v-if="activeFilters.gender === g" class="w-2 h-2 text-pink-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            </span>
                            {{ g }}
                        </button>
                    </div>
                </div>

                <!-- Pendidikan -->
                <div>
                    <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.15em] mb-2.5 font-mono">Pendidikan</h3>
                    <div class="space-y-1 max-h-40 overflow-y-auto scrollbar-thin">
                        <button v-for="edu in (filterOptions?.pendidikan || [])" :key="edu" @click="toggleFilter('pendidikan', edu)"
                            class="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-lg text-sm font-medium transition-all text-left"
                            :class="activeFilters.pendidikan === edu ? 'bg-indigo-500/15 border border-indigo-500/30 text-indigo-300' : 'text-slate-500 hover:bg-slate-900 hover:text-slate-300 border border-transparent'">
                            <span class="w-3.5 h-3.5 rounded border-2 flex items-center justify-center flex-shrink-0 transition-all"
                                :class="activeFilters.pendidikan === edu ? 'border-indigo-400 bg-indigo-500/30' : 'border-slate-700'">
                                <svg v-if="activeFilters.pendidikan === edu" class="w-2 h-2 text-indigo-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            </span>
                            <span class="truncate text-xs">{{ edu }}</span>
                        </button>
                    </div>
                </div>

                <!-- Usia Group -->
                <div>
                    <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.15em] mb-2.5 font-mono">Kelompok Usia</h3>
                    <div class="space-y-1.5">
                        <button v-for="age in (filterOptions?.usia_group || [])" :key="age" @click="toggleFilter('usia_group', age)"
                            class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-all text-left font-mono"
                            :class="activeFilters.usia_group === age ? 'bg-rose-500/15 border border-rose-500/30 text-rose-300' : 'text-slate-500 hover:bg-slate-900 hover:text-slate-300 border border-transparent'">
                            <span class="w-3.5 h-3.5 rounded border-2 flex items-center justify-center flex-shrink-0 transition-all"
                                :class="activeFilters.usia_group === age ? 'border-rose-400 bg-rose-500/30' : 'border-slate-700'">
                                <svg v-if="activeFilters.usia_group === age" class="w-2 h-2 text-rose-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            </span>
                            {{ age }}
                        </button>
                    </div>
                </div>
            </div>
        </aside>

        <!-- FULL-WIDTH MAIN CONTENT -->
        <div class="w-full space-y-0">

            <!-- HERO: TACTICAL MAP ZONE -->
            <div class="relative w-full rounded-xl overflow-hidden border border-cyan-500/10 shadow-2xl" style="min-height: 780px">

                <!-- Map Background Glow -->
                <div class="absolute inset-0 bg-gradient-to-b from-slate-950 via-slate-950 to-slate-900 pointer-events-none z-0"></div>

                <!-- Floating KPI Cards (absolute over map) -->
                <div class="absolute top-4 left-1/2 -translate-x-1/2 z-20 flex items-stretch gap-3 pointer-events-auto">

                    <!-- Total Pengaduan -->
                    <div class="kpi-card group hover:border-cyan-500/40">
                        <div class="flex items-center gap-2.5 mb-1">
                            <div class="p-1 bg-cyan-500/15 rounded">
                                <svg class="w-3.5 h-3.5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <span class="text-[9px] font-bold text-slate-500 uppercase tracking-[0.2em] font-mono">Laporan</span>
                        </div>
                        <p class="text-2xl font-black text-white tracking-tight font-mono">{{ formatNumber(keyStats?.total_laporan) }}</p>
                        <div class="flex items-center gap-1.5 mt-1">
                            <span class="text-[10px] text-emerald-400 font-bold font-mono">{{ keyStats?.clearance_rate || 0 }}%</span>
                            <span class="text-[10px] text-slate-600 font-mono">CR</span>
                        </div>
                    </div>

                    <!-- Total Kerugian -->
                    <div class="kpi-card group hover:border-rose-500/40">
                        <div class="flex items-center gap-2.5 mb-1">
                            <div class="p-1 bg-rose-500/15 rounded">
                                <svg class="w-3.5 h-3.5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <span class="text-[9px] font-bold text-slate-500 uppercase tracking-[0.2em] font-mono">Kerugian</span>
                        </div>
                        <p class="text-2xl font-black text-white tracking-tight font-mono">{{ formatCompact(keyStats?.total_kerugian) }}</p>
                        <p class="text-[10px] text-slate-600 mt-1 truncate font-mono" :title="formatRupiah(keyStats?.total_kerugian)">{{ formatRupiah(keyStats?.total_kerugian) }}</p>
                    </div>

                    <!-- Kasus Selesai -->
                    <div class="kpi-card group hover:border-emerald-500/40">
                        <div class="flex items-center gap-2.5 mb-1">
                            <div class="p-1 bg-emerald-500/15 rounded">
                                <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <span class="text-[9px] font-bold text-slate-500 uppercase tracking-[0.2em] font-mono">Selesai</span>
                        </div>
                        <p class="text-2xl font-black text-white tracking-tight font-mono">{{ formatNumber(keyStats?.total_selesai) }}</p>
                        <div class="w-full bg-slate-800/80 rounded-full h-1 mt-1.5 overflow-hidden">
                            <div class="bg-emerald-500 h-full rounded-full transition-all duration-700" :style="{ width: Math.min(keyStats?.clearance_rate || 0, 100) + '%' }"></div>
                        </div>
                    </div>
                </div>

                <!-- Map View Indicator -->
                <div class="absolute top-4 left-4 z-20 pointer-events-auto">
                    <div class="flex items-center gap-2 px-3 py-1.5 bg-slate-900/80 backdrop-blur-sm border border-slate-700/50 rounded-lg">
                        <span class="relative flex w-2 h-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-cyan-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full w-2 h-2 bg-cyan-400"></span>
                        </span>
                        <span class="text-[10px] font-mono tracking-widest uppercase text-cyan-400">
                            JAWA TENGAH · TACTICAL
                        </span>
                    </div>
                </div>

                <!-- Active Badges + Filter (top-right) -->
                <div class="absolute top-4 right-4 z-20 flex items-center gap-2 pointer-events-auto">
                    <div class="relative">
                        <select
                            :value="activeFilters.tahun"
                            @change="activeFilters.tahun = $event.target.value ? Number($event.target.value) : null; navigateWithFilters()"
                            class="appearance-none cursor-pointer pl-8 pr-8 py-2 bg-slate-900/90 backdrop-blur-sm hover:bg-slate-800 border border-slate-700/50 hover:border-cyan-500/40 rounded-lg text-xs font-bold text-slate-300 transition-all shadow-lg focus:outline-none focus:ring-1 focus:ring-cyan-500/40 font-mono"
                        >
                            <option :value="null">ALL YEARS</option>
                            <option v-for="year in (filterOptions?.tahun || [])" :key="year" :value="year">{{ year }}</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5">
                            <svg class="w-3.5 h-3.5 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        </div>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5">
                            <svg class="w-3 h-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                        </div>
                    </div>

                    <button
                        @click="isFilterDrawerOpen = true"
                        class="flex items-center gap-2 px-3 py-2 bg-slate-900/90 backdrop-blur-sm hover:bg-slate-800 border border-slate-700/50 hover:border-cyan-500/40 rounded-lg text-xs font-bold text-slate-300 transition-all shadow-lg group font-mono"
                    >
                        <svg class="w-3.5 h-3.5 text-cyan-500 group-hover:text-cyan-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                        </svg>
                        <span class="tracking-wider uppercase text-[10px]">Filters</span>
                        <span v-if="activeFilterCount > 0" class="inline-flex items-center justify-center w-4 h-4 text-[9px] font-black rounded-full bg-cyan-500 text-slate-950">{{ activeFilterCount }}</span>
                    </button>
                </div>

                <!-- THE MAP -->
                <div v-if="!mapReady" class="absolute inset-0 flex items-center justify-center z-10">
                    <div class="flex flex-col items-center gap-3">
                        <div class="w-10 h-10 border-2 border-cyan-500/30 border-t-cyan-400 rounded-full animate-spin"></div>
                        <span class="text-xs font-mono text-cyan-500/60 tracking-widest uppercase">Loading geojson</span>
                    </div>
                </div>



                <v-chart
                    v-if="mapReady"
                    ref="mapChartRef"
                    class="w-full"
                    style="height: 780px; position: relative; z-index: 1"
                    :option="mapOption"
                    :update-options="{ notMerge: true }"
                    autoresize
                    @click="onMapClick"
                />

                <!-- Active filter badges bar (bottom of map) -->
                <div v-if="hasAnyFilter" class="absolute bottom-3 left-1/2 -translate-x-1/2 z-20 flex items-center gap-1.5 px-3 py-2 bg-slate-950/90 backdrop-blur-sm border border-slate-700/50 rounded-xl pointer-events-auto max-w-[90%] overflow-x-auto scrollbar-thin">
                    <span
                        v-for="badge in activeFilterBadges" :key="badge.key"
                        class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold rounded border cursor-pointer transition-all hover:opacity-80 font-mono whitespace-nowrap"
                        :class="badgeColors[badge.color]"
                        @click="toggleFilter(badge.key, activeFilters[badge.key])"
                    >
                        {{ badge.label }}
                        <svg class="w-2.5 h-2.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                    </span>
                    <button @click="clearAllFilters" class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold rounded border cursor-pointer transition-all bg-red-500/10 border-red-500/25 text-red-400 hover:bg-red-500/20 font-mono whitespace-nowrap">
                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        RESET
                    </button>
                </div>


            </div>

            <!-- HEATMAP — Cyber Threat Matrix -->
            <div class="mt-5 bg-slate-950 rounded-xl border border-slate-800/80 shadow-xl overflow-hidden">
                <div class="px-5 pt-4 pb-2 flex items-center justify-between border-b border-slate-800/50">
                    <div class="flex items-center gap-2.5">
                        <div class="p-1.5 bg-cyan-500/10 rounded border border-cyan-500/15">
                            <svg class="w-3.5 h-3.5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-white font-mono uppercase tracking-wide">Matriks Vektor Serangan</h3>
                            <p class="text-[10px] text-slate-600 font-mono tracking-widest">PLATFORM VS KATEGORI KEJAHATAN</p>
                        </div>
                    </div>
                    <span class="text-[10px] text-slate-600 font-mono">{{ heatmapData.platforms.length }} PLATFORMS · {{ heatmapData.categories.length }} KATEGORI</span>
                </div>
                <v-chart
                    v-if="heatmapData.data.length"
                    class="w-full"
                    style="height: 450px"
                    :option="heatmapOption"
                    :update-options="{ notMerge: true }"
                    autoresize
                    @click="onMatrixClick"
                />
                <div v-else class="flex items-center justify-center h-48 text-slate-600 text-xs font-mono tracking-widest uppercase">
                    Tidak ada data matriks
                </div>
            </div>

            <!-- BOTTOM ANALYSIS PANEL -->
            <div class="mt-5 grid grid-cols-1 lg:grid-cols-2 gap-5">

                <!-- Top Platform -->
                <div class="bg-slate-950 rounded-xl border border-slate-800/80 shadow-xl overflow-hidden">
                    <div class="px-5 pt-4 pb-2 flex items-center justify-between border-b border-slate-800/50">
                        <div class="flex items-center gap-2.5">
                            <div class="p-1.5 bg-amber-500/10 rounded border border-amber-500/15">
                                <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-white font-mono uppercase tracking-wide">Top Platform</h3>
                                <p class="text-[10px] text-slate-600 font-mono tracking-widest">ATTACK VECTOR ANALYSIS</p>
                            </div>
                        </div>
                        <span class="text-[10px] text-slate-600 font-mono">{{ (platformLeaderboard || []).length }} ITEMS</span>
                    </div>
                    <v-chart
                        class="w-full"
                        :style="{ height: Math.max(280, (platformLeaderboard || []).length * 24 + 40) + 'px' }"
                        :option="platformBarOption"
                        autoresize
                        @click="onPlatformBarClick"
                    />
                </div>

                <!-- Top Wilayah by Kerugian -->
                <div class="bg-slate-950 rounded-xl border border-slate-800/80 shadow-xl overflow-hidden">
                    <div class="px-5 pt-4 pb-2 flex items-center justify-between border-b border-slate-800/50">
                        <div class="flex items-center gap-2.5">
                            <div class="p-1.5 bg-rose-500/10 rounded border border-rose-500/15">
                                <svg class="w-3.5 h-3.5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm0 0h18" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-white font-mono uppercase tracking-wide">Top Wilayah</h3>
                                <p class="text-[10px] text-slate-600 font-mono tracking-widest">KERUGIAN LEADERBOARD</p>
                            </div>
                        </div>
                        <span class="text-[10px] text-slate-600 font-mono">{{ (regionalLeaderboard || []).length }} ITEMS</span>
                    </div>
                    <v-chart
                        class="w-full"
                        :style="{ height: Math.max(280, (regionalLeaderboard || []).length * 32 + 40) + 'px' }"
                        :option="regionalBarOption"
                        autoresize
                        @click="onRegionalBarClick"
                    />
                </div>
            </div>

        </div>
    </PimpinanLayout>
</template>

<style scoped>
.kpi-card {
    @apply px-4 py-3 rounded-xl border border-slate-700/30 transition-all shadow-xl;
    background: rgba(2, 6, 23, 0.85);
    backdrop-filter: blur(12px);
    min-width: 160px;
}

.drawer-scroll::-webkit-scrollbar,
.scrollbar-thin::-webkit-scrollbar {
    width: 3px;
    height: 3px;
}
.drawer-scroll::-webkit-scrollbar-track,
.scrollbar-thin::-webkit-scrollbar-track {
    background: transparent;
}
.drawer-scroll::-webkit-scrollbar-thumb,
.scrollbar-thin::-webkit-scrollbar-thumb {
    background: #1e293b;
    border-radius: 4px;
}
.drawer-scroll::-webkit-scrollbar-thumb:hover,
.scrollbar-thin::-webkit-scrollbar-thumb:hover {
    background: #334155;
}
</style>
