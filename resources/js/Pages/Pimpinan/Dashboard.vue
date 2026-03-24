<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import PimpinanLayout from '@/Layouts/PimpinanLayout.vue';

// ── ECharts tree-shakable imports ──────────────────────────────────
import VChart from 'vue-echarts';
import { use } from 'echarts/core';
import { CanvasRenderer } from 'echarts/renderers';
import { BarChart, LineChart, PieChart } from 'echarts/charts';
import {
    TitleComponent,
    TooltipComponent,
    LegendComponent,
    GridComponent,
    DataZoomComponent,
    MarkLineComponent,
} from 'echarts/components';

use([
    CanvasRenderer,
    BarChart,
    LineChart,
    PieChart,
    TitleComponent,
    TooltipComponent,
    LegendComponent,
    GridComponent,
    DataZoomComponent,
    MarkLineComponent,
]);

// ── Props ──────────────────────────────────────────────────────────
const props = defineProps({
    keyStats: Object,
    monthlyCombo: Array,
    categoriesBar: Array,
    victimProfiling: Object,
    platformSunburst: Array,
    recentReports: Array,
    filterOptions: Object,
    appliedFilters: Object,
});

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
});

watch(() => props.appliedFilters, (val) => {
    if (!val) return;
    activeFilters.kategori_id = val.kategori_id ?? null;
    activeFilters.bulan = val.bulan ?? null;
    activeFilters.gender = val.gender ?? null;
    activeFilters.pendidikan = val.pendidikan ?? null;
    activeFilters.usia_group = val.usia_group ?? null;
    activeFilters.tahun = val.tahun ?? null;
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
    router.get('/pimpinan/dashboard', params, {
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
const EMERALD  = '#10B981';
const ROSE     = '#F43F5E';
const AMBER    = '#F59E0B';
const INDIGO   = '#6366F1';
const VIOLET   = '#8B5CF6';
const BLUE     = '#3B82F6';
const PINK     = '#EC4899';
const TEAL     = '#14B8A6';
const LIME     = '#84CC16';

const PALETTE = [CYAN, BLUE, INDIGO, VIOLET, PINK, ROSE, AMBER, EMERALD, TEAL, LIME, '#0EA5E9', '#A855F7', '#F97316', '#EF4444', '#22D3EE'];

const DARK_TOOLTIP = {
    backgroundColor: 'rgba(15, 23, 42, 0.96)',
    borderColor: '#06b6d4',
    borderWidth: 1,
    textStyle: { color: '#e2e8f0', fontSize: 12 },
    padding: [10, 14],
};

// ══════════════════════════════════════════════════════════════════════
// CHART OPTIONS
// ══════════════════════════════════════════════════════════════════════

// ── 1. Monthly Trend (Line Chart — clickable points) ───────────────
const monthlyLineOption = computed(() => {
    const data = props.monthlyCombo || [];
    const months = data.map(m => m.month);
    const reports = data.map(m => m.report_count);
    const losses = data.map(m => m.total_loss);
    const maxLoss = Math.max(...losses, 0);
    const lossAxisMax = maxLoss > 0 ? Math.ceil(maxLoss * 1.5) : undefined;

    return {
        tooltip: {
            ...DARK_TOOLTIP,
            trigger: 'axis',
            axisPointer: { type: 'cross', crossStyle: { color: '#475569' } },
            formatter: (params) => {
                const idx = params[0]?.dataIndex;
                const d = data[idx];
                let tip = `<div style="font-weight:700;margin-bottom:6px;color:#22d3ee">${params[0].axisValue}</div>`;
                params.forEach(p => {
                    const val = p.seriesName === 'Kerugian' ? formatRupiah(p.value) : formatNumber(p.value) + ' laporan';
                    tip += `<div style="margin:2px 0">${p.marker} ${p.seriesName}: <b>${val}</b></div>`;
                });
                if (d?.highlighted) tip += `<div style="margin-top:6px;color:#22d3ee;font-size:11px">● Bulan aktif (filter)</div>`;
                return tip;
            },
        },
        legend: {
            data: ['Laporan', 'Kerugian'],
            top: 0,
            right: 0,
            textStyle: { color: '#94a3b8', fontSize: 11 },
            icon: 'roundRect',
            itemWidth: 14,
            itemHeight: 8,
        },
        grid: { top: 40, right: 55, bottom: 30, left: 50, containLabel: true },
        xAxis: {
            type: 'category',
            data: months,
            axisLine: { lineStyle: { color: '#334155' } },
            axisLabel: { color: '#94a3b8', fontSize: 11 },
            axisTick: { show: false },
        },
        yAxis: [
            {
                type: 'value',
                name: 'Laporan',
                nameTextStyle: { color: '#64748b', fontSize: 10 },
                axisLabel: { color: '#64748b', fontSize: 10 },
                splitLine: { lineStyle: { color: '#1e293b', type: 'dashed' } },
                axisLine: { show: false },
            },
            {
                type: 'value',
                name: 'Kerugian',
                max: lossAxisMax,
                nameTextStyle: { color: '#64748b', fontSize: 10 },
                axisLabel: {
                    color: '#64748b',
                    fontSize: 10,
                    formatter: (v) => {
                        if (v >= 1e9) return (v / 1e9).toFixed(0) + 'M';
                        if (v >= 1e6) return (v / 1e6).toFixed(0) + 'Jt';
                        if (v >= 1e3) return (v / 1e3).toFixed(0) + 'rb';
                        return v;
                    },
                },
                splitLine: { show: false },
                axisLine: { show: false },
            },
        ],
        series: [
            {
                name: 'Laporan',
                type: 'line',
                data: reports,
                smooth: true,
                symbol: 'circle',
                symbolSize: (val, params) => data[params.dataIndex]?.highlighted ? 14 : 8,
                lineStyle: { color: CYAN, width: 2.5 },
                itemStyle: {
                    color: (params) => data[params.dataIndex]?.highlighted ? '#22d3ee' : CYAN,
                    borderColor: '#0f172a',
                    borderWidth: 2,
                },
                areaStyle: {
                    color: {
                        type: 'linear', x: 0, y: 0, x2: 0, y2: 1,
                        colorStops: [
                            { offset: 0, color: 'rgba(6,182,212,0.2)' },
                            { offset: 1, color: 'rgba(6,182,212,0)' },
                        ],
                    },
                },
            },
            {
                name: 'Kerugian',
                type: 'line',
                yAxisIndex: 1,
                data: losses,
                smooth: true,
                symbol: 'circle',
                symbolSize: 6,
                lineStyle: { color: ROSE, width: 2, type: 'dashed' },
                itemStyle: { color: ROSE, borderColor: '#0f172a', borderWidth: 2 },
                areaStyle: {
                    color: {
                        type: 'linear', x: 0, y: 0, x2: 0, y2: 1,
                        colorStops: [
                            { offset: 0, color: 'rgba(244,63,94,0.12)' },
                            { offset: 1, color: 'rgba(244,63,94,0)' },
                        ],
                    },
                },
            },
        ],
    };
});

function onMonthlyClick(params) {
    const idx = params.dataIndex;
    const d = (props.monthlyCombo || [])[idx];
    if (d) toggleFilter('bulan', d.bulan);
}

// ── 2. Gender Pie (Donut) — clickable slices ───────────────────────
const genderPieOption = computed(() => {
    const vp = props.victimProfiling?.gender || { labels: [], data: [] };
    const genderColors = { 'LAKI-LAKI': BLUE, 'PEREMPUAN': PINK };
    const pieData = vp.labels.map((label, i) => ({
        name: label,
        value: vp.data[i] || 0,
        itemStyle: {
            color: genderColors[label] || PALETTE[i % PALETTE.length],
            opacity: activeFilters.gender && activeFilters.gender !== label ? 0.3 : 1,
        },
    }));

    return {
        tooltip: {
            ...DARK_TOOLTIP,
            trigger: 'item',
            formatter: (p) => `<span style="color:#22d3ee;font-weight:600">${p.name}</span><br/>Jumlah: <b>${formatNumber(p.value)}</b> (${p.percent}%)`,
        },
        legend: {
            bottom: 0,
            textStyle: { color: '#94a3b8', fontSize: 11 },
            icon: 'circle',
            itemWidth: 10,
        },
        series: [{
            type: 'pie',
            radius: ['42%', '72%'],
            center: ['50%', '45%'],
            avoidLabelOverlap: true,
            itemStyle: { borderRadius: 6, borderColor: '#0f172a', borderWidth: 3 },
            label: {
                show: true,
                formatter: '{d}%',
                fontSize: 13,
                fontWeight: 700,
                color: '#e2e8f0',
            },
            emphasis: {
                scale: true,
                scaleSize: 6,
                label: { fontSize: 15 },
            },
            data: pieData,
        }],
    };
});

function onGenderClick(params) {
    if (params.data?.name) toggleFilter('gender', params.data.name);
}

// ── 3. Education Horizontal Bar — clickable bars ───────────────────
const educationBarOption = computed(() => {
    const vp = props.victimProfiling?.education || { labels: [], data: [] };
    const labels = [...vp.labels].reverse();
    const data = [...vp.data].reverse();

    return {
        tooltip: {
            ...DARK_TOOLTIP,
            trigger: 'axis',
            axisPointer: { type: 'shadow' },
            formatter: (params) => {
                const p = params[0];
                return `<span style="color:#22d3ee;font-weight:600">${p.name}</span><br/>Jumlah: <b>${formatNumber(p.value)}</b>`;
            },
        },
        grid: { top: 8, right: 50, bottom: 8, left: 8, containLabel: true },
        xAxis: {
            type: 'value',
            axisLabel: { color: '#64748b', fontSize: 10 },
            splitLine: { lineStyle: { color: '#1e293b', type: 'dashed' } },
            axisLine: { show: false },
        },
        yAxis: {
            type: 'category',
            data: labels,
            axisLabel: { color: '#cbd5e1', fontSize: 11, width: 90, overflow: 'truncate' },
            axisTick: { show: false },
            axisLine: { show: false },
        },
        series: [{
            type: 'bar',
            data: data.map((v, i) => {
                const originalLabel = labels[i];
                const isActive = activeFilters.pendidikan === originalLabel;
                return {
                    value: v,
                    itemStyle: {
                        color: {
                            type: 'linear', x: 0, y: 0, x2: 1, y2: 0,
                            colorStops: [
                                { offset: 0, color: isActive ? '#06b6d4' : '#4f46e5' },
                                { offset: 1, color: isActive ? '#22d3ee' : '#818cf8' },
                            ],
                        },
                        borderRadius: [0, 4, 4, 0],
                        opacity: activeFilters.pendidikan && !isActive ? 0.35 : 1,
                    },
                };
            }),
            barMaxWidth: 20,
            label: {
                show: true,
                position: 'right',
                color: '#e2e8f0',
                fontSize: 11,
                fontWeight: 600,
                formatter: (p) => formatNumber(p.value),
            },
        }],
    };
});

function onEducationClick(params) {
    if (params.name) toggleFilter('pendidikan', params.name);
}

// ── 3b. Usia Pie (Donut) — clickable slices ───────────────────────
const USIA_PALETTE = [TEAL, AMBER, ROSE, INDIGO, VIOLET];

const usiaPieOption = computed(() => {
    const vp = props.victimProfiling?.usia || { labels: [], data: [] };
    const pieData = vp.labels.map((label, i) => ({
        name: label,
        value: vp.data[i] || 0,
        itemStyle: {
            color: USIA_PALETTE[i % USIA_PALETTE.length],
            opacity: activeFilters.usia_group && activeFilters.usia_group !== label ? 0.3 : 1,
        },
    }));

    return {
        tooltip: {
            ...DARK_TOOLTIP,
            trigger: 'item',
            formatter: (p) => `<span style="color:#f59e0b;font-weight:600">${p.name}</span><br/>Jumlah: <b>${formatNumber(p.value)}</b> (${p.percent}%)`,
        },
        legend: {
            bottom: 0,
            textStyle: { color: '#94a3b8', fontSize: 11 },
            icon: 'circle',
            itemWidth: 10,
        },
        series: [{
            type: 'pie',
            radius: ['42%', '72%'],
            center: ['50%', '45%'],
            avoidLabelOverlap: true,
            itemStyle: { borderRadius: 6, borderColor: '#0f172a', borderWidth: 3 },
            label: {
                show: true,
                formatter: '{d}%',
                fontSize: 13,
                fontWeight: 700,
                color: '#e2e8f0',
            },
            emphasis: {
                scale: true,
                scaleSize: 6,
                label: { fontSize: 15 },
            },
            data: pieData,
        }],
    };
});

function onUsiaClick(params) {
    if (params.data?.name) toggleFilter('usia_group', params.data.name);
}

// ── 4. Categories Horizontal Bar — clickable, highlights active ────
const categoriesBarOption = computed(() => {
    const raw = props.categoriesBar || [];
    const sorted = [...raw].sort((a, b) => a.total_reports - b.total_reports);

    return {
        tooltip: {
            ...DARK_TOOLTIP,
            trigger: 'axis',
            axisPointer: { type: 'shadow' },
            formatter: (params) => {
                const p = params[0];
                const item = sorted.find(c => c.nama === p.name);
                return `<div style="font-weight:700;margin-bottom:4px;color:#22d3ee">${p.name}</div>
                    <div>Laporan: <b>${formatNumber(p.value)}</b></div>
                    <div>Kerugian: <b>${formatRupiah(item?.total_losses || 0)}</b></div>`;
            },
        },
        grid: { top: 8, right: 60, bottom: 8, left: 8, containLabel: true },
        xAxis: {
            type: 'value',
            axisLabel: { color: '#64748b', fontSize: 10 },
            splitLine: { lineStyle: { color: '#1e293b', type: 'dashed' } },
            axisLine: { show: false },
        },
        yAxis: {
            type: 'category',
            data: sorted.map(c => c.nama),
            axisLabel: {
                color: '#cbd5e1',
                fontSize: 11,
                width: 180,
                overflow: 'truncate',
            },
            axisTick: { show: false },
            axisLine: { show: false },
        },
        series: [{
            type: 'bar',
            data: sorted.map((c) => {
                const isActive = c.active;
                const hasFilter = activeFilters.kategori_id !== null;
                return {
                    value: c.total_reports,
                    _id: c.id,
                    itemStyle: {
                        color: isActive
                            ? { type: 'linear', x: 0, y: 0, x2: 1, y2: 0, colorStops: [{ offset: 0, color: '#06b6d4' }, { offset: 1, color: '#22d3ee' }] }
                            : { type: 'linear', x: 0, y: 0, x2: 1, y2: 0, colorStops: [{ offset: 0, color: '#1e3a5f' }, { offset: 1, color: '#2563eb' }] },
                        borderRadius: [0, 4, 4, 0],
                        opacity: hasFilter && !isActive ? 0.3 : 1,
                    },
                };
            }),
            barMaxWidth: 22,
            label: {
                show: true,
                position: 'right',
                color: '#e2e8f0',
                fontSize: 11,
                fontWeight: 600,
                formatter: (p) => formatNumber(p.value),
            },
        }],
    };
});

function onCategoryClick(params) {
    const idx = params.dataIndex;
    const sorted = [...(props.categoriesBar || [])].sort((a, b) => a.total_reports - b.total_reports);
    const item = sorted[idx];
    if (item?.id) toggleFilter('kategori_id', item.id);
}

// ── 5. Platform Horizontal Bar ─────────────────────────────────────
const platformBarOption = computed(() => {
    const raw = props.platformSunburst || [];
    const platformMap = {};
    raw.forEach(jenis => {
        (jenis.platforms || []).forEach(p => {
            if (!platformMap[p.platform]) platformMap[p.platform] = 0;
            platformMap[p.platform] += p.total;
        });
    });

    const entries = Object.entries(platformMap)
        .sort((a, b) => a[1] - b[1])
        .slice(-12);

    return {
        tooltip: {
            ...DARK_TOOLTIP,
            trigger: 'axis',
            axisPointer: { type: 'shadow' },
        },
        grid: { top: 8, right: 50, bottom: 8, left: 8, containLabel: true },
        xAxis: {
            type: 'value',
            axisLabel: { color: '#64748b', fontSize: 10 },
            splitLine: { lineStyle: { color: '#1e293b', type: 'dashed' } },
            axisLine: { show: false },
        },
        yAxis: {
            type: 'category',
            data: entries.map(e => e[0]),
            axisLabel: { color: '#cbd5e1', fontSize: 11, width: 100, overflow: 'truncate' },
            axisTick: { show: false },
            axisLine: { show: false },
        },
        series: [{
            type: 'bar',
            data: entries.map((e) => ({
                value: e[1],
                itemStyle: {
                    color: {
                        type: 'linear', x: 0, y: 0, x2: 1, y2: 0,
                        colorStops: [
                            { offset: 0, color: '#b45309' },
                            { offset: 1, color: '#f59e0b' },
                        ],
                    },
                    borderRadius: [0, 4, 4, 0],
                },
            })),
            barMaxWidth: 18,
            label: {
                show: true,
                position: 'right',
                color: '#e2e8f0',
                fontSize: 10,
                fontWeight: 600,
            },
        }],
    };
});

// ── Active filter badges ───────────────────────────────────────────
const activeFilterBadges = computed(() => {
    const badges = [];
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
        badges.push({ key: 'usia_group', label: 'Usia ' + activeFilters.usia_group, color: 'amber' });
    }
    if (activeFilters.tahun) {
        badges.push({ key: 'tahun', label: 'Tahun ' + activeFilters.tahun, color: 'violet' });
    }
    return badges;
});

const badgeColors = {
    cyan: 'bg-cyan-500/15 border-cyan-500/30 text-cyan-300',
    emerald: 'bg-emerald-500/15 border-emerald-500/30 text-emerald-300',
    pink: 'bg-pink-500/15 border-pink-500/30 text-pink-300',
    indigo: 'bg-indigo-500/15 border-indigo-500/30 text-indigo-300',
    amber: 'bg-amber-500/15 border-amber-500/30 text-amber-300',
    violet: 'bg-violet-500/15 border-violet-500/30 text-violet-300',
};
</script>

<template>
    <Head title="Executive Dashboard" />

    <PimpinanLayout title="Executive Dashboard">

        <!-- ═══════════════════════════════════════════════════════════
             RIGHT FILTER DRAWER — Backdrop
             ═══════════════════════════════════════════════════════════ -->
        <Transition
            enter-active-class="transition-opacity duration-300"
            leave-active-class="transition-opacity duration-300"
            enter-from-class="opacity-0"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isFilterDrawerOpen"
                class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm"
                @click="isFilterDrawerOpen = false"
            />
        </Transition>

        <!-- ═══════════════════════════════════════════════════════════
             RIGHT FILTER DRAWER — Panel
             ═══════════════════════════════════════════════════════════ -->
        <aside
            class="fixed inset-y-0 right-0 z-50 w-80 bg-slate-900 border-l border-cyan-500/20 shadow-2xl transform transition-transform duration-300 flex flex-col"
            :class="isFilterDrawerOpen ? 'translate-x-0' : 'translate-x-full'"
        >
            <!-- Drawer Header -->
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-700/50 flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-cyan-500/10 rounded-lg border border-cyan-500/20">
                        <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-white uppercase tracking-wide">Cross-Filters</h2>
                        <p class="text-[11px] text-slate-500">Klik item untuk toggle</p>
                    </div>
                </div>
                <button
                    @click="isFilterDrawerOpen = false"
                    class="p-1.5 text-slate-400 hover:text-white hover:bg-slate-700/60 rounded-lg transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Drawer Body (scrollable) -->
            <div class="flex-1 overflow-y-auto px-5 py-4 space-y-5 drawer-scroll">

                <!-- Active filter badges -->
                <div v-if="hasAnyFilter" class="space-y-3">
                    <div class="flex flex-wrap gap-1.5">
                        <span
                            v-for="badge in activeFilterBadges"
                            :key="badge.key"
                            class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-medium rounded-md border cursor-pointer transition-all hover:opacity-80"
                            :class="badgeColors[badge.color]"
                            @click="toggleFilter(badge.key, activeFilters[badge.key])"
                        >
                            {{ badge.label }}
                            <svg class="w-3 h-3 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        </span>
                    </div>
                    <button
                        @click="clearAllFilters"
                        class="w-full flex items-center justify-center gap-2 px-3 py-2 bg-red-500/10 hover:bg-red-500/20 border border-red-500/25 rounded-lg text-xs text-red-300 font-semibold transition-all"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Hapus Semua Filter
                    </button>
                </div>

                <!-- Tahun -->
                <div>
                    <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider mb-2.5">Tahun</h3>
                    <div class="space-y-1.5">
                        <button
                            v-for="yr in (filterOptions?.tahun || [])"
                            :key="yr"
                            @click="toggleFilter('tahun', yr)"
                            class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-all text-left"
                            :class="activeFilters.tahun === yr
                                ? 'bg-violet-500/15 border border-violet-500/30 text-violet-200'
                                : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200 border border-transparent'"
                        >
                            <span
                                class="w-4 h-4 rounded border-2 flex items-center justify-center flex-shrink-0 transition-all"
                                :class="activeFilters.tahun === yr ? 'border-violet-400 bg-violet-500/20' : 'border-slate-600'"
                            >
                                <svg v-if="activeFilters.tahun === yr" class="w-2.5 h-2.5 text-violet-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </span>
                            {{ yr }}
                        </button>
                    </div>
                </div>

                <!-- Gender -->
                <div>
                    <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider mb-2.5">Jenis Kelamin</h3>
                    <div class="space-y-1.5">
                        <button
                            v-for="g in (filterOptions?.gender || [])"
                            :key="g"
                            @click="toggleFilter('gender', g)"
                            class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-all text-left"
                            :class="activeFilters.gender === g
                                ? 'bg-cyan-500/15 border border-cyan-500/30 text-cyan-200'
                                : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200 border border-transparent'"
                        >
                            <span
                                class="w-4 h-4 rounded border-2 flex items-center justify-center flex-shrink-0 transition-all"
                                :class="activeFilters.gender === g ? 'border-cyan-400 bg-cyan-500/20' : 'border-slate-600'"
                            >
                                <svg v-if="activeFilters.gender === g" class="w-2.5 h-2.5 text-cyan-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </span>
                            {{ g }}
                        </button>
                    </div>
                </div>

                <!-- Pendidikan -->
                <div>
                    <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider mb-2.5">Pendidikan</h3>
                    <div class="space-y-1 max-h-52 overflow-y-auto scrollbar-thin">
                        <button
                            v-for="edu in (filterOptions?.pendidikan || [])"
                            :key="edu"
                            @click="toggleFilter('pendidikan', edu)"
                            class="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-lg text-sm font-medium transition-all text-left"
                            :class="activeFilters.pendidikan === edu
                                ? 'bg-indigo-500/15 border border-indigo-500/30 text-indigo-200'
                                : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200 border border-transparent'"
                        >
                            <span
                                class="w-4 h-4 rounded border-2 flex items-center justify-center flex-shrink-0 transition-all"
                                :class="activeFilters.pendidikan === edu ? 'border-indigo-400 bg-indigo-500/20' : 'border-slate-600'"
                            >
                                <svg v-if="activeFilters.pendidikan === edu" class="w-2.5 h-2.5 text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </span>
                            <span class="truncate">{{ edu }}</span>
                        </button>
                    </div>
                </div>

                <!-- Usia Group -->
                <div>
                    <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider mb-2.5">Kelompok Usia</h3>
                    <div class="space-y-1.5">
                        <button
                            v-for="age in (filterOptions?.usia_group || [])"
                            :key="age"
                            @click="toggleFilter('usia_group', age)"
                            class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-all text-left"
                            :class="activeFilters.usia_group === age
                                ? 'bg-amber-500/15 border border-amber-500/30 text-amber-200'
                                : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200 border border-transparent'"
                        >
                            <span
                                class="w-4 h-4 rounded border-2 flex items-center justify-center flex-shrink-0 transition-all"
                                :class="activeFilters.usia_group === age ? 'border-amber-400 bg-amber-500/20' : 'border-slate-600'"
                            >
                                <svg v-if="activeFilters.usia_group === age" class="w-2.5 h-2.5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </span>
                            {{ age }} tahun
                        </button>
                    </div>
                </div>
            </div>
        </aside>

        <!-- ═══════════════════════════════════════════════════════════
             FULL-WIDTH MAIN CONTENT
             ═══════════════════════════════════════════════════════════ -->
        <div class="w-full space-y-5">

            <!-- ─── TOP BAR: Active Badges + Filter Button ────────── -->
            <div class="flex items-center justify-between gap-4">
                <!-- Active filter badges (inline) -->
                <div class="flex items-center gap-2 flex-wrap min-w-0">
                    <template v-if="hasAnyFilter">
                        <span
                            v-for="badge in activeFilterBadges"
                            :key="badge.key"
                            class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-medium rounded-md border cursor-pointer transition-all hover:opacity-80"
                            :class="badgeColors[badge.color]"
                            @click="toggleFilter(badge.key, activeFilters[badge.key])"
                        >
                            {{ badge.label }}
                            <svg class="w-3 h-3 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        </span>
                        <button
                            @click="clearAllFilters"
                            class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-medium rounded-md border cursor-pointer transition-all bg-red-500/10 border-red-500/25 text-red-300 hover:bg-red-500/20"
                        >
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            Reset
                        </button>
                    </template>
                    <span v-else class="text-xs text-slate-500">Klik chart atau buka filter untuk cross-filter data</span>
                </div>

                <!-- Year Selector + Filter Button -->
                <div class="flex items-center gap-2.5 flex-shrink-0">

                    <!-- Year Selector -->
                    <div class="relative">
                        <select
                            :value="activeFilters.tahun"
                            @change="activeFilters.tahun = $event.target.value ? Number($event.target.value) : null; navigateWithFilters()"
                            class="appearance-none cursor-pointer pl-9 pr-9 py-2.5 bg-slate-800 hover:bg-slate-700 border border-slate-600/50 hover:border-cyan-500/40 rounded-xl text-sm font-semibold text-slate-200 transition-all shadow-lg focus:outline-none focus:ring-2 focus:ring-cyan-500/40 focus:border-cyan-500/40"
                        >
                            <option :value="null">Semua Tahun</option>
                            <option v-for="year in (filterOptions?.tahun || [])" :key="year" :value="year">
                                Tahun {{ year }}
                            </option>
                        </select>
                        <!-- Calendar icon (left) -->
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <!-- Chevron icon (right) -->
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>

                    <!-- Filter Drawer Toggle Button -->
                    <button
                        @click="isFilterDrawerOpen = true"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-800 hover:bg-slate-700 border border-slate-600/50 hover:border-cyan-500/40 rounded-xl text-sm font-semibold text-slate-200 transition-all shadow-lg group"
                    >
                        <svg class="w-4 h-4 text-cyan-400 group-hover:text-cyan-300 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                        </svg>
                        Filters
                        <span
                            v-if="activeFilterCount > 0"
                            class="inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold rounded-full bg-cyan-500 text-slate-900"
                        >
                            {{ activeFilterCount }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- ─── ROW 1: KPI CARDS ──────────────────────────────── -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

                <!-- Total Pengaduan -->
                <div class="bg-slate-900 rounded-xl border border-slate-700/50 p-5 shadow-lg group hover:border-cyan-500/30 transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Total Pengaduan</span>
                        <div class="p-1.5 bg-cyan-500/10 rounded-lg">
                            <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>
                    <p class="text-3xl font-extrabold text-white tracking-tight">{{ formatNumber(keyStats?.total_laporan) }}</p>
                    <div class="mt-2 flex items-center gap-2">
                        <span class="text-xs text-emerald-400 font-semibold">{{ keyStats?.clearance_rate || 0 }}%</span>
                        <span class="text-xs text-slate-500">clearance rate</span>
                    </div>
                </div>

                <!-- Total Kerugian -->
                <div class="bg-slate-900 rounded-xl border border-slate-700/50 p-5 shadow-lg group hover:border-rose-500/30 transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Total Kerugian</span>
                        <div class="p-1.5 bg-rose-500/10 rounded-lg">
                            <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <p class="text-3xl font-extrabold text-white tracking-tight">{{ formatCompact(keyStats?.total_kerugian) }}</p>
                    <p class="text-xs text-slate-500 mt-2 truncate" :title="formatRupiah(keyStats?.total_kerugian)">{{ formatRupiah(keyStats?.total_kerugian) }}</p>
                </div>

                <!-- Kasus Selesai -->
                <div class="bg-slate-900 rounded-xl border border-slate-700/50 p-5 shadow-lg group hover:border-emerald-500/30 transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Kasus Selesai</span>
                        <div class="p-1.5 bg-emerald-500/10 rounded-lg">
                            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <p class="text-3xl font-extrabold text-white tracking-tight">{{ formatNumber(keyStats?.total_selesai) }}</p>
                    <div class="mt-2 w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
                        <div class="bg-emerald-500 h-full rounded-full transition-all duration-700" :style="{ width: Math.min(keyStats?.clearance_rate || 0, 100) + '%' }"></div>
                    </div>
                </div>
            </div>

            <!-- ─── ROW 2: Tren Pengaduan (Full Width Cinematic) ── -->
            <div class="bg-slate-900 rounded-xl border border-slate-700/50 shadow-lg overflow-hidden">
                <div class="px-5 pt-4 pb-2 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-white">Tren Pengaduan Per Bulan</h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">Klik titik data untuk filter bulan</p>
                    </div>
                    <div class="p-1.5 bg-cyan-500/10 rounded-lg border border-cyan-500/15">
                        <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                </div>
                <v-chart
                    class="w-full"
                    style="height: 340px"
                    :option="monthlyLineOption"
                    autoresize
                    @click="onMonthlyClick"
                />
            </div>

            <!-- ─── ROW 3: Profil Korban (3 charts side-by-side) ─── -->
            <div>
                <div class="flex items-center gap-2.5 mb-4">
                    <div class="p-1.5 bg-pink-500/10 rounded-lg border border-pink-500/15">
                        <svg class="w-4 h-4 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-white uppercase tracking-wide">Profil Korban</h2>
                        <p class="text-[10px] text-slate-500 font-mono tracking-wider">DEMOGRAFI · KLIK CHART UNTUK FILTER</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

                    <!-- Gender Donut -->
                    <div class="bg-slate-900 rounded-xl border border-slate-700/50 shadow-lg overflow-hidden">
                        <div class="px-4 pt-4 pb-1 flex items-center justify-between">
                            <h3 class="text-sm font-bold text-white">Jenis Kelamin</h3>
                            <span class="text-[10px] text-slate-500 uppercase tracking-wider">Klik untuk filter</span>
                        </div>
                        <v-chart
                            class="w-full"
                            style="height: 260px"
                            :option="genderPieOption"
                            autoresize
                            @click="onGenderClick"
                        />
                    </div>

                    <!-- Usia Donut (NEW) -->
                    <div class="bg-slate-900 rounded-xl border border-slate-700/50 shadow-lg overflow-hidden">
                        <div class="px-4 pt-4 pb-1 flex items-center justify-between">
                            <h3 class="text-sm font-bold text-white">Kelompok Usia</h3>
                            <span class="text-[10px] text-slate-500 uppercase tracking-wider">Klik untuk filter</span>
                        </div>
                        <v-chart
                            class="w-full"
                            style="height: 260px"
                            :option="usiaPieOption"
                            autoresize
                            @click="onUsiaClick"
                        />
                    </div>

                    <!-- Education Bar -->
                    <div class="bg-slate-900 rounded-xl border border-slate-700/50 shadow-lg overflow-hidden">
                        <div class="px-4 pt-4 pb-1 flex items-center justify-between">
                            <h3 class="text-sm font-bold text-white">Tingkat Pendidikan</h3>
                            <span class="text-[10px] text-slate-500 uppercase tracking-wider">Klik untuk filter</span>
                        </div>
                        <v-chart
                            class="w-full"
                            style="height: 260px"
                            :option="educationBarOption"
                            autoresize
                            @click="onEducationClick"
                        />
                    </div>
                </div>
            </div>

            <!-- ─── ROW 4: Categories Horizontal Bar ──────────────── -->
            <div class="bg-slate-900 rounded-xl border border-slate-700/50 shadow-lg overflow-hidden">
                <div class="px-5 pt-4 pb-2 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-white">Perbandingan Jenis Aduan</h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">Klik bar untuk filter kategori kejahatan</p>
                    </div>
                    <span class="text-[10px] text-slate-500 font-mono uppercase tracking-wider">
                        {{ (categoriesBar || []).length }} kategori
                    </span>
                </div>
                <v-chart
                    class="w-full"
                    :style="{ height: Math.max(280, (categoriesBar || []).length * 32 + 40) + 'px' }"
                    :option="categoriesBarOption"
                    autoresize
                    @click="onCategoryClick"
                />
            </div>

            <!-- ─── ROW 5: Platform Bar ───────────────────────── -->
            <div class="bg-slate-900 rounded-xl border border-slate-700/50 shadow-lg overflow-hidden">
                <div class="px-5 pt-4 pb-2 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-white">Platform / Vektor Serangan</h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">Top platform identitas tersangka</p>
                    </div>
                    <div class="p-1.5 bg-amber-500/10 rounded-lg border border-amber-500/15">
                        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4" />
                        </svg>
                    </div>
                </div>
                <v-chart
                    class="w-full"
                    style="height: 360px"
                    :option="platformBarOption"
                    autoresize
                />
            </div>

        </div>
    </PimpinanLayout>
</template>

<style scoped>
/* Drawer scrollbar */
.drawer-scroll::-webkit-scrollbar,
.scrollbar-thin::-webkit-scrollbar {
    width: 4px;
}
.drawer-scroll::-webkit-scrollbar-track,
.scrollbar-thin::-webkit-scrollbar-track {
    background: transparent;
}
.drawer-scroll::-webkit-scrollbar-thumb,
.scrollbar-thin::-webkit-scrollbar-thumb {
    background: #334155;
    border-radius: 4px;
}
.drawer-scroll::-webkit-scrollbar-thumb:hover,
.scrollbar-thin::-webkit-scrollbar-thumb:hover {
    background: #475569;
}

</style>
