<script setup>
import { computed } from 'vue';
import SidebarLayout from '@/Layouts/SidebarLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

// Props from DashboardController
const props = defineProps({
    summary: Object,
    laporanPerProvinsi: Array,
    laporanPerKategori: Array,
    topRekening: Array,
    recentLaporan: Array,
});

// Format currency
const formatRupiah = (value) => {
    if (!value) return 'Rp 0';
    return 'Rp ' + parseInt(value).toLocaleString('id-ID');
};

// Status badge colors
const getStatusColor = (status) => {
    const colors = {
        draft: 'bg-gray-100 text-gray-800',
        submitted: 'bg-blue-100 text-blue-800',
        verified: 'bg-green-100 text-green-800',
        investigating: 'bg-yellow-100 text-yellow-800',
        closed: 'bg-purple-100 text-purple-800',
        rejected: 'bg-red-100 text-red-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <Head title="Dashboard" />

    <SidebarLayout title="Dashboard">
        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Laporan -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Total Laporan</p>
                            <p class="text-3xl font-bold text-navy">{{ summary?.total_laporan || 0 }}</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">
                        <span class="text-green-600 font-medium">+{{ summary?.laporan_bulan_ini || 0 }}</span> bulan ini
                    </p>
                </div>

                <!-- Laporan Hari Ini -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Laporan Hari Ini</p>
                            <p class="text-3xl font-bold text-navy">{{ summary?.laporan_hari_ini || 0 }}</p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total Korban -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Total Korban</p>
                            <p class="text-3xl font-bold text-navy">{{ summary?.total_korban || 0 }}</p>
                        </div>
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total Kerugian -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Total Kerugian</p>
                            <p class="text-xl font-bold text-red-600">{{ formatRupiah(summary?.total_kerugian) }}</p>
                        </div>
                        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">
                        <span class="text-red-600 font-medium">{{ formatRupiah(summary?.total_kerugian_bulan_ini) }}</span> bulan ini
                    </p>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <Link href="/laporan/create" class="block">
                    <div class="bg-gradient-to-r from-tactical-accent to-blue-600 rounded-xl p-6 text-white hover:shadow-lg transition-all">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-lg">Entry Laporan Baru</h3>
                                <p class="text-white/80 text-sm">Buat laporan kejahatan siber</p>
                            </div>
                        </div>
                    </div>
                </Link>

                <Link href="/laporan" class="block">
                    <div class="bg-gradient-to-r from-navy to-slate-700 rounded-xl p-6 text-white hover:shadow-lg transition-all">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-lg">Arsip Laporan</h3>
                                <p class="text-white/80 text-sm">Lihat semua laporan</p>
                            </div>
                        </div>
                    </div>
                </Link>
            </div>

            <!-- Statistics Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Laporan per Provinsi -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="font-bold text-navy mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-tactical-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                        </svg>
                        Laporan per Provinsi
                    </h3>
                    
                    <div v-if="laporanPerProvinsi?.length" class="space-y-3">
                        <div v-for="(item, idx) in laporanPerProvinsi.slice(0, 5)" :key="idx" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div>
                                <p class="font-medium text-navy">{{ item.nama }}</p>
                                <p class="text-xs text-gray-500">{{ formatRupiah(item.total_kerugian) }} kerugian</p>
                            </div>
                            <span class="bg-tactical-accent text-white px-3 py-1 rounded-full text-sm font-medium">
                                {{ item.total_laporan }}
                            </span>
                        </div>
                    </div>
                    <div v-else class="text-center text-gray-400 py-8">
                        <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <p>Belum ada data statistik</p>
                    </div>
                </div>

                <!-- Top Rekening Dilaporkan -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="font-bold text-navy mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        Top Rekening Dilaporkan
                    </h3>
                    
                    <div v-if="topRekening?.length" class="space-y-3">
                        <div v-for="(item, idx) in topRekening.slice(0, 5)" :key="idx" class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                            <div>
                                <p class="font-mono font-medium text-navy">{{ item.nomor_rekening }}</p>
                                <p class="text-xs text-gray-500">{{ item.bank }}</p>
                            </div>
                            <span class="bg-red-500 text-white px-3 py-1 rounded-full text-sm font-medium">
                                {{ item.total_laporan }} laporan
                            </span>
                        </div>
                    </div>
                    <div v-else class="text-center text-gray-400 py-8">
                        <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        <p>Belum ada data rekening</p>
                    </div>
                </div>
            </div>

            <!-- Recent Laporan -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-navy flex items-center gap-2">
                        <svg class="w-5 h-5 text-tactical-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Laporan Terbaru
                    </h3>
                    <Link href="/laporan" class="text-tactical-accent hover:underline text-sm">
                        Lihat Semua →
                    </Link>
                </div>
                
                <div v-if="recentLaporan?.length" class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                                <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">STPA</th>
                                <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Pelapor</th>
                                <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Jenis</th>
                                <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Provinsi</th>
                                <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="lap in recentLaporan" :key="lap.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm">{{ lap.tanggal_laporan }}</td>
                                <td class="px-4 py-3 text-sm font-mono">{{ lap.nomor_stpa }}</td>
                                <td class="px-4 py-3 text-sm">{{ lap.pelapor }}</td>
                                <td class="px-4 py-3 text-sm">{{ lap.jenis_kejahatan }}</td>
                                <td class="px-4 py-3 text-sm">{{ lap.provinsi }}</td>
                                <td class="px-4 py-3">
                                    <span :class="['px-2 py-1 rounded-full text-xs font-medium', getStatusColor(lap.status)]">
                                        {{ lap.status_label }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="text-center text-gray-400 py-8">
                    <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p>Belum ada laporan</p>
                    <Link href="/laporan/create" class="text-tactical-accent hover:underline mt-2 inline-block">
                        Buat laporan pertama →
                    </Link>
                </div>
            </div>
        </div>
    </SidebarLayout>
</template>
