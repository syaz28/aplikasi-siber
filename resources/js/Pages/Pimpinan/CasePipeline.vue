<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import PimpinanLayout from '@/Layouts/PimpinanLayout.vue';

// ── ECharts tree-shakable imports ──────────────────────────────────
import VChart from 'vue-echarts';
import { use } from 'echarts/core';
import { CanvasRenderer } from 'echarts/renderers';
import { PieChart, LineChart, BarChart } from 'echarts/charts';
import {
    TitleComponent,
    TooltipComponent,
    LegendComponent,
    GridComponent,
} from 'echarts/components';

use([
    CanvasRenderer,
    PieChart,
    LineChart,
    BarChart,
    TitleComponent,
    TooltipComponent,
    LegendComponent,
    GridComponent,
]);

// ── Props ──────────────────────────────────────────────────────────
const props = defineProps({
    keyStats: Object,
    statusRing: Array,
    monthlyTrend: Array,
    subditMatrix: Array,
    filterOptions: Object,
    appliedFilters: Object,
});

// ══════════════════════════════════════════════════════════════════════
// CONSTANTS
// ══════════════════════════════════════════════════════════════════════

const STATUS_COLORS = {
    'Penyelidikan': '#f59e0b',
    'Penyidikan':   '#f97316',
    'Tahap I':      '#3b82f6',
    'Tahap II':     '#6366f1',
    'SP3':          '#64748b',
    'RJ':           '#14b8a6',
    'Diversi':      '#10b981',
};

const STATUS_ORDER = [
    'Penyelidikan', 'Penyidikan', 'Tahap I', 'Tahap II', 'SP3', 'RJ', 'Diversi',
];

const DARK_TOOLTIP = {
    backgroundColor: 'rgba(2, 6, 23, 0.96)',
    borderColor: 'rgba(6, 182, 212, 0.4)',
    borderWidth: 1,
    textStyle: { color: '#e2e8f0', fontSize: 12, fontFamily: 'ui-monospace, monospace' },
    padding: [10, 14],
    extraCssText: 'box-shadow: 0 0 20px rgba(6,182,212,0.15);',
};

// ══════════════════════════════════════════════════════════════════════
// FILTER DRAWER STATE
// ══════════════════════════════════════════════════════════════════════
const isFilterDrawerOpen = ref(false);

