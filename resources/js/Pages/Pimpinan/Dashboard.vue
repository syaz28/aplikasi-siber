<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import PimpinanLayout from '@/Layouts/PimpinanLayout.vue';
import VueApexCharts from 'vue3-apexcharts';

const page = usePage();
const user = computed(() => page.props.auth?.user);

// Props from controller
const props = defineProps({
    stats: Object,
    chartGender: Object,
    chartPendidikan: Object,
    chartUsia: Object,
    chartKategori: Object,
    tersangkaStats: Object,
    pelaporKorbanComparison: Object,
    filterOptions: Object,
    appliedFilters: Object,
});

// =============================================
// STATE
// =============================================
const showFilterModal = ref(false);
const isLoading = ref(false);

// Local filter state
const localFilters = ref({
    date_range: {
        start: props.appliedFilters?.date_range?.start || '',
        end: props.appliedFilters?.date_range?.end || '',
    },
    filters: {
        pendidikan: props.appliedFilters?.filters?.pendidikan || [],
        gender: props.appliedFilters?.filters?.gender || [],
        age_group: props.appliedFilters?.filters?.age_group || [],
    },
    kategori_fokus: props.appliedFilters?.kategori_fokus || '',
});

// =============================================
// HELPERS
// =============================================
const formatRupiah = (amount) => {
    if (!amount) return 'Rp 0';
    return 'Rp ' + parseInt(amount).toLocaleString('id-ID');
};

const formatNumber = (num) => {
    if (!num) return '0';
    return parseInt(num).toLocaleString('id-ID');
};

const getGenderLabel = (code) => {
    return code === 'LAKI-LAKI' ? 'LAKI-LAKI' : 'PEREMPUAN';
};

const getAgeGroupLabel = (code) => {
    const labels = {
        '< 17': '< 17 Th (Anak-anak)',
        '17-25': '17-25 Th (Remaja)',
        '26-45': '26-45 Th (Dewasa)',
        '46-60': '46-60 Th (Orang Tua)',
        '> 60': '> 60 Th (Lansia)',
    };
    return labels[code] || code;
};

const getKategoriName = (id) => {
    const kat = props.filterOptions?.kategori?.find(k => k.id == id);
    return kat?.nama || '';
};

// =============================================
// QUICK DATE CHIPS
// =============================================
const quickDateOptions = [
    { label: '7 Hari Terakhir', key: '7days' },
    { label: 'Bulan Ini', key: 'month' },
    { label: 'Tahun Ini', key: 'year' },
];

const activeQuickDate = computed(() => {
    const start = localFilters.value.date_range.start;
    const end = localFilters.value.date_range.end;
    if (!start || !end) return null;
    
    const today = new Date();
    const startDate = new Date(start);
    const endDate = new Date(end);
    
    // Check if matches any quick date
    const diffDays = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));
    
    // 7 days check
    const sevenDaysAgo = new Date(today);
    sevenDaysAgo.setDate(today.getDate() - 6);
    if (start === formatDate(sevenDaysAgo) && end === formatDate(today)) return '7days';
    
    // Month check
    const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    if (start === formatDate(startOfMonth) && end === formatDate(today)) return 'month';
    
    // Year check
    const startOfYear = new Date(today.getFullYear(), 0, 1);
    if (start === formatDate(startOfYear) && end === formatDate(today)) return 'year';
    
    return null;
});

const formatDate = (date) => {
    return date.toISOString().split('T')[0];
};

const applyQuickDate = (key) => {
    const today = new Date();
    let start, end;
    
    switch (key) {
        case '7days':
            start = new Date(today);
            start.setDate(today.getDate() - 6);
            end = today;
            break;
        case 'month':
            start = new Date(today.getFullYear(), today.getMonth(), 1);
            end = today;
            break;
        case 'year':
            start = new Date(today.getFullYear(), 0, 1);
            end = today;
            break;
        default:
            return;
    }
    
    localFilters.value.date_range.start = formatDate(start);
    localFilters.value.date_range.end = formatDate(end);
    applyFilters();
};

const clearQuickDate = () => {
    localFilters.value.date_range.start = '';
    localFilters.value.date_range.end = '';
    applyFilters();
};

// =============================================
// ACTIVE FILTER BADGES
// =============================================
const activeFilterBadges = computed(() => {
    const badges = [];
    
    // Gender badges
    localFilters.value.filters.gender.forEach(g => {
        badges.push({
            type: 'gender',
            value: g,
            label: getGenderLabel(g),
            color: 'bg-pink-100 text-pink-800',
        });
    });
    
    // Age group badges
    localFilters.value.filters.age_group.forEach(a => {
        badges.push({
            type: 'age_group',
            value: a,
            label: getAgeGroupLabel(a),
            color: 'bg-green-100 text-green-800',
        });
    });
    
    // Pendidikan badges
    localFilters.value.filters.pendidikan.forEach(p => {
        badges.push({
            type: 'pendidikan',
            value: p,
            label: p,
            color: 'bg-indigo-100 text-indigo-800',
        });
    });
    
    return badges;
});

