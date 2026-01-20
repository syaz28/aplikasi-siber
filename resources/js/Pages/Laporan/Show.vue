<script setup>
import SidebarLayout from '@/Layouts/SidebarLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    laporan: Object,
});

// Format date
const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};

// Format currency
const formatRupiah = (amount) => {
    if (!amount) return 'Rp 0';
    return 'Rp ' + parseInt(amount).toLocaleString('id-ID');
};

// Status badge
const getStatusClass = (stat) => {
    const classes = {
        draft: 'bg-gray-100 text-gray-800',
        submitted: 'bg-blue-100 text-blue-800',
        verified: 'bg-green-100 text-green-800',
        investigating: 'bg-yellow-100 text-yellow-800',
        closed: 'bg-purple-100 text-purple-800',
        rejected: 'bg-red-100 text-red-800',
    };
    return classes[stat] || 'bg-gray-100 text-gray-800';
};

const getStatusLabel = (stat) => {
    const labels = {
        draft: 'Draft',
        submitted: 'Diajukan',
        verified: 'Terverifikasi',
        investigating: 'Dalam Penyelidikan',
        closed: 'Ditutup',
        rejected: 'Ditolak',
    };
    return labels[stat] || stat;
};
</script>

<template>
    <Head :title="`Laporan ${laporan?.nomor_stpa || laporan?.id}`" />

    <SidebarLayout :title="`Detail Laporan #${laporan?.id}`">
        <div class="max-w-5xl mx-auto">
            <!-- Header Actions -->
            <div class="flex items-center justify-between mb-6">
                <Link href="/laporan" class="text-gray-500 hover:text-navy flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Kembali ke Arsip
                </Link>

                <a
                    :href="`/laporan/${laporan?.id}/pdf`"
                    target="_blank"
                    class="px-4 py-2 bg-tactical-danger text-white font-semibold rounded-lg hover:bg-red-700 transition-colors flex items-center gap-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Cetak STPA (PDF)
                </a>
            </div>

            <!-- Main Content -->
            <div class="space-y-6">
                <!-- Info Umum -->
                <div class="bg-white rounded-xl shadow-tactical border border-tactical-border overflow-hidden">
                    <div class="bg-navy px-6 py-3 border-l-4 border-tactical-accent">
                        <h3 class="text-lg font-bold text-white">Informasi Laporan</h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <div class="text-sm text-gray-500">Nomor STPA</div>
                            <div class="font-semibold text-navy">{{ laporan?.nomor_stpa || '-' }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Tanggal Laporan</div>
                            <div class="font-semibold">{{ formatDate(laporan?.tanggal_laporan) }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Jenis Kejahatan</div>
                            <div class="font-semibold">{{ laporan?.jenis_kejahatan?.nama || '-' }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Status</div>
                            <span class="px-3 py-1 text-sm font-semibold rounded-full" :class="getStatusClass(laporan?.status)">
                                {{ getStatusLabel(laporan?.status) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Data Pelapor -->
                <div class="bg-white rounded-xl shadow-tactical border border-tactical-border overflow-hidden">
                    <div class="bg-navy px-6 py-3 border-l-4 border-tactical-accent">
                        <h3 class="text-lg font-bold text-white">Data Pelapor</h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <div class="text-sm text-gray-500">Nama</div>
                            <div class="font-semibold">{{ laporan?.pelapor?.nama || '-' }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">NIK</div>
                            <div class="font-semibold">{{ laporan?.pelapor?.nik || '-' }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Telepon</div>
                            <div class="font-semibold">{{ laporan?.pelapor?.telepon || '-' }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Pekerjaan</div>
                            <div class="font-semibold">{{ laporan?.pelapor?.pekerjaan || '-' }}</div>
                        </div>
                    </div>
                </div>

                <!-- Data Korban -->
                <div class="bg-white rounded-xl shadow-tactical border border-tactical-border overflow-hidden">
                    <div class="bg-navy px-6 py-3 border-l-4 border-tactical-success">
                        <h3 class="text-lg font-bold text-white">Data Korban ({{ laporan?.korban?.length || 0 }})</h3>
                    </div>
                    <div class="p-6">
                        <div v-if="laporan?.korban?.length" class="space-y-4">
                            <div
                                v-for="(korban, index) in laporan.korban"
                                :key="index"
                                class="p-4 bg-gray-50 rounded-lg border border-gray-200"
                            >
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <div class="text-sm text-gray-500">Nama Korban</div>
                                        <div class="font-semibold">{{ korban.orang?.nama || '-' }}</div>
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-500">Kerugian</div>
                                        <div class="font-semibold text-tactical-danger">{{ formatRupiah(korban.kerugian_nominal) }}</div>
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-500">Terbilang</div>
                                        <div class="text-sm italic">{{ korban.kerugian_terbilang || '-' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-gray-500 text-center py-4">
                            Tidak ada data korban
                        </div>
                    </div>
                </div>

                <!-- Data Tersangka -->
                <div class="bg-white rounded-xl shadow-tactical border border-tactical-border overflow-hidden">
                    <div class="bg-navy px-6 py-3 border-l-4 border-tactical-danger">
                        <h3 class="text-lg font-bold text-white">Data Tersangka ({{ laporan?.tersangka?.length || 0 }})</h3>
                    </div>
                    <div class="p-6">
                        <div v-if="laporan?.tersangka?.length" class="space-y-4">
                            <div
                                v-for="(tersangka, index) in laporan.tersangka"
                                :key="index"
                                class="p-4 bg-gray-50 rounded-lg border border-gray-200"
                            >
                                <div class="font-semibold mb-2">
                                    Tersangka {{ index + 1 }}: {{ tersangka.orang?.nama || 'Belum Teridentifikasi' }}
                                </div>
                                <div v-if="tersangka.identitas?.length" class="space-y-1">
                                    <div
                                        v-for="(id, idIndex) in tersangka.identitas"
                                        :key="idIndex"
                                        class="text-sm text-gray-600"
                                    >
                                        <span class="font-medium">{{ id.jenis }}:</span>
                                        {{ id.nilai }}
                                        <span v-if="id.platform" class="text-gray-400">({{ id.platform }})</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-gray-500 text-center py-4">
                            Tidak ada data tersangka
                        </div>
                    </div>
                </div>

                <!-- Modus Operandi -->
                <div class="bg-white rounded-xl shadow-tactical border border-tactical-border overflow-hidden">
                    <div class="bg-navy px-6 py-3 border-l-4 border-tactical-warning">
                        <h3 class="text-lg font-bold text-white">Modus Operandi</h3>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-700 whitespace-pre-wrap">{{ laporan?.modus || '-' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </SidebarLayout>
</template>
