<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import SidebarLayout from '@/Layouts/SidebarLayout.vue';
import VueApexCharts from 'vue3-apexcharts';

const page = usePage();
const user = computed(() => page.props.auth?.user);

// Props from controller
const props = defineProps({
    metrics: Object,
    weeklyTrend: Object,
    platformDistribution: Object,
    categoryDistribution: Object,
    recentReports: Array,
    tersangkaSummary: Object,
});

const currentDate = computed(() => {
    return new Date().toLocaleDateString('id-ID', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
});

// Format currency
const formatRupiah = (amount) => {
    if (!amount) return 'Rp 0';
    return 'Rp ' + parseInt(amount).toLocaleString('id-ID');
};

// Get jenis identity label
const getJenisLabel = (jenis) => {
    const labels = {
        'rekening': 'Rekening',
        'telepon': 'Telepon',
        'email': 'Email',
        'social_media': 'Sosial Media',
        'website': 'Website',
        'lainnya': 'Lainnya',
    };
    return labels[jenis] || jenis;
};

// Mask identity value for privacy
const maskValue = (value, jenis) => {
    if (!value) return '-';
    if (jenis === 'rekening' && value.length > 6) {
        return value.substring(0, 3) + '***' + value.substring(value.length - 3);
    }
    if (jenis === 'telepon' && value.length > 6) {
        return value.substring(0, 4) + '****' + value.substring(value.length - 2);
    }
    if (value.length > 8) {
        return value.substring(0, 4) + '****' + value.substring(value.length - 4);
    }
    return value;
};

// Status badge classes
const getStatusClass = (status) => {
    const classes = {
        'Penyelidikan': 'bg-blue-100 text-blue-700',
        'Penyidikan': 'bg-indigo-100 text-indigo-700',
        'Tahap I': 'bg-yellow-100 text-yellow-700',
        'Tahap II': 'bg-orange-100 text-orange-700',
        'SP3': 'bg-red-100 text-red-700',
        'RJ': 'bg-green-100 text-green-700',
        'Diversi': 'bg-purple-100 text-purple-700',
    };
    return classes[status] || 'bg-gray-100 text-gray-700';
};

const getStatusLabel = (status) => {
    const labels = {
        'Penyelidikan': 'Penyelidikan',
        'Penyidikan': 'Penyidikan',
        'Tahap I': 'Tahap I',
        'Tahap II': 'Tahap II',
        'SP3': 'SP3',
        'RJ': 'Restorative Justice',
        'Diversi': 'Diversi',
    };
    return labels[status] || status;
};

// =============================================
// APEXCHARTS CONFIGURATION
// =============================================

// Weekly Trend Chart (Area)
const weeklyTrendOptions = computed(() => ({
    chart: {
        type: 'area',
        height: 280,
        toolbar: { show: false },
        fontFamily: 'Inter, sans-serif',
    },
    colors: ['#3B82F6'],
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.4,
            opacityTo: 0.1,
            stops: [0, 90, 100]
        }
    },
    dataLabels: { enabled: false },
    stroke: {
        curve: 'smooth',
        width: 3,
    },
    xaxis: {
        categories: props.weeklyTrend?.labels || [],
        labels: {
            style: { colors: '#6B7280', fontSize: '12px' }
        },
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: {
        labels: {
            style: { colors: '#6B7280', fontSize: '12px' }
        }
    },
    grid: {
        borderColor: '#E5E7EB',
        strokeDashArray: 4,
    },
    tooltip: {
        theme: 'light',
        y: {
            formatter: (val) => `${val} Laporan`
        }
    },
}));

const weeklyTrendSeries = computed(() => [{
    name: 'Laporan',
    data: props.weeklyTrend?.series || []
}]);