const removeFilterBadge = (badge) => {
    const arr = localFilters.value.filters[badge.type];
    const index = arr.indexOf(badge.value);
    if (index > -1) {
        arr.splice(index, 1);
        applyFilters();
    }
};

// =============================================
// FILTER COUNT FOR MOBILE
// =============================================
const activeFilterCount = computed(() => {
    let count = 0;
    if (localFilters.value.date_range.start && localFilters.value.date_range.end) count++;
    count += localFilters.value.filters.gender.length;
    count += localFilters.value.filters.age_group.length;
    count += localFilters.value.filters.pendidikan.length;
    // Don't count kategori_fokus as it's now separate
    return count;
});

// =============================================
// FILTER ACTIONS
// =============================================
const applyFilters = () => {
    isLoading.value = true;
    showFilterModal.value = false;
    
    router.get('/pimpinan/dashboard', {
        date_range: localFilters.value.date_range,
        filters: localFilters.value.filters,
        kategori_fokus: localFilters.value.kategori_fokus,
    }, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            isLoading.value = false;
        }
    });
};

const resetFilters = () => {
    localFilters.value = {
        date_range: { start: '', end: '' },
        filters: { pendidikan: [], gender: [], age_group: [] },
        kategori_fokus: '',
    };
    applyFilters();
};

const hasActiveFilters = computed(() => {
    return localFilters.value.date_range.start ||
           localFilters.value.date_range.end ||
           localFilters.value.filters.pendidikan.length > 0 ||
           localFilters.value.filters.gender.length > 0 ||
           localFilters.value.filters.age_group.length > 0 ||
           localFilters.value.kategori_fokus;
});

// Toggle checkbox in filter arrays
const toggleFilter = (type, value) => {
    const arr = localFilters.value.filters[type];
    const index = arr.indexOf(value);
    if (index > -1) {
        arr.splice(index, 1);
    } else {
        arr.push(value);
    }
};

// Watch kategori_fokus for immediate apply
watch(() => localFilters.value.kategori_fokus, (newVal, oldVal) => {
    if (newVal !== oldVal && oldVal !== undefined) {
        applyFilters();
    }
});

// =============================================
// CHART CONFIGURATIONS
// =============================================

// Professional Color Palette
const colors = {
    primary: '#3B82F6',    // Blue
    secondary: '#6366F1',  // Indigo
    success: '#10B981',    // Green
    warning: '#F59E0B',    // Amber
    danger: '#EF4444',     // Red
    purple: '#8B5CF6',     // Purple
    pink: '#EC4899',       // Pink
    cyan: '#06B6D4',       // Cyan
};

// Horizontal Bar Chart for Categories (MAIN CHART)
const kategoriChartHeight = computed(() => {
    const count = props.chartKategori?.labels?.length || 0;
    return Math.max(400, count * 40);
});

const kategoriChartOptions = computed(() => ({
    chart: {
        type: 'bar',
        height: kategoriChartHeight.value,
        toolbar: { show: false },
        fontFamily: 'Inter, sans-serif',
    },
    colors: ['#3B82F6'],
    plotOptions: {
        bar: {
            horizontal: true,
            borderRadius: 4,
            barHeight: '70%',
            distributed: false,
        }
    },
    dataLabels: {
        enabled: true,
        style: {
            fontSize: '11px',
            fontWeight: 600,
            colors: ['#fff']
        },
        offsetX: -6,
    },
    xaxis: {
        categories: props.chartKategori?.labels || [],
        labels: {
            style: { colors: '#6B7280', fontSize: '11px' }
        },
    },
    yaxis: {
        labels: {
            style: { colors: '#374151', fontSize: '12px' },
            maxWidth: 200,
        },
    },
    grid: {
        borderColor: '#E5E7EB',
        strokeDashArray: 4,
        xaxis: { lines: { show: true } },
        yaxis: { lines: { show: false } },
    },
    tooltip: {
        y: { formatter: (val) => `${val} Laporan` }
    },
}));

const kategoriChartSeries = computed(() => [{
    name: 'Laporan',
    data: props.chartKategori?.data || []
}]);

