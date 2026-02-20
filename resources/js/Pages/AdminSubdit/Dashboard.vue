<script setup>
import SidebarLayout from '@/Layouts/SidebarLayout.vue';
import ToastContainer from '@/Components/ToastContainer.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    stats: Object,
    pendingLaporans: Array,
    unitChart: Object,
    unitOptions: Array,
    subdit: Number,
    pelaporTersangkaStats: Object,
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

const toast = useToast();

// Modal state
const showDisposisiModal = ref(false);
const selectedLaporan = ref(null);
const selectedUnit = ref('');
const isSubmitting = ref(false);

// ApexCharts
let chart = null;

const openDisposisiModal = (laporan) => {
    selectedLaporan.value = laporan;
    selectedUnit.value = '';
    showDisposisiModal.value = true;
};

const closeDisposisiModal = () => {
    showDisposisiModal.value = false;
    selectedLaporan.value = null;
    selectedUnit.value = '';
};

const submitDisposisi = () => {
    if (!selectedUnit.value) {
        toast.error('Pilih unit terlebih dahulu');
        return;
    }

    isSubmitting.value = true;
    router.patch(`/subdit/dashboard/${selectedLaporan.value.id}/assign-unit`, {
        unit: selectedUnit.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(`Laporan berhasil didisposisi ke Unit ${selectedUnit.value}`);
            closeDisposisiModal();
        },
        onError: () => {
            toast.error('Gagal mendisposisi laporan');
        },
        onFinish: () => {
            isSubmitting.value = false;
        }
    });
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

const getShortStpa = (stpa) => {
    if (!stpa) return '-';
    const parts = stpa.split('/');
    if (parts.length >= 2) {
        return `${parts[1]}/${parts[2]}`;
    }
    return stpa;
};

// Initialize chart
onMounted(async () => {
    if (typeof window !== 'undefined') {
        const ApexCharts = (await import('apexcharts')).default;
        
        const options = {
            series: [{
                name: 'Kasus Aktif',
                data: props.unitChart.data
            }],
            chart: {
                type: 'bar',
                height: 280,
                toolbar: { show: false },
                fontFamily: 'Inter, sans-serif',
            },
            plotOptions: {
                bar: {
                    borderRadius: 6,
                    horizontal: false,
                    columnWidth: '60%',
                    distributed: true,
                }
            },
            colors: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'],
            dataLabels: {
                enabled: true,
                style: {
                    fontSize: '12px',
                    fontWeight: 'bold',
                }
            },
            legend: { show: false },
            xaxis: {
                categories: props.unitChart.labels,
                labels: {
                    style: {
                        fontSize: '12px',
                        fontWeight: 500,
                    }
                }
            },
            yaxis: {
                title: { text: 'Jumlah Kasus' },
                labels: {
                    formatter: (val) => Math.round(val)
                }
            },
            grid: {
                borderColor: '#E2E8F0',
                strokeDashArray: 4,
            },
            tooltip: {
                y: {
                    formatter: (val) => `${val} kasus`
                }
            }
        };

        const chartEl = document.querySelector('#unitWorkloadChart');
        if (chartEl) {
            chart = new ApexCharts(chartEl, options);
            chart.render();
        }
    }
});
</script>