// Platform Distribution Chart (Donut)
const platformChartOptions = computed(() => ({
    chart: {
        type: 'donut',
        height: 280,
        fontFamily: 'Inter, sans-serif',
    },
    colors: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'],
    labels: props.platformDistribution?.labels || [],
    legend: {
        position: 'bottom',
        fontSize: '12px',
        markers: { width: 10, height: 10, radius: 3 },
    },
    plotOptions: {
        pie: {
            donut: {
                size: '65%',
                labels: {
                    show: true,
                    total: {
                        show: true,
                        label: 'Total',
                        fontSize: '14px',
                        fontWeight: 600,
                        color: '#1F2937',
                    }
                }
            }
        }
    },
    dataLabels: {
        enabled: false,
    },
    tooltip: {
        y: {
            formatter: (val) => `${val} Identitas`
        }
    },
}));

const platformChartSeries = computed(() => props.platformDistribution?.series || []);

// Category Distribution Chart (Horizontal Bar)
const categoryChartOptions = computed(() => ({
    chart: {
        type: 'bar',
        height: 280,
        toolbar: { show: false },
        fontFamily: 'Inter, sans-serif',
    },
    colors: ['#6366F1'],
    plotOptions: {
        bar: {
            horizontal: true,
            borderRadius: 4,
            barHeight: '60%',
        }
    },
    dataLabels: {
        enabled: true,
        style: { fontSize: '11px', fontWeight: 600, colors: ['#fff'] },
    },
    xaxis: {
        categories: props.categoryDistribution?.labels || [],
        labels: { style: { colors: '#6B7280', fontSize: '11px' } },
    },
    yaxis: {
        labels: { style: { colors: '#374151', fontSize: '11px' } },
    },
    grid: {
        borderColor: '#E5E7EB',
        strokeDashArray: 4,
        xaxis: { lines: { show: true } },
        yaxis: { lines: { show: false } },
    },
    tooltip: {
        y: {
            formatter: (val) => `${val} Laporan`
        }
    },
}));

const categoryChartSeries = computed(() => [{
    name: 'Laporan',
    data: props.categoryDistribution?.series || []
}]);
</script>

