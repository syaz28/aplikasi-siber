<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ToastContainer from '@/Components/ToastContainer.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    userStats: Object,
    laporanStats: Object,
    laporanBaru: Array,
    aktivitasTerakhir: Array,
    pelaporTersangkaSummary: Object,
});

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
const page = usePage();

onMounted(() => {
    if (page.props.flash?.success) {
        toast.success(page.props.flash.success);
    }
    if (page.props.flash?.error) {
        toast.error(page.props.flash.error);
    }
});

// Format date
const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Get short STPA number
const getShortStpa = (nomorStpa) => {
    if (!nomorStpa) return '-';
    const parts = nomorStpa.split('/');
    if (parts.length >= 2) {
        return parts[1];
    }
    return nomorStpa;
};

// Format currency
const formatRupiah = (amount) => {
    if (!amount) return 'Rp 0';
    return 'Rp ' + parseInt(amount).toLocaleString('id-ID');
};

// Get total kerugian
const getTotalKerugian = (korban) => {
    if (!korban || !Array.isArray(korban)) return 0;
    return korban.reduce((sum, k) => sum + (parseInt(k.kerugian_nominal) || 0), 0);
};
</script>

<template>
    <Head title="Admin Dashboard" />

    <AdminLayout title="Dashboard Admin">
        <ToastContainer />
        
        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Welcome Section -->
            <div class="bg-gradient-to-r from-slate-800 to-slate-700 rounded-xl p-6 text-white">
                <h1 class="text-2xl font-bold mb-2">Selamat Datang, Admin</h1>
                <p class="text-slate-300">Panel administrasi Sistem Pelaporan Ditresiber Polda Jawa Tengah</p>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total User -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-blue-100 rounded-xl">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-800">{{ userStats.total }}</p>
                            <p class="text-sm text-slate-500">Total User</p>
                        </div>
                    </div>
                    <div class="mt-3 flex gap-2 text-xs">
                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded">{{ userStats.active }} Aktif</span>
                    </div>
                </div>

                <!-- Total Laporan -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-amber-100 rounded-xl">
                            <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-800">{{ laporanStats.total }}</p>
                            <p class="text-sm text-slate-500">Total Laporan</p>
                        </div>
                    </div>
                </div>

                <!-- Belum Ditugaskan -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-red-100 rounded-xl">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-red-600">{{ laporanStats.belum_ditugaskan }}</p>
                            <p class="text-sm text-slate-500">Belum Ditugaskan</p>
                        </div>
                    </div>
                    <Link 
                        v-if="laporanStats.belum_ditugaskan > 0"
                        href="/admin/laporan?assigned=no" 
                        class="mt-3 inline-flex items-center gap-1 text-xs text-red-600 hover:text-red-700"
                    >
                        Lihat semua
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>
                </div>

                <!-- Sudah Ditugaskan -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-green-100 rounded-xl">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-green-600">{{ laporanStats.sudah_ditugaskan }}</p>
                            <p class="text-sm text-slate-500">Sudah Ditugaskan</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Distribution Cards -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- User by Role -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h3 class="text-lg font-semibold text-slate-800 mb-4">Distribusi User</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-slate-600">Admin</span>
                            <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-sm font-medium">{{ userStats.by_role.admin }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-slate-600">Petugas</span>
                            <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-sm font-medium">{{ userStats.by_role.petugas }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-slate-600">Admin Subdit</span>
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-sm font-medium">{{ userStats.by_role.admin_subdit }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-slate-600">Pimpinan</span>
                            <span class="px-2 py-1 bg-purple-100 text-purple-700 rounded text-sm font-medium">{{ userStats.by_role.pimpinan }}</span>
                        </div>
                    </div>
                    <Link 
                        href="/admin/users" 
                        class="mt-4 inline-flex items-center gap-2 text-sm text-amber-600 hover:text-amber-700"
                    >
                        Kelola User
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>
                </div>

                <!-- Laporan by Subdit -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h3 class="text-lg font-semibold text-slate-800 mb-4">Laporan per Subdit</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-slate-600">Subdit 1</span>
                            <span class="px-2 py-1 bg-slate-100 text-slate-700 rounded text-sm font-medium">{{ laporanStats.by_subdit.subdit_1 }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-slate-600">Subdit 2</span>
                            <span class="px-2 py-1 bg-slate-100 text-slate-700 rounded text-sm font-medium">{{ laporanStats.by_subdit.subdit_2 }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-slate-600">Subdit 3</span>
                            <span class="px-2 py-1 bg-slate-100 text-slate-700 rounded text-sm font-medium">{{ laporanStats.by_subdit.subdit_3 }}</span>
                        </div>
                    </div>
                    <Link 
                        href="/admin/laporan" 
                        class="mt-4 inline-flex items-center gap-2 text-sm text-amber-600 hover:text-amber-700"
                    >
                        Kelola Laporan
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>
                </div>
            </div>

            <!-- Laporan Baru (Belum ditugaskan) -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-6 border-b border-slate-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-slate-800">Laporan Baru (Perlu Ditugaskan)</h3>
                        <Link 
                            href="/admin/laporan?assigned=no" 
                            class="text-sm text-amber-600 hover:text-amber-700"
                        >
                            Lihat Semua
                        </Link>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">No. STPA</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Pelapor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Kategori</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Kerugian</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <tr v-if="laporanBaru.length === 0">
                                <td colspan="5" class="px-6 py-8 text-center text-slate-500">
                                    <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Semua laporan sudah ditugaskan
                                </td>
                            </tr>
                            <tr v-for="lap in laporanBaru" :key="lap.id" class="hover:bg-slate-50">
                                <td class="px-6 py-4">
                                    <span class="font-mono text-sm font-medium text-slate-800">
                                        {{ getShortStpa(lap.nomor_stpa) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-medium text-slate-800">{{ lap.pelapor?.nama }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-slate-600">{{ lap.kategori_kejahatan?.nama }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm font-medium text-red-600">
                                        {{ formatRupiah(getTotalKerugian(lap.korban)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <Link 
                                        :href="`/admin/laporan/${lap.id}`"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-white bg-amber-500 rounded-lg hover:bg-amber-600 transition"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Proses
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Aktivitas Terakhir -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-lg font-semibold text-slate-800 mb-4">Aktivitas Terakhir</h3>
                <div class="space-y-4">
                    <div v-if="aktivitasTerakhir.length === 0" class="text-center text-slate-500 py-4">
                        Belum ada aktivitas
                    </div>
                    <div 
                        v-for="act in aktivitasTerakhir" 
                        :key="act.id"
                        class="flex items-start gap-4 p-3 bg-slate-50 rounded-lg"
                    >
                        <div class="p-2 bg-green-100 rounded-lg flex-shrink-0">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-slate-800">
                                <span class="font-medium">{{ getShortStpa(act.nomor_stpa) }}</span>
                                ditugaskan ke 
                                <span class="font-medium text-amber-600">Subdit {{ act.assigned_subdit }}</span>
                            </p>
                            <p class="text-xs text-slate-500 mt-1">
                                oleh {{ act.assigned_by?.name || 'Admin' }} • {{ formatDate(act.assigned_at) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- =============================================
                 PELAPOR & TERSANGKA SUMMARY
                 ============================================= -->
            <div v-if="pelaporTersangkaSummary" class="space-y-4">
                <h2 class="text-lg font-semibold text-slate-800">Ringkasan Pelapor & Tersangka</h2>
                
                <!-- Stats Cards -->
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
                    <!-- Total Pelapor -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center mb-2">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <p class="text-xs text-slate-500">Pelapor</p>
                            <p class="text-xl font-bold text-blue-600">{{ pelaporTersangkaSummary.total_pelapor }}</p>
                        </div>
                    </div>

                    <!-- Total Korban -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center mb-2">
                                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                                </svg>
                            </div>
                            <p class="text-xs text-slate-500">Korban</p>
                            <p class="text-xl font-bold text-orange-600">{{ pelaporTersangkaSummary.total_korban }}</p>
                        </div>
                    </div>

                    <!-- Total Tersangka -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center mb-2">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <p class="text-xs text-slate-500">Tersangka</p>
                            <p class="text-xl font-bold text-purple-600">{{ pelaporTersangkaSummary.total_tersangka }}</p>
                        </div>
                    </div>

                    <!-- Belum Teridentifikasi -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-10 h-10 rounded-lg bg-yellow-100 flex items-center justify-center mb-2">
                                <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <p class="text-xs text-slate-500">Belum ID</p>
                            <p class="text-xl font-bold text-yellow-600">{{ pelaporTersangkaSummary.unidentified_tersangka }}</p>
                        </div>
                    </div>

                    <!-- Tersangka Terhubung -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center mb-2">
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                            </div>
                            <p class="text-xs text-slate-500">Terhubung</p>
                            <p class="text-xl font-bold text-red-600">{{ pelaporTersangkaSummary.linked_tersangka }}</p>
                        </div>
                    </div>

                    <!-- Linked Groups -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-10 h-10 rounded-lg bg-pink-100 flex items-center justify-center mb-2">
                                <svg class="w-5 h-5 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857m0 0a5.002 5.002 0 00-9.288 0M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0" />
                                </svg>
                            </div>
                            <p class="text-xs text-slate-500">Grup Link</p>
                            <p class="text-xl font-bold text-pink-600">{{ pelaporTersangkaSummary.linked_groups }}</p>
                        </div>
                    </div>

                    <!-- Total Kerugian -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center mb-2">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <p class="text-xs text-slate-500">Kerugian</p>
                            <p class="text-sm font-bold text-green-600">{{ formatRupiah(pelaporTersangkaSummary.total_kerugian) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Tersangka per Subdit & Identity Types -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Tersangka per Subdit -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                        <h3 class="text-lg font-semibold text-slate-800 mb-4">Tersangka per Subdit</h3>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-slate-600">Subdit 1</span>
                                <span class="px-2 py-1 bg-purple-100 text-purple-700 rounded text-sm font-medium">
                                    {{ pelaporTersangkaSummary.tersangka_by_subdit?.subdit_1 || 0 }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-slate-600">Subdit 2</span>
                                <span class="px-2 py-1 bg-purple-100 text-purple-700 rounded text-sm font-medium">
                                    {{ pelaporTersangkaSummary.tersangka_by_subdit?.subdit_2 || 0 }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-slate-600">Subdit 3</span>
                                <span class="px-2 py-1 bg-purple-100 text-purple-700 rounded text-sm font-medium">
                                    {{ pelaporTersangkaSummary.tersangka_by_subdit?.subdit_3 || 0 }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Top Identity Types -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                        <h3 class="text-lg font-semibold text-slate-800 mb-4">Jenis Identitas Digital Tersangka</h3>
                        <div class="flex flex-wrap gap-2">
                            <div 
                                v-for="(item, index) in pelaporTersangkaSummary.top_identity_types" 
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
                                    {{ item.total }}
                                </span>
                            </div>
                            <div v-if="!pelaporTersangkaSummary.top_identity_types?.length" class="text-sm text-slate-500">
                                Belum ada data identitas tersangka
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