<template>
    <Head title="Dashboard Admin Subdit" />
    <SidebarLayout>
        <ToastContainer />
        
        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">Dashboard Subdit {{ subdit }}</h1>
                    <p class="text-slate-500 mt-1">Manajemen distribusi kasus ke unit</p>
                </div>
                <Link
                    href="/min-ops"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-navy text-white rounded-lg hover:bg-navy-700 transition"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                    Lihat Semua Kasus
                </Link>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Pending Disposisi -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-xl bg-amber-100 flex items-center justify-center">
                            <svg class="w-7 h-7 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500">Menunggu Disposisi</p>
                            <p class="text-3xl font-bold text-amber-600">{{ stats.pending_disposisi }}</p>
                        </div>
                    </div>
                </div>

                <!-- Total Proses -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-xl bg-blue-100 flex items-center justify-center">
                            <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500">Sedang Diproses</p>
                            <p class="text-3xl font-bold text-blue-600">{{ stats.total_proses }}</p>
                        </div>
                    </div>
                </div>

                <!-- Selesai Bulan Ini -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-xl bg-green-100 flex items-center justify-center">
                            <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500">Selesai Bulan Ini</p>
                            <p class="text-3xl font-bold text-green-600">{{ stats.selesai_bulan_ini }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content: Table + Chart -->
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
                <!-- Left: Pending Table (60%) -->
                <div class="lg:col-span-3 bg-white rounded-xl shadow-sm border border-slate-200">
                    <div class="p-6 border-b border-slate-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-slate-800">Laporan Menunggu Disposisi</h2>
                                <p class="text-sm text-slate-500 mt-1">Klik tombol disposisi untuk menugaskan ke unit</p>
                            </div>
                            <span class="px-3 py-1 text-sm font-medium bg-amber-100 text-amber-700 rounded-full">
                                {{ stats.pending_disposisi }} laporan
                            </span>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">No. STPA</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Pelapor</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Tanggal</th>
                                    <th class="px-6 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                <tr v-for="lap in pendingLaporans" :key="lap.id" class="hover:bg-slate-50 transition">
                                    <td class="px-6 py-4">
                                        <Link :href="`/min-ops/kasus/${lap.id}`" class="font-mono text-sm font-medium text-blue-600 hover:underline">
                                            {{ getShortStpa(lap.nomor_stpa) }}
                                        </Link>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-sm font-medium text-slate-800">{{ lap.pelapor_nama }}</p>
                                        <p class="text-xs text-slate-500">{{ lap.kategori }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600">
                                        {{ formatDate(lap.tanggal_laporan) }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <button
                                            @click="openDisposisiModal(lap)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-500 text-white text-sm font-medium rounded-lg hover:bg-amber-600 transition"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                            </svg>
                                            Disposisi
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="pendingLaporans.length === 0">
                                    <td colspan="4" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <svg class="w-12 h-12 text-green-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <p class="text-slate-500">Semua laporan sudah didisposisi</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="pendingLaporans.length > 0" class="p-4 border-t border-slate-200 bg-slate-50">
                        <Link href="/min-ops?unit=" class="text-sm text-blue-600 hover:underline">
                            Lihat semua laporan menunggu disposisi →
                        </Link>
                    </div>
                </div>

                <!-- Right: Chart (40%) -->
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <div class="mb-4">
                        <h2 class="text-lg font-semibold text-slate-800">Beban Kerja Unit</h2>
                        <p class="text-sm text-slate-500">Distribusi kasus aktif per unit</p>
                    </div>
                    <div id="unitWorkloadChart"></div>
                </div>
            </div>

            <!-- ======================================
                 ROW 3: PELAPOR & TERSANGKA STATS
                 ====================================== -->
            <div v-if="pelaporTersangkaStats" class="space-y-4">
                <h2 class="text-lg font-semibold text-slate-800">Statistik Pelapor & Tersangka</h2>
                
                <!-- Stats Cards Row -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <!-- Total Pelapor -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Pelapor</p>
                                <p class="text-xl font-bold text-blue-600">{{ pelaporTersangkaStats.total_pelapor }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Total Korban -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Korban</p>
                                <p class="text-xl font-bold text-orange-600">{{ pelaporTersangkaStats.total_korban }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Total Tersangka -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Tersangka</p>
                                <p class="text-xl font-bold text-purple-600">{{ pelaporTersangkaStats.total_tersangka }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Belum Teridentifikasi -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-yellow-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Belum Identifikasi</p>
                                <p class="text-xl font-bold text-yellow-600">{{ pelaporTersangkaStats.unidentified_tersangka }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Tersangka Terhubung -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Terhubung</p>
                                <p class="text-xl font-bold text-red-600">{{ pelaporTersangkaStats.linked_tersangka }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Total Kerugian -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Kerugian</p>
                                <p class="text-sm font-bold text-green-600">{{ formatRupiah(pelaporTersangkaStats.total_kerugian) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Identity Types Distribution -->
                <div v-if="pelaporTersangkaStats.identity_types?.length > 0" class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-slate-800">Jenis Identitas Digital Tersangka</h3>
                        <Link href="/subdit/tersangka" class="text-xs text-blue-600 hover:underline">
                            Detail →
                        </Link>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <div 
                            v-for="(item, index) in pelaporTersangkaStats.identity_types" 
                            :key="index"
                            class="flex items-center gap-2 px-3 py-2 rounded-lg"
                            :class="{
                                'bg-blue-50': item.jenis === 'rekening',
                                'bg-green-50': item.jenis === 'telepon',
                                'bg-purple-50': item.jenis === 'email',
                                'bg-pink-50': item.jenis === 'social_media',
                                'bg-gray-50': !['rekening', 'telepon', 'email', 'social_media'].includes(item.jenis)
                            }"
                        >
                            <span 
                                class="text-sm font-medium"
                                :class="{
                                    'text-blue-700': item.jenis === 'rekening',
                                    'text-green-700': item.jenis === 'telepon',
                                    'text-purple-700': item.jenis === 'email',
                                    'text-pink-700': item.jenis === 'social_media',
                                    'text-gray-700': !['rekening', 'telepon', 'email', 'social_media'].includes(item.jenis)
                                }"
                            >
                                {{ getJenisLabel(item.jenis) }}
                            </span>
                            <span 
                                class="px-1.5 py-0.5 text-xs font-bold rounded-full"
                                :class="{
                                    'bg-blue-200 text-blue-800': item.jenis === 'rekening',
                                    'bg-green-200 text-green-800': item.jenis === 'telepon',
                                    'bg-purple-200 text-purple-800': item.jenis === 'email',
                                    'bg-pink-200 text-pink-800': item.jenis === 'social_media',
                                    'bg-gray-200 text-gray-800': !['rekening', 'telepon', 'email', 'social_media'].includes(item.jenis)
                                }"
                            >
                                {{ item.count }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Disposisi Modal -->
        <Teleport to="body">
            <div v-if="showDisposisiModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <!-- Backdrop -->
                    <div class="fixed inset-0 bg-black/50 transition-opacity" @click="closeDisposisiModal"></div>
                    
                    <!-- Modal -->
                    <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6 z-10">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-slate-800">Disposisi Laporan</h3>
                            <button @click="closeDisposisiModal" class="text-slate-400 hover:text-slate-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div v-if="selectedLaporan" class="mb-6">
                            <div class="bg-slate-50 rounded-lg p-4 mb-4">
                                <p class="text-sm text-slate-500">No. STPA</p>
                                <p class="font-mono font-semibold text-slate-800">{{ selectedLaporan.nomor_stpa }}</p>
                                <p class="text-sm text-slate-500 mt-2">Pelapor</p>
                                <p class="font-medium text-slate-800">{{ selectedLaporan.pelapor_nama }}</p>
                            </div>

                            <label class="block text-sm font-medium text-slate-700 mb-2">
                                Pilih Unit Tujuan
                            </label>
                            <div class="grid grid-cols-5 gap-2">
                                <button
                                    v-for="opt in unitOptions"
                                    :key="opt.value"
                                    @click="selectedUnit = opt.value"
                                    :class="[
                                        'py-3 rounded-lg border-2 font-semibold transition text-center',
                                        selectedUnit === opt.value
                                            ? 'border-amber-500 bg-amber-50 text-amber-700'
                                            : 'border-slate-200 text-slate-600 hover:border-slate-300'
                                    ]"
                                >
                                    {{ opt.value }}
                                </button>
                            </div>
                        </div>

                        <div class="flex gap-3">
                            <button
                                @click="closeDisposisiModal"
                                class="flex-1 py-2.5 px-4 bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 transition font-medium"
                            >
                                Batal
                            </button>
                            <button
                                @click="submitDisposisi"
                                :disabled="!selectedUnit || isSubmitting"
                                class="flex-1 py-2.5 px-4 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span v-if="isSubmitting">Menyimpan...</span>
                                <span v-else>Disposisi ke Unit {{ selectedUnit }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </SidebarLayout>
</template>