// ══════════════════════════════════════════════════════════════════════
// CROSS-FILTER INTERACTIVITY
// ══════════════════════════════════════════════════════════════════════
const activeFilters = reactive({
    tahun: props.appliedFilters?.tahun ?? null,
    bulan: props.appliedFilters?.bulan ?? null,
    kategori_id: props.appliedFilters?.kategori_id ?? null,
    wilayah_kode: props.appliedFilters?.wilayah_kode ?? null,
    status: props.appliedFilters?.status ?? null,
    subdit: props.appliedFilters?.subdit ?? null,
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
    router.get('/pimpinan/case-pipeline', params, {
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
const formatNumber = (v) => {
    if (!v && v !== 0) return '0';
    return Number(v).toLocaleString('id-ID');
};

// ══════════════════════════════════════════════════════════════════════
// FILTER BADGES
// ══════════════════════════════════════════════════════════════════════
const activeFilterBadges = computed(() => {
    const badges = [];
    if (activeFilters.tahun) {
        badges.push({ key: 'tahun', label: 'Tahun ' + activeFilters.tahun, color: 'violet' });
    }
    if (activeFilters.bulan) {
        const bulanOpt = props.filterOptions?.bulan?.find(b => b.value == activeFilters.bulan);
        badges.push({ key: 'bulan', label: bulanOpt?.label || 'Bulan ' + activeFilters.bulan, color: 'emerald' });
    }
    if (activeFilters.kategori_id) {
        const kat = props.filterOptions?.kategori?.find(k => k.id == activeFilters.kategori_id);
        badges.push({ key: 'kategori_id', label: kat?.nama || 'Kategori #' + activeFilters.kategori_id, color: 'cyan' });
    }
    if (activeFilters.wilayah_kode) {
        const wil = props.filterOptions?.wilayah?.find(w => w.kode === activeFilters.wilayah_kode);
        badges.push({ key: 'wilayah_kode', label: wil?.nama || activeFilters.wilayah_kode, color: 'teal' });
    }
    if (activeFilters.status) {
        badges.push({ key: 'status', label: activeFilters.status, color: 'amber' });
    }
    if (activeFilters.subdit) {
        badges.push({ key: 'subdit', label: 'Subdit ' + activeFilters.subdit, color: 'rose' });
    }
    return badges;
});

const badgeColors = {
    cyan:    'bg-cyan-500/15 border-cyan-500/30 text-cyan-300',
    emerald: 'bg-emerald-500/15 border-emerald-500/30 text-emerald-300',
    amber:   'bg-amber-500/15 border-amber-500/30 text-amber-300',
    violet:  'bg-violet-500/15 border-violet-500/30 text-violet-300',
    rose:    'bg-rose-500/15 border-rose-500/30 text-rose-300',
    teal:    'bg-teal-500/15 border-teal-500/30 text-teal-300',
};

// ══════════════════════════════════════════════════════════════════════
// CHART 1: TACTICAL STATUS RING (Doughnut)
// ══════════════════════════════════════════════════════════════════════
const statusRingOption = computed(() => {
    const data = props.statusRing || [];
    if (!data.length) return {};

    const activeStatus = activeFilters.status;

    return {
        tooltip: {
            ...DARK_TOOLTIP,
            trigger: 'item',
            formatter: (p) => {
                const color = STATUS_COLORS[p.name] || '#94a3b8';
                return '<div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">'
                    + '<span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:' + color + '"></span>'
                    + '<span style="font-weight:700;color:' + color + ';letter-spacing:0.5px">' + p.name + '</span>'
                    + '</div>'
                    + '<div>Kasus: <b style="color:#f59e0b">' + formatNumber(p.value) + '</b></div>'
                    + '<div style="color:#64748b;font-size:10px">' + p.percent.toFixed(1) + '% dari total</div>';
            },
        },
        legend: { show: false },
        series: [{
            type: 'pie',
            radius: ['52%', '78%'],
            center: ['50%', '50%'],
            avoidLabelOverlap: true,
            padAngle: 2,
            itemStyle: {
                borderRadius: 6,
                borderColor: '#020617',
                borderWidth: 3,
            },
            label: {
                show: true,
                color: '#94a3b8',
                fontSize: 10,
                fontFamily: 'ui-monospace, monospace',
                formatter: (p) => p.value > 0 ? p.name + '\n' + formatNumber(p.value) : '',
            },
            labelLine: {
                lineStyle: { color: '#334155', width: 1 },
                length: 12,
                length2: 8,
            },
            emphasis: {
                scale: true,
                scaleSize: 8,
                itemStyle: {
                    shadowBlur: 20,
                    shadowColor: 'rgba(6, 182, 212, 0.4)',
                },
            },
            data: data.map(d => ({
                name: d.status,
                value: d.total,
                itemStyle: {
                    color: STATUS_COLORS[d.status] || '#64748b',
                    opacity: activeStatus && activeStatus !== d.status ? 0.2 : 1,
                },
            })),
        }],
    };
});

function onStatusRingClick(params) {
    if (params.name) {
        toggleFilter('status', params.name);
    }
}

// ══════════════════════════════════════════════════════════════════════
// CHART 2: MULTI-WAVE TREND LINE
// ══════════════════════════════════════════════════════════════════════
const trendLineOption = computed(() => {
    const data = props.monthlyTrend || [];
    if (!data.length) return {};

    const months = data.map(d => d.month_name);
    const activeStatus = activeFilters.status;

    return {
        tooltip: {
            ...DARK_TOOLTIP,
            trigger: 'axis',
            axisPointer: { type: 'cross', lineStyle: { color: '#334155' }, crossStyle: { color: '#334155' } },
            formatter: (params) => {
                let html = '<div style="font-weight:700;color:#e2e8f0;margin-bottom:6px;letter-spacing:0.5px">'
                    + params[0].axisValue + '</div>';
                params.forEach(p => {
                    if (p.value > 0) {
                        html += '<div style="display:flex;align-items:center;gap:6px;margin:2px 0">'
                            + '<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:' + p.color + '"></span>'
                            + '<span style="color:#94a3b8;flex:1">' + p.seriesName + '</span>'
                            + '<b style="color:' + p.color + '">' + formatNumber(p.value) + '</b>'
                            + '</div>';
                    }
                });
                return html;
            },
        },
        legend: {
            show: true,
            bottom: 0,
            itemWidth: 12,
            itemHeight: 8,
            itemGap: 14,
            textStyle: { color: '#94a3b8', fontSize: 10, fontFamily: 'ui-monospace, monospace' },
            icon: 'roundRect',
        },
        grid: { top: 16, right: 16, bottom: 40, left: 40 },
        xAxis: {
            type: 'category',
            data: months,
            boundaryGap: false,
            axisLabel: { color: '#64748b', fontSize: 10, fontFamily: 'ui-monospace, monospace' },
            axisLine: { lineStyle: { color: '#1e293b' } },
            axisTick: { show: false },
        },
        yAxis: {
            type: 'value',
            axisLabel: { color: '#475569', fontSize: 10, fontFamily: 'ui-monospace, monospace' },
            splitLine: { lineStyle: { color: '#1e293b', type: 'dashed' } },
            axisLine: { show: false },
            axisTick: { show: false },
        },
        series: STATUS_ORDER.map(status => ({
            name: status,
            type: 'line',
            smooth: true,
            symbol: 'circle',
            symbolSize: 6,
            showSymbol: true,
            data: data.map(d => d[status] ?? 0),
            lineStyle: {
                color: STATUS_COLORS[status],
                width: activeStatus === status ? 3 : 2,
                opacity: activeStatus && activeStatus !== status ? 0.15 : 1,
            },
            itemStyle: {
                color: STATUS_COLORS[status],
                opacity: activeStatus && activeStatus !== status ? 0.15 : 1,
            },
            areaStyle: activeStatus === status ? {
                color: {
                    type: 'linear', x: 0, y: 0, x2: 0, y2: 1,
                    colorStops: [
                        { offset: 0, color: STATUS_COLORS[status] + '30' },
                        { offset: 1, color: STATUS_COLORS[status] + '05' },
                    ],
                },
            } : undefined,
            emphasis: {
                focus: 'series',
                itemStyle: { borderColor: '#fff', borderWidth: 2 },
            },
            z: activeStatus === status ? 10 : 1,
        })),
    };
});

function onTrendLineClick(params) {
    if (params.dataIndex !== undefined) {
        const monthValue = params.dataIndex + 1;
        toggleFilter('bulan', monthValue);
    }
}

// ══════════════════════════════════════════════════════════════════════
// CHART 3: SUBDIT LOAD MATRIX (Stacked Horizontal Bar)
// ══════════════════════════════════════════════════════════════════════
const subditMatrixOption = computed(() => {
    const data = props.subditMatrix || [];
    if (!data.length) return {};

    const labels = data.map(d => d.label);
    const activeStatus = activeFilters.status;
    const activeSubdit = activeFilters.subdit;

    return {
        tooltip: {
            ...DARK_TOOLTIP,
            trigger: 'axis',
            axisPointer: { type: 'shadow' },
            formatter: (params) => {
                let total = 0;
                let html = '<div style="font-weight:700;color:#e2e8f0;margin-bottom:6px;letter-spacing:0.5px">'
                    + params[0].axisValue + '</div>';
                params.forEach(p => {
                    if (p.value > 0) {
                        total += p.value;
                        html += '<div style="display:flex;align-items:center;gap:6px;margin:2px 0">'
                            + '<span style="display:inline-block;width:8px;height:8px;border-radius:2px;background:' + p.color + '"></span>'
                            + '<span style="color:#94a3b8;flex:1">' + p.seriesName + '</span>'
                            + '<b style="color:' + p.color + '">' + formatNumber(p.value) + '</b>'
                            + '</div>';
                    }
                });
                html += '<div style="border-top:1px solid #334155;margin-top:4px;padding-top:4px;color:#e2e8f0;font-weight:700">Total: '
                    + formatNumber(total) + '</div>';
                return html;
            },
        },
        legend: {
            show: true,
            bottom: 0,
            itemWidth: 12,
            itemHeight: 8,
            itemGap: 14,
            textStyle: { color: '#94a3b8', fontSize: 10, fontFamily: 'ui-monospace, monospace' },
            icon: 'roundRect',
        },
        grid: { top: 12, right: 40, bottom: 40, left: 8, containLabel: true },
        xAxis: {
            type: 'value',
            axisLabel: { color: '#475569', fontSize: 10, fontFamily: 'ui-monospace, monospace' },
            splitLine: { lineStyle: { color: '#1e293b', type: 'dashed' } },
            axisLine: { show: false },
            axisTick: { show: false },
        },
        yAxis: {
            type: 'category',
            data: labels,
            inverse: true,
            axisLabel: {
                color: '#cbd5e1',
                fontSize: 12,
                fontWeight: 600,
                fontFamily: 'ui-monospace, monospace',
            },
            axisTick: { show: false },
            axisLine: { lineStyle: { color: '#1e293b' } },
        },
        series: STATUS_ORDER.map(status => ({
            name: status,
            type: 'bar',
            stack: 'total',
            barMaxWidth: 28,
            data: data.map((d, idx) => {
                const subditId = d.subdit;
                const isActiveSubdit = activeSubdit && activeSubdit !== subditId;
                const isActiveStatus = activeStatus && activeStatus !== status;
                const dimmed = isActiveSubdit || isActiveStatus;
                return {
                    value: d[status] ?? 0,
                    itemStyle: {
                        color: STATUS_COLORS[status],
                        opacity: dimmed ? 0.2 : 1,
                        borderRadius: 0,
                    },
                };
            }),
            emphasis: {
                itemStyle: {
                    shadowBlur: 10,
                    shadowColor: 'rgba(6, 182, 212, 0.3)',
                },
            },
        })),
    };
});

function onSubditMatrixClick(params) {
    const data = props.subditMatrix || [];
    const subditItem = data[params.dataIndex];
    if (subditItem) {
        toggleFilter('subdit', subditItem.subdit);
    }
    if (params.seriesName) {
        toggleFilter('status', params.seriesName);
    }
}
</script>

<template>
    <Head title="Case Pipeline — Status & Progression Monitor" />

    <PimpinanLayout title="Case Pipeline">

        <!-- FULL-WIDTH MAIN CONTENT -->
        <div class="w-full space-y-5">

            <!-- TOP: FILTER BAR -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <!-- Year Select -->
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

                    <!-- Filter Drawer Toggle -->
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

                <!-- Active Badges -->
                <div v-if="hasAnyFilter" class="flex items-center gap-1.5 max-w-[60%] overflow-x-auto scrollbar-thin">
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

            <!-- ROW 1: KPI CARDS -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

                <!-- Total Kasus -->
                <div class="kpi-card group hover:border-cyan-500/40">
                    <div class="flex items-center gap-2.5 mb-2">
                        <div class="p-1.5 bg-cyan-500/10 rounded border border-cyan-500/15">
                            <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <span class="text-[9px] font-bold text-slate-500 uppercase tracking-[0.2em] font-mono">Total Kasus</span>
                    </div>
                    <p class="text-3xl font-black text-white tracking-tight font-mono">{{ formatNumber(keyStats?.total_kasus) }}</p>
                </div>

                <!-- Kasus Aktif -->
                <div class="kpi-card group hover:border-amber-500/40">
                    <div class="flex items-center gap-2.5 mb-2">
                        <div class="p-1.5 bg-amber-500/10 rounded border border-amber-500/15">
                            <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span class="text-[9px] font-bold text-slate-500 uppercase tracking-[0.2em] font-mono">Kasus Aktif</span>
                    </div>
                    <p class="text-3xl font-black text-amber-400 tracking-tight font-mono">{{ formatNumber(keyStats?.kasus_aktif) }}</p>
                    <p class="text-[10px] text-slate-600 font-mono mt-1">Penyelidikan — Tahap II</p>
                </div>

                <!-- Kasus Selesai -->
                <div class="kpi-card group hover:border-emerald-500/40">
                    <div class="flex items-center gap-2.5 mb-2">
                        <div class="p-1.5 bg-emerald-500/10 rounded border border-emerald-500/15">
                            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span class="text-[9px] font-bold text-slate-500 uppercase tracking-[0.2em] font-mono">Kasus Selesai</span>
                    </div>
                    <p class="text-3xl font-black text-emerald-400 tracking-tight font-mono">{{ formatNumber(keyStats?.kasus_selesai) }}</p>
                    <p class="text-[10px] text-slate-600 font-mono mt-1">SP3 / RJ / Diversi</p>
                </div>

                <!-- Clearance Rate -->
                <div class="kpi-card group hover:border-indigo-500/40">
                    <div class="flex items-center gap-2.5 mb-2">
                        <div class="p-1.5 bg-indigo-500/10 rounded border border-indigo-500/15">
                            <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                        <span class="text-[9px] font-bold text-slate-500 uppercase tracking-[0.2em] font-mono">Clearance Rate</span>
                    </div>
                    <p class="text-3xl font-black text-indigo-400 tracking-tight font-mono">{{ keyStats?.clearance_rate || 0 }}%</p>
                    <div class="w-full bg-slate-800/80 rounded-full h-1.5 mt-2 overflow-hidden">
                        <div class="bg-indigo-500 h-full rounded-full transition-all duration-700" :style="{ width: Math.min(keyStats?.clearance_rate || 0, 100) + '%' }"></div>
                    </div>
                </div>
            </div>

            <!-- ROW 2: STATUS RING + TREND LINE (40/60 Split) -->
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-5">

                <!-- LEFT: Tactical Status Ring -->
                <div class="lg:col-span-2 bg-slate-950 rounded-xl border border-slate-800/80 shadow-xl overflow-hidden">
                    <div class="px-5 pt-4 pb-2 flex items-center justify-between border-b border-slate-800/50">
                        <div class="flex items-center gap-2.5">
                            <div class="p-1.5 bg-amber-500/10 rounded border border-amber-500/15">
                                <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-white font-mono uppercase tracking-wide">Tactical Status Ring</h3>
                                <p class="text-[10px] text-slate-600 font-mono tracking-widest">DISTRIBUSI STATUS PERKARA</p>
                            </div>
                        </div>
                    </div>
                    <v-chart
                        class="w-full"
                        style="height: 380px"
                        :option="statusRingOption"
                        :update-options="{ notMerge: true }"
                        autoresize
                        @click="onStatusRingClick"
                    />
                </div>

                <!-- RIGHT: Multi-Wave Trend Line -->
                <div class="lg:col-span-3 bg-slate-950 rounded-xl border border-slate-800/80 shadow-xl overflow-hidden">
                    <div class="px-5 pt-4 pb-2 flex items-center justify-between border-b border-slate-800/50">
                        <div class="flex items-center gap-2.5">
                            <div class="p-1.5 bg-cyan-500/10 rounded border border-cyan-500/15">
                                <svg class="w-3.5 h-3.5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-white font-mono uppercase tracking-wide">Multi-Wave Trend</h3>
                                <p class="text-[10px] text-slate-600 font-mono tracking-widest">TREN BULANAN PER STATUS</p>
                            </div>
                        </div>
                    </div>
                    <v-chart
                        class="w-full"
                        style="height: 380px"
                        :option="trendLineOption"
                        :update-options="{ notMerge: true }"
                        autoresize
                        @click="onTrendLineClick"
                    />
                </div>
            </div>

            <!-- ROW 3: SUBDIT LOAD MATRIX (Full Width) -->
            <div class="bg-slate-950 rounded-xl border border-slate-800/80 shadow-xl overflow-hidden">
                <div class="px-5 pt-4 pb-2 flex items-center justify-between border-b border-slate-800/50">
                    <div class="flex items-center gap-2.5">
                        <div class="p-1.5 bg-rose-500/10 rounded border border-rose-500/15">
                            <svg class="w-3.5 h-3.5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm0 0h18" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-white font-mono uppercase tracking-wide">Subdit Load Matrix</h3>
                            <p class="text-[10px] text-slate-600 font-mono tracking-widest">BEBAN PENANGANAN PER SUBDIT</p>
                        </div>
                    </div>
                    <span class="text-[10px] text-slate-600 font-mono">3 SUBDITS · 7 STATUSES</span>
                </div>
                <v-chart
                    class="w-full"
                    style="height: 240px"
                    :option="subditMatrixOption"
                    :update-options="{ notMerge: true }"
                    autoresize
                    @click="onSubditMatrixClick"
                />
            </div>

        </div>

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

                <!-- Bulan -->
                <div>
                    <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.15em] mb-2.5 font-mono">Bulan</h3>
                    <div class="space-y-1 max-h-48 overflow-y-auto scrollbar-thin">
                        <button v-for="b in (filterOptions?.bulan || [])" :key="b.value" @click="toggleFilter('bulan', b.value)"
                            class="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-lg text-sm font-medium transition-all text-left"
                            :class="activeFilters.bulan === b.value ? 'bg-emerald-500/15 border border-emerald-500/30 text-emerald-300' : 'text-slate-500 hover:bg-slate-900 hover:text-slate-300 border border-transparent'">
                            <span class="w-3.5 h-3.5 rounded border-2 flex items-center justify-center flex-shrink-0 transition-all"
                                :class="activeFilters.bulan === b.value ? 'border-emerald-400 bg-emerald-500/30' : 'border-slate-700'">
                                <svg v-if="activeFilters.bulan === b.value" class="w-2 h-2 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            </span>
                            <span class="truncate text-xs font-mono">{{ b.label }}</span>
                        </button>
                    </div>
                </div>

                <!-- Status -->
                <div>
                    <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.15em] mb-2.5 font-mono">Status Perkara</h3>
                    <div class="space-y-1.5">
                        <button v-for="st in (filterOptions?.status || [])" :key="st" @click="toggleFilter('status', st)"
                            class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-all text-left font-mono"
                            :class="activeFilters.status === st ? 'bg-amber-500/15 border border-amber-500/30 text-amber-300' : 'text-slate-500 hover:bg-slate-900 hover:text-slate-300 border border-transparent'">
                            <span class="w-3 h-3 rounded-full flex-shrink-0" :style="{ backgroundColor: STATUS_COLORS[st] || '#64748b' }"></span>
                            {{ st }}
                        </button>
                    </div>
                </div>

                <!-- Subdit -->
                <div>
                    <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.15em] mb-2.5 font-mono">Subdit</h3>
                    <div class="space-y-1.5">
                        <button v-for="sd in (filterOptions?.subdit || [])" :key="sd.value" @click="toggleFilter('subdit', sd.value)"
                            class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-all text-left font-mono"
                            :class="activeFilters.subdit === sd.value ? 'bg-rose-500/15 border border-rose-500/30 text-rose-300' : 'text-slate-500 hover:bg-slate-900 hover:text-slate-300 border border-transparent'">
                            <span class="w-3.5 h-3.5 rounded border-2 flex items-center justify-center flex-shrink-0 transition-all"
                                :class="activeFilters.subdit === sd.value ? 'border-rose-400 bg-rose-500/30' : 'border-slate-700'">
                                <svg v-if="activeFilters.subdit === sd.value" class="w-2 h-2 text-rose-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            </span>
                            {{ sd.label }}
                        </button>
                    </div>
                </div>

                <!-- Kategori -->
                <div>
                    <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.15em] mb-2.5 font-mono">Kategori Kejahatan</h3>
                    <div class="space-y-1 max-h-48 overflow-y-auto scrollbar-thin">
                        <button v-for="kat in (filterOptions?.kategori || [])" :key="kat.id" @click="toggleFilter('kategori_id', kat.id)"
                            class="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-lg text-sm font-medium transition-all text-left"
                            :class="activeFilters.kategori_id === kat.id ? 'bg-cyan-500/15 border border-cyan-500/30 text-cyan-300' : 'text-slate-500 hover:bg-slate-900 hover:text-slate-300 border border-transparent'">
                            <span class="w-3.5 h-3.5 rounded border-2 flex items-center justify-center flex-shrink-0 transition-all"
                                :class="activeFilters.kategori_id === kat.id ? 'border-cyan-400 bg-cyan-500/30' : 'border-slate-700'">
                                <svg v-if="activeFilters.kategori_id === kat.id" class="w-2 h-2 text-cyan-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            </span>
                            <span class="truncate text-xs font-mono">{{ kat.nama }}</span>
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
            </div>
        </aside>

    </PimpinanLayout>
</template>

<style scoped>
.kpi-card {
    @apply px-5 py-4 rounded-xl border border-slate-800/50 transition-all shadow-xl;
    background: rgba(2, 6, 23, 0.85);
    backdrop-filter: blur(12px);
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