// Pie Chart for Gender
const genderChartOptions = computed(() => ({
    chart: {
        type: 'pie',
        height: 280,
        fontFamily: 'Inter, sans-serif',
    },
    colors: ['#3B82F6', '#EC4899'],
    labels: props.chartGender?.labels || [],
    legend: {
        position: 'bottom',
        fontSize: '12px',
        markers: { width: 10, height: 10, radius: 3 },
    },
    dataLabels: {
        enabled: true,
        style: { fontSize: '12px', fontWeight: 600 },
        formatter: (val) => `${val.toFixed(1)}%`,
    },
    tooltip: {
        y: { formatter: (val) => `${val} Laporan` }
    },
}));

const genderChartSeries = computed(() => props.chartGender?.data || []);

// Bar Chart for Age Groups
const usiaChartOptions = computed(() => ({
    chart: {
        type: 'bar',
        height: 280,
        toolbar: { show: false },
        fontFamily: 'Inter, sans-serif',
    },
    colors: ['#10B981'],
    plotOptions: {
        bar: {
            borderRadius: 6,
            columnWidth: '60%',
        }
    },
    dataLabels: {
        enabled: true,
        style: { fontSize: '11px', fontWeight: 600, colors: ['#fff'] },
        offsetY: -2,
    },
    xaxis: {
        categories: props.chartUsia?.labels || [],
        labels: {
            style: { colors: '#6B7280', fontSize: '11px' }
        },
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: {
        labels: {
            style: { colors: '#6B7280', fontSize: '11px' }
        }
    },
    grid: {
        borderColor: '#E5E7EB',
        strokeDashArray: 4,
    },
    tooltip: {
        y: { formatter: (val) => `${val} Laporan` }
    },
}));

const usiaChartSeries = computed(() => [{
    name: 'Laporan',
    data: props.chartUsia?.data || []
}]);

// Bar Chart for Education
const pendidikanChartOptions = computed(() => ({
    chart: {
        type: 'bar',
        height: 280,
        toolbar: { show: false },
        fontFamily: 'Inter, sans-serif',
    },
    colors: ['#6366F1'],
    plotOptions: {
        bar: {
            borderRadius: 6,
            columnWidth: '60%',
        }
    },
    dataLabels: {
        enabled: true,
        style: { fontSize: '11px', fontWeight: 600, colors: ['#fff'] },
        offsetY: -2,
    },
    xaxis: {
        categories: props.chartPendidikan?.labels || [],
        labels: {
            style: { colors: '#6B7280', fontSize: '11px' },
            rotate: -45,
            rotateAlways: false,
        },
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: {
        labels: {
            style: { colors: '#6B7280', fontSize: '11px' }
        }
    },
    grid: {
        borderColor: '#E5E7EB',
        strokeDashArray: 4,
    },
    tooltip: {
        y: { formatter: (val) => `${val} Laporan` }
    },
}));

const pendidikanChartSeries = computed(() => [{
    name: 'Laporan',
    data: props.chartPendidikan?.data || []
}]);

// =============================================
// TERSANGKA CHARTS
// =============================================

// Donut Chart for Tersangka Identification Status
const tersangkaStatusChartOptions = computed(() => ({
    chart: {
        type: 'donut',
        height: 200,
        fontFamily: 'Inter, sans-serif',
    },
    colors: ['#10B981', '#F59E0B'],
    labels: ['Teridentifikasi', 'Belum Teridentifikasi'],
    legend: {
        position: 'bottom',
        fontSize: '11px',
        markers: { width: 8, height: 8, radius: 2 },
    },
    dataLabels: {
        enabled: true,
        style: { fontSize: '11px', fontWeight: 600 },
        formatter: (val) => `${val.toFixed(0)}%`,
    },
    plotOptions: {
        pie: {
            donut: {
                size: '65%',
                labels: {
                    show: true,
                    name: { fontSize: '12px' },
                    value: { fontSize: '18px', fontWeight: 700 },
                    total: {
                        show: true,
                        label: 'Total',
                        fontSize: '11px',
                        formatter: () => props.tersangkaStats?.total || 0,
                    }
                }
            }
        }
    },
}));

const tersangkaStatusChartSeries = computed(() => [
    props.tersangkaStats?.identified || 0,
    props.tersangkaStats?.unidentified || 0,
]);

// Horizontal Bar Chart for Identity Types
const identityTypeChartOptions = computed(() => ({
    chart: {
        type: 'bar',
        height: 200,
        toolbar: { show: false },
        fontFamily: 'Inter, sans-serif',
    },
    colors: ['#8B5CF6'],
    plotOptions: {
        bar: {
            horizontal: true,
            borderRadius: 4,
            barHeight: '60%',
        }
    },
    dataLabels: {
        enabled: true,
        style: { fontSize: '10px', fontWeight: 600, colors: ['#fff'] },
        offsetX: -6,
    },
    xaxis: {
        categories: props.tersangkaStats?.identity_by_type?.labels || [],
        labels: { style: { fontSize: '10px' } },
    },
    yaxis: {
        labels: { style: { fontSize: '10px' }, maxWidth: 100 },
    },
    grid: {
        borderColor: '#E5E7EB',
        strokeDashArray: 4,
    },
    tooltip: {
        y: { formatter: (val) => `${val} Identitas` }
    },
}));

const identityTypeChartSeries = computed(() => [{
    name: 'Identitas',
    data: props.tersangkaStats?.identity_by_type?.data || []
}]);

// Grouped Bar Chart for Gender Comparison (Pelapor vs Korban vs Tersangka)
const genderComparisonChartOptions = computed(() => ({
    chart: {
        type: 'bar',
        height: 280,
        toolbar: { show: false },
        fontFamily: 'Inter, sans-serif',
    },
    colors: ['#3B82F6', '#EF4444', '#8B5CF6'],
    plotOptions: {
        bar: {
            horizontal: false,
            columnWidth: '60%',
            borderRadius: 4,
        }
    },
    dataLabels: {
        enabled: true,
        style: { fontSize: '10px', fontWeight: 600, colors: ['#fff'] },
    },
    xaxis: {
        categories: props.pelaporKorbanComparison?.gender_comparison?.labels || [],
        labels: { style: { fontSize: '11px' } },
    },
    yaxis: {
        labels: { style: { fontSize: '11px' } },
    },
    legend: {
        position: 'top',
        horizontalAlign: 'center',
        fontSize: '11px',
    },
    grid: {
        borderColor: '#E5E7EB',
        strokeDashArray: 4,
    },
    tooltip: {
        y: { formatter: (val) => `${val} Orang` }
    },
}));

const genderComparisonChartSeries = computed(() => [
    { name: 'Pelapor', data: props.pelaporKorbanComparison?.gender_comparison?.pelapor || [] },
    { name: 'Korban', data: props.pelaporKorbanComparison?.gender_comparison?.korban || [] },
    { name: 'Tersangka', data: props.pelaporKorbanComparison?.gender_comparison?.tersangka || [] },
]);

// Horizontal Bar Chart for Top Pekerjaan Pelapor
const pekerjaanChartOptions = computed(() => ({
    chart: {
        type: 'bar',
        height: 200,
        toolbar: { show: false },
        fontFamily: 'Inter, sans-serif',
    },
    colors: ['#06B6D4'],
    plotOptions: {
        bar: {
            horizontal: true,
            borderRadius: 4,
            barHeight: '60%',
        }
    },
    dataLabels: {
        enabled: true,
        style: { fontSize: '10px', fontWeight: 600, colors: ['#fff'] },
        offsetX: -6,
    },
    xaxis: {
        categories: props.pelaporKorbanComparison?.top_pekerjaan_pelapor?.labels || [],
        labels: { style: { fontSize: '10px' } },
    },
    yaxis: {
        labels: { style: { fontSize: '10px' }, maxWidth: 120 },
    },
    grid: {
        borderColor: '#E5E7EB',
        strokeDashArray: 4,
    },
    tooltip: {
        y: { formatter: (val) => `${val} Pelapor` }
    },
}));

const pekerjaanChartSeries = computed(() => [{
    name: 'Pelapor',
    data: props.pelaporKorbanComparison?.top_pekerjaan_pelapor?.data || []
}]);
</script>

<template>
    <Head title="Executive Dashboard - Profiling & Demographics" />

    <PimpinanLayout title="Executive Dashboard">
        <div class="space-y-6">
            
            <!-- Loading Overlay -->
            <div v-if="isLoading" class="fixed inset-0 z-50 bg-white/80 flex items-center justify-center">
                <div class="flex flex-col items-center gap-3">
                    <div class="animate-spin rounded-full h-12 w-12 border-4 border-blue-500 border-t-transparent"></div>
                    <span class="text-gray-600 font-medium">Memuat data...</span>
                </div>
            </div>

            <!-- Header -->
            <div class="bg-gradient-to-r from-purple-700 via-purple-800 to-indigo-900 rounded-2xl p-6 text-white shadow-xl">
                <div class="flex flex-col gap-4">
                    <!-- Top Row: Title + Kategori Dropdown + Filter Button -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h1 class="text-xl md:text-2xl font-bold">Executive Dashboard</h1>
                            <p class="text-purple-200 text-sm mt-1">Profiling & Demographics Analysis</p>
                        </div>
                        
                        <div class="flex items-center gap-3">
                            <!-- Kategori Fokus Dropdown (Prominent Placement) -->
                            <div class="relative flex-1 sm:flex-none sm:min-w-[200px]">
                                <select
                                    v-model="localFilters.kategori_fokus"
                                    class="w-full appearance-none pl-4 pr-10 py-2.5 bg-white/20 hover:bg-white/30 border border-white/30 rounded-xl text-sm text-white focus:ring-2 focus:ring-white/50 focus:border-transparent transition-all cursor-pointer"
                                >
                                    <option value="" class="text-gray-900">🎯 Semua Kategori</option>
                                    <option v-for="kat in filterOptions.kategori" :key="kat.id" :value="kat.id" class="text-gray-900">
                                        {{ kat.nama }}
                                    </option>
                                </select>
                                <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none">
                                    <svg class="w-4 h-4 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>
                            
                            <!-- Filter Button (Mobile & Desktop) -->
                            <button
                                @click="showFilterModal = true"
                                class="lg:hidden inline-flex items-center gap-2 px-4 py-2.5 bg-white/20 hover:bg-white/30 rounded-xl transition-colors whitespace-nowrap"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                                <span>Filter</span>
                                <span 
                                    v-if="activeFilterCount > 0" 
                                    class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold bg-yellow-400 text-purple-900 rounded-full"
                                >
                                    {{ activeFilterCount }}
                                </span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Active Filter Badges Row -->
                    <div v-if="activeFilterBadges.length > 0 || localFilters.kategori_fokus" class="flex flex-wrap items-center gap-2">
                        <span class="text-purple-200 text-xs font-medium">Filter aktif:</span>
                        
                        <!-- Kategori Badge -->
                        <span 
                            v-if="localFilters.kategori_fokus"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-purple-100 text-purple-800 rounded-full text-xs font-medium"
                        >
                            <span>🎯 {{ getKategoriName(localFilters.kategori_fokus) }}</span>
                            <button 
                                @click="localFilters.kategori_fokus = ''; applyFilters()"
                                class="hover:bg-purple-200 rounded-full p-0.5 transition-colors"
                            >
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </span>
                        
                        <!-- Date Range Badge -->
                        <span 
                            v-if="localFilters.date_range.start && localFilters.date_range.end"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium"
                        >
                            <span>📅 {{ localFilters.date_range.start }} s/d {{ localFilters.date_range.end }}</span>
                            <button 
                                @click="clearQuickDate"
                                class="hover:bg-blue-200 rounded-full p-0.5 transition-colors"
                            >
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </span>
                        
                        <!-- Other Filter Badges -->
                        <span 
                            v-for="badge in activeFilterBadges" 
                            :key="`${badge.type}-${badge.value}`"
                            :class="[badge.color, 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium']"
                        >
                            <span>{{ badge.label }}</span>
                            <button 
                                @click="removeFilterBadge(badge)"
                                class="hover:bg-black/10 rounded-full p-0.5 transition-colors"
                            >
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </span>
                        
                        <!-- Clear All Button -->
                        <button 
                            v-if="hasActiveFilters"
                            @click="resetFilters"
                            class="text-xs text-purple-200 hover:text-white underline underline-offset-2 transition-colors"
                        >
                            Hapus semua
                        </button>
                    </div>
                </div>
            </div>

            <!-- Quick Date Chips (Horizontal Scroll) -->
            <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-hide">
                <span class="text-sm text-gray-500 font-medium whitespace-nowrap">Periode:</span>
                <div class="flex items-center gap-2">
                    <button
                        v-for="opt in quickDateOptions"
                        :key="opt.key"
                        @click="applyQuickDate(opt.key)"
                        :class="[
                            'px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-all',
                            activeQuickDate === opt.key
                                ? 'bg-purple-600 text-white shadow-md'
                                : 'bg-white text-gray-700 border border-gray-200 hover:border-purple-300 hover:bg-purple-50'
                        ]"
                    >
                        {{ opt.label }}
                    </button>
                    <button
                        v-if="activeQuickDate"
                        @click="clearQuickDate"
                        class="px-3 py-2 rounded-full text-sm font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-all"
                    >
                        ✕ Reset
                    </button>
                </div>
            </div>

            <!-- Main Grid Layout (Desktop: Sidebar + Content) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                
                <!-- =============================================
                     LEFT SIDEBAR: FILTERS (Desktop Only)
                     ============================================= -->
                <aside class="hidden lg:block lg:col-span-3 space-y-4">
                    
                    <!-- Filter Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 sticky top-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            Filter Data
                        </h3>

                        <!-- Date Range -->
                        <div class="mb-5">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Rentang Tanggal Kustom</label>
                            <div class="space-y-2">
                                <input
                                    v-model="localFilters.date_range.start"
                                    type="date"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                    placeholder="Dari"
                                />
                                <input
                                    v-model="localFilters.date_range.end"
                                    type="date"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                    placeholder="Sampai"
                                />
                            </div>
                        </div>

                        <!-- Gender Filter -->
                        <div class="mb-5">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Jenis Kelamin</label>
                            <div class="space-y-2">
                                <label
                                    v-for="gender in filterOptions.gender"
                                    :key="gender"
                                    class="flex items-center gap-2 cursor-pointer"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="localFilters.filters.gender.includes(gender)"
                                        @change="toggleFilter('gender', gender)"
                                        class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500"
                                    />
                                    <span class="text-sm text-gray-700">{{ getGenderLabel(gender) }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Age Group Filter -->
                        <div class="mb-5">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Kelompok Usia</label>
                            <div class="space-y-2">
                                <label
                                    v-for="age in filterOptions.age_group"
                                    :key="age"
                                    class="flex items-center gap-2 cursor-pointer"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="localFilters.filters.age_group.includes(age)"
                                        @change="toggleFilter('age_group', age)"
                                        class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500"
                                    />
                                    <span class="text-sm text-gray-700">{{ getAgeGroupLabel(age) }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Education Filter -->
                        <div class="mb-5">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Pendidikan</label>
                            <div class="space-y-2 max-h-40 overflow-y-auto">
                                <label
                                    v-for="pend in filterOptions.pendidikan"
                                    :key="pend"
                                    class="flex items-center gap-2 cursor-pointer"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="localFilters.filters.pendidikan.includes(pend)"
                                        @change="toggleFilter('pendidikan', pend)"
                                        class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500"
                                    />
                                    <span class="text-sm text-gray-700">{{ pend }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-2">
                            <button
                                @click="applyFilters"
                                class="flex-1 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition-colors"
                            >
                                Terapkan
                            </button>
                            <button
                                @click="resetFilters"
                                class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition-colors"
                            >
                                Reset
                            </button>
                        </div>
                    </div>
                </aside>

                <!-- =============================================
                     MAIN CONTENT AREA
                     ============================================= -->
                <main class="lg:col-span-9 space-y-6">
                    
                    <!-- KPI Cards Row -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        
                        <!-- Total Laporan -->
                        <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-500 mb-1">Total Laporan</p>
                                    <p class="text-3xl font-bold text-gray-900">{{ formatNumber(stats?.total_laporan) }}</p>
                                </div>
                                <div class="p-3 bg-blue-100 rounded-xl">
                                    <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Total Kerugian -->
                        <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm text-gray-500 mb-1">Total Kerugian</p>
                                    <p class="text-2xl font-bold text-gray-900 truncate" :title="formatRupiah(stats?.total_kerugian)">
                                        {{ formatRupiah(stats?.total_kerugian) }}
                                    </p>
                                </div>
                                <div class="p-3 bg-red-100 rounded-xl flex-shrink-0 ml-3">
                                    <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Penyelesaian -->
                        <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-500 mb-1">Penyelesaian</p>
                                    <p class="text-3xl font-bold text-gray-900">{{ stats?.persentase_selesai || 0 }}%</p>
                                    <p class="text-xs text-gray-400 mt-1">{{ formatNumber(stats?.total_selesai) }} selesai</p>
                                </div>
                                <div class="p-3 bg-green-100 rounded-xl">
                                    <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Big Chart: Kategori Kejahatan (Horizontal Bar) -->
                    <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Perbandingan Jenis Aduan</h3>
                                <p class="text-sm text-gray-500">Semua kategori kejahatan siber, diurutkan dari yang terbanyak</p>
                            </div>
                            <div class="p-2 bg-blue-50 rounded-lg">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                        </div>
                        <VueApexCharts
                            type="bar"
                            :height="kategoriChartHeight"
                            :options="kategoriChartOptions"
                            :series="kategoriChartSeries"
                        />
                    </div>

                    <!-- Demographics Row: 3 Small Charts -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        
                        <!-- Gender Pie Chart -->
                        <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900">Jenis Kelamin</h3>
                                    <p class="text-xs text-gray-500">Distribusi pelapor</p>
                                </div>
                                <div class="p-2 bg-pink-50 rounded-lg">
                                    <svg class="w-4 h-4 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                            </div>
                            <VueApexCharts
                                type="pie"
                                height="280"
                                :options="genderChartOptions"
                                :series="genderChartSeries"
                            />
                        </div>

                        <!-- Age Group Bar Chart -->
                        <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900">Demografi Usia Pelapor</h3>
                                    <p class="text-xs text-gray-500">Distribusi umur pelapor saat melapor</p>
                                </div>
                                <div class="p-2 bg-green-50 rounded-lg">
                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <VueApexCharts
                                type="bar"
                                height="280"
                                :options="usiaChartOptions"
                                :series="usiaChartSeries"
                            />
                        </div>

                        <!-- Education Bar Chart -->
                        <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900">Tingkat Pendidikan</h3>
                                    <p class="text-xs text-gray-500">Distribusi pendidikan pelapor</p>
                                </div>
                                <div class="p-2 bg-indigo-50 rounded-lg">
                                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                </div>
                            </div>
                            <VueApexCharts
                                type="bar"
                                height="280"
                                :options="pendidikanChartOptions"
                                :series="pendidikanChartSeries"
                            />
                        </div>
                    </div>

                    <!-- =============================================
                         SECTION: PELAPOR, KORBAN & TERSANGKA
                         ============================================= -->
                    <div class="mt-8">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-2 bg-red-100 rounded-lg">
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-gray-900">Analisis Pelapor, Korban & Tersangka</h2>
                                <p class="text-sm text-gray-500">Perbandingan demografi dan statistik pelaku kejahatan siber</p>
                            </div>
                        </div>
                    </div>

                    <!-- Pelapor & Korban Summary Cards -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 text-white shadow-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-blue-100 text-xs font-medium">Total Pelapor</p>
                                    <p class="text-2xl font-bold mt-1">{{ formatNumber(pelaporKorbanComparison?.total_pelapor) }}</p>
                                </div>
                                <div class="p-2 bg-white/20 rounded-lg">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl p-4 text-white shadow-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-red-100 text-xs font-medium">Total Korban</p>
                                    <p class="text-2xl font-bold mt-1">{{ formatNumber(pelaporKorbanComparison?.total_korban) }}</p>
                                </div>
                                <div class="p-2 bg-white/20 rounded-lg">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-4 text-white shadow-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-purple-100 text-xs font-medium">Total Tersangka</p>
                                    <p class="text-2xl font-bold mt-1">{{ formatNumber(tersangkaStats?.total) }}</p>
                                </div>
                                <div class="p-2 bg-white/20 rounded-lg">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl p-4 text-white shadow-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-orange-100 text-xs font-medium">Tersangka Terhubung</p>
                                    <p class="text-2xl font-bold mt-1">{{ formatNumber(tersangkaStats?.total_linked) }}</p>
                                    <p class="text-orange-200 text-xs mt-0.5">{{ tersangkaStats?.total_linked_groups }} grup identitas sama</p>
                                </div>
                                <div class="p-2 bg-white/20 rounded-lg">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gender Comparison Chart (Pelapor vs Korban vs Tersangka) -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900">Perbandingan Gender</h3>
                                    <p class="text-xs text-gray-500">Pelapor vs Korban vs Tersangka</p>
                                </div>
                                <div class="p-2 bg-purple-50 rounded-lg">
                                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                </div>
                            </div>
                            <VueApexCharts
                                type="bar"
                                height="280"
                                :options="genderComparisonChartOptions"
                                :series="genderComparisonChartSeries"
                            />
                        </div>

                        <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900">Top 5 Pekerjaan Pelapor</h3>
                                    <p class="text-xs text-gray-500">Profesi yang paling sering menjadi pelapor</p>
                                </div>
                                <div class="p-2 bg-cyan-50 rounded-lg">
                                    <svg class="w-4 h-4 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            </div>
                            <VueApexCharts
                                type="bar"
                                height="280"
                                :options="pekerjaanChartOptions"
                                :series="pekerjaanChartSeries"
                            />
                        </div>
                    </div>

                    <!-- Tersangka Analysis -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Tersangka Identification Status -->
                        <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900">Status Identifikasi</h3>
                                    <p class="text-xs text-gray-500">Tersangka teridentifikasi vs belum</p>
                                </div>
                                <div class="px-2.5 py-1 bg-green-100 text-green-700 rounded-full text-xs font-bold">
                                    {{ tersangkaStats?.identification_rate || 0 }}% Teridentifikasi
                                </div>
                            </div>
                            <VueApexCharts
                                type="donut"
                                height="220"
                                :options="tersangkaStatusChartOptions"
                                :series="tersangkaStatusChartSeries"
                            />
                        </div>

                        <!-- Tersangka Identity Types -->
                        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900">Jenis Identitas Digital Tersangka</h3>
                                    <p class="text-xs text-gray-500">Rekening, telepon, akun sosmed, dan lainnya</p>
                                </div>
                                <div class="p-2 bg-purple-50 rounded-lg">
                                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                </div>
                            </div>
                            <VueApexCharts
                                type="bar"
                                height="220"
                                :options="identityTypeChartOptions"
                                :series="identityTypeChartSeries"
                            />
                        </div>
                    </div>

                </main>
            </div>

        </div>

        <!-- =============================================
             MOBILE FILTER MODAL (Bottom Sheet Style)
             ============================================= -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showFilterModal" class="fixed inset-0 z-50 lg:hidden">
                    <!-- Backdrop -->
                    <div
                        class="absolute inset-0 bg-black/50"
                        @click="showFilterModal = false"
                    ></div>
                    
                    <!-- Bottom Sheet -->
                    <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-2xl max-h-[85vh] overflow-y-auto">
                        <!-- Handle -->
                        <div class="flex justify-center py-3">
                            <div class="w-10 h-1 bg-gray-300 rounded-full"></div>
                        </div>
                        
                        <!-- Header -->
                        <div class="flex items-center justify-between px-5 pb-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Filter Data</h3>
                            <button
                                @click="showFilterModal = false"
                                class="p-2 hover:bg-gray-100 rounded-lg"
                            >
                                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        
                        <!-- Filter Content -->
                        <div class="p-5 space-y-5">
                            
                            <!-- Quick Date Chips -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Pilihan Cepat</label>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="opt in quickDateOptions"
                                        :key="opt.key"
                                        @click="applyQuickDate(opt.key)"
                                        :class="[
                                            'px-3 py-1.5 rounded-full text-sm font-medium transition-all',
                                            activeQuickDate === opt.key
                                                ? 'bg-purple-600 text-white'
                                                : 'bg-gray-100 text-gray-700 hover:bg-purple-50'
                                        ]"
                                    >
                                        {{ opt.label }}
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Date Range -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Rentang Tanggal Kustom</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <input
                                        v-model="localFilters.date_range.start"
                                        type="date"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                                    />
                                    <input
                                        v-model="localFilters.date_range.end"
                                        type="date"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                                    />
                                </div>
                            </div>

                            <!-- Gender -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Jenis Kelamin</label>
                                <div class="flex flex-wrap gap-2">
                                    <label
                                        v-for="gender in filterOptions.gender"
                                        :key="gender"
                                        class="flex items-center gap-2 px-3 py-2 border border-gray-200 rounded-lg cursor-pointer"
                                        :class="localFilters.filters.gender.includes(gender) ? 'bg-purple-50 border-purple-500' : ''"
                                    >
                                        <input
                                            type="checkbox"
                                            :checked="localFilters.filters.gender.includes(gender)"
                                            @change="toggleFilter('gender', gender)"
                                            class="w-4 h-4 text-purple-600 border-gray-300 rounded"
                                        />
                                        <span class="text-sm">{{ getGenderLabel(gender) }}</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Age Group -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Kelompok Usia</label>
                                <div class="flex flex-wrap gap-2">
                                    <label
                                        v-for="age in filterOptions.age_group"
                                        :key="age"
                                        class="flex items-center gap-2 px-3 py-2 border border-gray-200 rounded-lg cursor-pointer"
                                        :class="localFilters.filters.age_group.includes(age) ? 'bg-purple-50 border-purple-500' : ''"
                                    >
                                        <input
                                            type="checkbox"
                                            :checked="localFilters.filters.age_group.includes(age)"
                                            @change="toggleFilter('age_group', age)"
                                            class="w-4 h-4 text-purple-600 border-gray-300 rounded"
                                        />
                                        <span class="text-sm">{{ getAgeGroupLabel(age) }}</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Education -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Pendidikan</label>
                                <div class="flex flex-wrap gap-2 max-h-32 overflow-y-auto">
                                    <label
                                        v-for="pend in filterOptions.pendidikan"
                                        :key="pend"
                                        class="flex items-center gap-2 px-3 py-2 border border-gray-200 rounded-lg cursor-pointer"
                                        :class="localFilters.filters.pendidikan.includes(pend) ? 'bg-purple-50 border-purple-500' : ''"
                                    >
                                        <input
                                            type="checkbox"
                                            :checked="localFilters.filters.pendidikan.includes(pend)"
                                            @change="toggleFilter('pendidikan', pend)"
                                            class="w-4 h-4 text-purple-600 border-gray-300 rounded"
                                        />
                                        <span class="text-sm">{{ pend }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="p-5 border-t border-gray-200 flex gap-3">
                            <button
                                @click="resetFilters"
                                class="flex-1 px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors"
                            >
                                Reset
                            </button>
                            <button
                                @click="applyFilters"
                                class="flex-1 px-4 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-medium transition-colors"
                            >
                                Terapkan Filter
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </PimpinanLayout>
</template>

<style scoped>
/* Modal Transition */
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-active .absolute.bottom-0,
.modal-leave-active .absolute.bottom-0 {
    transition: transform 0.3s ease;
}

.modal-enter-from .absolute.bottom-0,
.modal-leave-to .absolute.bottom-0 {
    transform: translateY(100%);
}

/* Hide scrollbar but keep functionality */
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
</style>