<template>
    <Head title="Dashboard Operasional" />

    <SidebarLayout title="Dashboard Operasional">
        <div class="space-y-6">
            
            <!-- Welcome Header -->
            <div class="bg-gradient-to-r from-navy via-blue-800 to-blue-900 rounded-2xl p-6 text-white shadow-xl">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <p class="text-blue-200 text-sm mb-1">{{ currentDate }}</p>
                        <h1 class="text-xl md:text-2xl font-bold">Selamat Datang di Sistem Pelaporan</h1>
                        <p class="text-blue-200 text-sm mt-1">Ditresiber Polda Jawa Tengah</p>
                    </div>
                </div>
            </div>

            <!-- =============================================
                 ROW 1: STATS CARDS (4 Cols)
                 ============================================= -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <!-- Today Reports -->
                <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Laporan Hari Ini</p>
                            <p class="text-3xl font-bold text-gray-900">{{ metrics?.today_reports || 0 }}</p>
                        </div>
                        <div class="p-3 bg-blue-100 rounded-xl">
                            <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center text-sm">
                        <span class="text-blue-600 font-medium">Hari ini</span>
                    </div>
                </div>

                <!-- Month Reports -->
                <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Laporan Bulan Ini</p>
                            <p class="text-3xl font-bold text-gray-900">{{ metrics?.month_reports || 0 }}</p>
                        </div>
                        <div class="p-3 bg-indigo-100 rounded-xl">
                            <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center text-sm">
                        <span class="text-indigo-600 font-medium">{{ formatRupiah(metrics?.month_loss) }} kerugian</span>
                    </div>
                </div>

                <!-- Process Reports -->
                <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Dalam Proses</p>
                            <p class="text-3xl font-bold text-gray-900">{{ metrics?.process_reports || 0 }}</p>
                        </div>
                        <div class="p-3 bg-amber-100 rounded-xl">
                            <svg class="w-7 h-7 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center text-sm">
                        <span class="text-amber-600 font-medium">Perlu ditindaklanjuti</span>
                    </div>
                </div>

                <!-- Top Crime -->
                <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-500 mb-1">Kejahatan Tertinggi</p>
                            <p class="text-lg font-bold text-gray-900 truncate" :title="metrics?.top_crime">
                                {{ metrics?.top_crime || 'Belum ada' }}
                            </p>
                        </div>
                        <div class="p-3 bg-red-100 rounded-xl flex-shrink-0 ml-3">
                            <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center text-sm">
                        <span class="text-red-600 font-medium">Bulan ini</span>
                    </div>
                </div>
            </div>

            <!-- =============================================
                 ROW 2: ANALYTICS CHARTS (2 Cols)
                 ============================================= -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                <!-- Weekly Trend Chart -->
                <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Trend Minggu Ini</h3>
                            <p class="text-sm text-gray-500">Jumlah laporan 7 hari terakhir</p>
                        </div>
                        <div class="p-2 bg-blue-50 rounded-lg">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                            </svg>
                        </div>
                    </div>
                    <VueApexCharts
                        type="area"
                        height="280"
                        :options="weeklyTrendOptions"
                        :series="weeklyTrendSeries"
                    />
                </div>

                <!-- Platform Distribution Chart -->
                <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Platform Kejahatan</h3>
                            <p class="text-sm text-gray-500">Top 5 platform identitas tersangka</p>
                        </div>
                        <div class="p-2 bg-green-50 rounded-lg">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                            </svg>
                        </div>
                    </div>
                    <VueApexCharts
                        type="donut"
                        height="280"
                        :options="platformChartOptions"
                        :series="platformChartSeries"
                    />
                </div>
            </div>

            <!-- =============================================
                 ROW 3: TERSANGKA SUMMARY CARDS
                 ============================================= -->
            <div v-if="tersangkaSummary" class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Ringkasan Tersangka</h2>
                    <Link
                        href="/tersangka"
                        class="text-sm font-medium text-blue-600 hover:text-blue-800 flex items-center gap-1"
                    >
                        Lihat Semua
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Total Tersangka -->
                    <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Total Tersangka</p>
                                <p class="text-3xl font-bold text-gray-900">{{ tersangkaSummary?.total || 0 }}</p>
                            </div>
                            <div class="p-3 bg-purple-100 rounded-xl">
                                <svg class="w-7 h-7 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center text-sm">
                            <span class="text-purple-600 font-medium">Dari semua laporan</span>
                        </div>
                    </div>

                    <!-- Belum Teridentifikasi -->
                    <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Belum Teridentifikasi</p>
                                <p class="text-3xl font-bold text-orange-600">{{ tersangkaSummary?.unidentified || 0 }}</p>
                            </div>
                            <div class="p-3 bg-orange-100 rounded-xl">
                                <svg class="w-7 h-7 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center text-sm">
                            <span class="text-orange-600 font-medium">Perlu identifikasi</span>
                        </div>
                    </div>

                    <!-- Tersangka Terhubung -->
                    <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Tersangka Terhubung</p>
                                <p class="text-3xl font-bold text-red-600">{{ tersangkaSummary?.linked || 0 }}</p>
                            </div>
                            <div class="p-3 bg-red-100 rounded-xl">
                                <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center text-sm">
                            <span class="text-red-600 font-medium">{{ tersangkaSummary?.linked_groups || 0 }} grup koneksi</span>
                        </div>
                    </div>

                    <!-- Identifikasi Rate -->
                    <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Tingkat Identifikasi</p>
                                <p class="text-3xl font-bold text-green-600">
                                    {{ tersangkaSummary?.total ? Math.round(((tersangkaSummary.total - tersangkaSummary.unidentified) / tersangkaSummary.total) * 100) : 0 }}%
                                </p>
                            </div>
                            <div class="p-3 bg-green-100 rounded-xl">
                                <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center text-sm">
                            <span class="text-green-600 font-medium">Tersangka teridentifikasi</span>
                        </div>
                    </div>
                </div>

                <!-- Top Repeated Identities -->
                <div v-if="tersangkaSummary?.top_repeated?.length > 0" class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Identitas Digital Berulang</h3>
                            <p class="text-sm text-gray-500">Identitas tersangka yang muncul di lebih dari 1 laporan</p>
                        </div>
                        <Link
                            href="/tersangka?tab=linked"
                            class="text-sm font-medium text-blue-600 hover:text-blue-800"
                        >
                            Lihat Detail
                        </Link>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Jenis</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Nilai</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Platform</th>
                                    <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600 uppercase">Frekuensi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(item, index) in tersangkaSummary.top_repeated" :key="index" class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full"
                                            :class="{
                                                'bg-blue-100 text-blue-700': item.jenis === 'rekening',
                                                'bg-green-100 text-green-700': item.jenis === 'telepon',
                                                'bg-purple-100 text-purple-700': item.jenis === 'email',
                                                'bg-pink-100 text-pink-700': item.jenis === 'social_media',
                                                'bg-gray-100 text-gray-700': !['rekening', 'telepon', 'email', 'social_media'].includes(item.jenis)
                                            }"
                                        >
                                            {{ getJenisLabel(item.jenis) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 font-mono text-sm text-gray-800">{{ maskValue(item.nilai, item.jenis) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ item.platform || '-' }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-2.5 py-1 text-xs font-bold bg-red-100 text-red-700 rounded-full">
                                            {{ item.count }}x
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- =============================================
                 ROW 4: RECENT REPORTS TABLE + CATEGORY CHART
                 ============================================= -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Recent Reports Table (2 cols) -->
                <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Laporan Terbaru</h3>
                            <p class="text-sm text-gray-500">5 laporan terakhir masuk</p>
                        </div>
                        <Link
                            href="/laporan"
                            class="text-sm font-medium text-blue-600 hover:text-blue-800 flex items-center gap-1"
                        >
                            Lihat Semua
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">No. STPA</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Waktu</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Pelapor</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Kategori</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="report in recentReports"
                                    :key="report.id"
                                    class="hover:bg-gray-50 transition-colors"
                                >
                                    <td class="px-4 py-3 text-sm font-medium text-navy">
                                        {{ report.nomor_stpa }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        <div>{{ report.created_at }}</div>
                                        <div class="text-xs text-gray-400">{{ report.created_at_diff }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <div class="font-medium text-gray-800">{{ report.pelapor_nama }}</div>
                                        <div class="text-xs text-gray-500">{{ report.pelapor_telepon }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ report.kategori }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="px-2.5 py-1 text-xs font-semibold rounded-full"
                                            :class="getStatusClass(report.status)"
                                        >
                                            {{ getStatusLabel(report.status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <Link
                                            :href="`/laporan/${report.id}`"
                                            class="inline-flex items-center justify-center p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                            title="Lihat Detail"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </Link>
                                    </td>
                                </tr>
                                
                                <!-- Empty State -->
                                <tr v-if="!recentReports || recentReports.length === 0">
                                    <td colspan="6" class="px-4 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <p class="text-gray-500 text-sm">Belum ada laporan</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Category Distribution Chart (1 col) -->
                <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Per Kategori</h3>
                            <p class="text-sm text-gray-500">Distribusi kejahatan</p>
                        </div>
                        <div class="p-2 bg-indigo-50 rounded-lg">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                    </div>
                    <VueApexCharts
                        type="bar"
                        height="280"
                        :options="categoryChartOptions"
                        :series="categoryChartSeries"
                    />
                </div>
            </div>

        </div>
    </SidebarLayout>
</template>

<style scoped>
/* Custom styles */
</style>
