<script setup>
import SidebarLayout from '@/Layouts/SidebarLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    laporan: Object,
    trackRecord: Object,
});

// State untuk accordion track record
const expandedTrackRecord = ref({});

const toggleTrackRecord = (tersangkaId) => {
    expandedTrackRecord.value[tersangkaId] = !expandedTrackRecord.value[tersangkaId];
};

const hasTrackRecord = (tersangkaId) => {
    return props.trackRecord && props.trackRecord[tersangkaId] && props.trackRecord[tersangkaId].length > 0;
};

const getTrackRecordCount = (tersangkaId) => {
    if (!props.trackRecord || !props.trackRecord[tersangkaId]) return 0;
    // Hitung unique laporan_id
    const uniqueLaporan = new Set(props.trackRecord[tersangkaId].map(t => t.laporan_id));
    return uniqueLaporan.size;
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};

const formatDateTime = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatRupiah = (amount) => {
    if (!amount) return 'Rp 0';
    return 'Rp ' + parseInt(amount).toLocaleString('id-ID');
};

const formatNik = (nik) => {
    if (!nik) return '-';
    const clean = String(nik).replace(/\D/g, '');
    return clean.replace(/(\d{4})(?=\d)/g, '$1.');
};

const getTotalKerugian = computed(() => {
    if (!props.laporan?.korban || !Array.isArray(props.laporan.korban)) return 0;
    return props.laporan.korban.reduce((sum, k) => sum + (parseInt(k.kerugian_nominal) || 0), 0);
});

const getIdentitasLabel = (jenis) => {
    const labels = {
        'telepon': 'Telepon',
        'rekening': 'Rekening Bank',
        'sosmed': 'Media Sosial',
        'email': 'Email',
        'ewallet': 'E-Wallet',
        'kripto': 'Kripto',
        'marketplace': 'Marketplace',
        'website': 'Website',
        'lainnya': 'Lainnya',
    };
    return labels[jenis] || jenis;
};

const getStatusClass = (stat) => {
    const classes = {
        'Penyelidikan': 'bg-blue-100 text-blue-800',
        'Penyidikan': 'bg-indigo-100 text-indigo-800',
        'Tahap I': 'bg-yellow-100 text-yellow-800',
        'Tahap II': 'bg-orange-100 text-orange-800',
        'SP3': 'bg-red-100 text-red-800',
        'RJ': 'bg-green-100 text-green-800',
        'Diversi': 'bg-purple-100 text-purple-800',
    };
    return classes[stat] || 'bg-gray-100 text-gray-800';
};

const getHubunganPelaporLabel = (value) => {
    const labels = {
        'diri_sendiri': 'Diri Sendiri',
        'keluarga': 'Keluarga',
        'kuasa_hukum': 'Kuasa Hukum',
        'teman': 'Teman',
        'rekan_kerja': 'Rekan Kerja',
        'lainnya': 'Lainnya',
    };
    return labels[value] || value;
};

const getAlamatLengkap = (alamat) => {
    if (!alamat) return '-';
    const parts = [];
    if (alamat.detail_alamat) parts.push(alamat.detail_alamat);
    if (alamat.kelurahan?.nama) parts.push(`Kel. ${alamat.kelurahan.nama}`);
    if (alamat.kecamatan?.nama) parts.push(`Kec. ${alamat.kecamatan.nama}`);
    if (alamat.kabupaten?.nama) parts.push(alamat.kabupaten.nama);
    if (alamat.provinsi?.nama) parts.push(alamat.provinsi.nama);
    return parts.length > 0 ? parts.join(', ') : '-';
};
</script>

<template>
    <Head :title="`Laporan - ${laporan?.nomor_stpa || 'Detail'}`" />

    <SidebarLayout :title="laporan?.nomor_stpa || 'Detail Laporan'">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <Link href="/laporan" class="inline-flex items-center gap-2 text-slate-600 hover:text-slate-800 transition mb-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Kembali ke Arsip Laporan
                    </Link>
                    <h1 class="text-2xl font-bold text-slate-800">Detail Laporan</h1>
                </div>
                <div class="flex items-center gap-3">
                    <span :class="['px-4 py-2 rounded-full text-sm font-medium', getStatusClass(laporan?.status)]">
                        {{ laporan?.status }}
                    </span>
                    <a
                        :href="`/laporan/${laporan?.id}/pdf`"
                        target="_blank"
                        class="px-4 py-2 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition-colors flex items-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Cetak STPA (PDF)
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Data Administrasi -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                        <h2 class="text-lg font-semibold text-slate-800 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Data Administrasi
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="block text-slate-500 text-xs mb-1">Tanggal Laporan</span>
                                <span class="font-medium text-slate-900">{{ formatDate(laporan?.tanggal_laporan) }}</span>
                            </div>
                            <div>
                                <span class="block text-slate-500 text-xs mb-1">Nomor STPA</span>
                                <span class="font-medium font-mono text-amber-600">{{ laporan?.nomor_stpa || '(Belum dibuat)' }}</span>
                            </div>
                            <div>
                                <span class="block text-slate-500 text-xs mb-1">Kategori Kejahatan</span>
                                <span class="font-bold text-red-600">{{ laporan?.kategori_kejahatan?.nama || '-' }}</span>
                            </div>
                            <div>
                                <span class="block text-slate-500 text-xs mb-1">Kapasitas Pelapor</span>
                                <span class="font-medium text-slate-900">{{ getHubunganPelaporLabel(laporan?.hubungan_pelapor) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Pelapor -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                        <h2 class="text-lg font-semibold text-slate-800 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Data Pelapor
                            <span v-if="laporan?.hubungan_pelapor" class="ml-2 px-2 py-0.5 bg-blue-100 text-blue-700 text-xs rounded-full">
                                {{ getHubunganPelaporLabel(laporan?.hubungan_pelapor) }}
                            </span>
                        </h2>
                        
                        <div v-if="laporan?.pelapor" class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="block text-slate-500 text-xs mb-1">Kewarganegaraan</span>
                                <span class="font-medium">{{ laporan?.pelapor?.kewarganegaraan || 'WNI' }}</span>
                            </div>
                            <div v-if="laporan?.pelapor?.kewarganegaraan === 'WNA'">
                                <span class="block text-slate-500 text-xs mb-1">Negara Asal</span>
                                <span class="font-medium">{{ laporan?.pelapor?.negara_asal || '-' }}</span>
                            </div>
                            <div>
                                <span class="block text-slate-500 text-xs mb-1">{{ laporan?.pelapor?.kewarganegaraan === 'WNA' ? 'Passport/KITAS' : 'NIK' }}</span>
                                <span class="font-medium font-mono">{{ formatNik(laporan?.pelapor?.nik) }}</span>
                            </div>
                            <div>
                                <span class="block text-slate-500 text-xs mb-1">Nama Lengkap</span>
                                <span class="font-medium">{{ laporan?.pelapor?.nama || '-' }}</span>
                            </div>
                            <div v-if="laporan?.pelapor?.kewarganegaraan !== 'WNA'">
                                <span class="block text-slate-500 text-xs mb-1">Tempat Lahir</span>
                                <span class="font-medium">{{ laporan?.pelapor?.tempat_lahir || '-' }}</span>
                            </div>
                            <div>
                                <span class="block text-slate-500 text-xs mb-1">Tanggal Lahir</span>
                                <span class="font-medium">{{ laporan?.pelapor?.tanggal_lahir || '-' }}</span>
                            </div>
                            <div>
                                <span class="block text-slate-500 text-xs mb-1">Jenis Kelamin</span>
                                <span class="font-medium">{{ laporan?.pelapor?.jenis_kelamin || '-' }}</span>
                            </div>
                            <div>
                                <span class="block text-slate-500 text-xs mb-1">Pekerjaan</span>
                                <span class="font-medium">{{ laporan?.pelapor?.pekerjaan || '-' }}</span>
                            </div>
                            <div>
                                <span class="block text-slate-500 text-xs mb-1">Pendidikan Terakhir</span>
                                <span class="font-medium">{{ laporan?.pelapor?.pendidikan || '-' }}</span>
                            </div>
                            <div>
                                <span class="block text-slate-500 text-xs mb-1">Telepon</span>
                                <span class="font-medium font-mono">{{ laporan?.pelapor?.telepon || '-' }}</span>
                            </div>
                            <div class="md:col-span-2">
                                <span class="block text-slate-500 text-xs mb-1">Alamat</span>
                                <span class="font-medium">{{ getAlamatLengkap(laporan?.pelapor?.alamat_ktp) }}</span>
                            </div>
                        </div>
                        <p v-else class="text-slate-500">Data pelapor tidak tersedia</p>
                    </div>

                    <!-- Korban -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                        <h2 class="text-lg font-semibold text-slate-800 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            Data Korban ({{ laporan?.korban?.length || 0 }})
                        </h2>
                        
                        <div v-if="laporan?.korban?.length > 0" class="space-y-4">
                            <div v-for="(korban, idx) in laporan?.korban" :key="idx" class="border border-slate-200 rounded-lg p-4">
                                <div class="flex items-start justify-between mb-3">
                                    <div>
                                        <div class="font-bold text-slate-800">{{ korban.orang?.nama || 'Korban ' + (idx + 1) }}</div>
                                        <div class="text-xs text-slate-500 mt-1">
                                            <span v-if="korban.orang?.kewarganegaraan">{{ korban.orang.kewarganegaraan }}</span>
                                            <span v-if="korban.orang?.negara_asal" class="ml-1">({{ korban.orang.negara_asal }})</span>
                                        </div>
                                    </div>
                                    <span class="text-red-600 font-bold text-base">{{ formatRupiah(korban.kerugian_nominal) }}</span>
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-sm text-slate-600">
                                    <div v-if="korban.orang?.nik">
                                        <span class="text-slate-500">NIK:</span> 
                                        <span class="font-mono">{{ formatNik(korban.orang.nik) }}</span>
                                    </div>
                                    <div v-if="korban.orang?.telepon">
                                        <span class="text-slate-500">Telepon:</span> {{ korban.orang.telepon }}
                                    </div>
                                    <div v-if="korban.orang?.jenis_kelamin">
                                        <span class="text-slate-500">Jenis Kelamin:</span> {{ korban.orang.jenis_kelamin }}
                                    </div>
                                    <div v-if="korban.orang?.pekerjaan">
                                        <span class="text-slate-500">Pekerjaan:</span> {{ korban.orang.pekerjaan }}
                                    </div>
                                    <div v-if="korban.orang?.pendidikan">
                                        <span class="text-slate-500">Pendidikan:</span> {{ korban.orang.pendidikan }}
                                    </div>
                                </div>
                                <div v-if="korban.keterangan" class="mt-2 pt-2 border-t border-slate-100 text-sm text-slate-600">
                                    <span class="text-slate-500">Keterangan:</span> {{ korban.keterangan }}
                                </div>
                            </div>
                            <!-- Total Kerugian -->
                            <div class="mt-4 pt-4 border-t-2 border-slate-300 flex justify-between text-base font-bold">
                                <span class="text-slate-700">Total Kerugian:</span>
                                <span class="text-red-600">{{ formatRupiah(getTotalKerugian) }}</span>
                            </div>
                        </div>
                        <p v-else class="text-slate-500">Tidak ada data korban</p>
                    </div>

                    <!-- Tersangka -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                        <h2 class="text-lg font-semibold text-slate-800 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                            </svg>
                            Data Tersangka ({{ laporan?.tersangka?.length || 0 }})
                        </h2>
                        
                        <div v-if="laporan?.tersangka?.length > 0" class="space-y-3">
                            <div v-for="(tersangka, idx) in laporan?.tersangka" :key="idx" class="border-b border-slate-100 pb-3 last:border-0 last:pb-0">
                                <div class="text-xs text-red-500 font-bold mb-2">Tersangka {{ idx + 1 }}</div>
                                
                                <!-- ALERT RESIDIVIS -->
                                <div v-if="hasTrackRecord(tersangka.id)" class="mb-3 p-3 bg-red-50 border border-red-200 rounded-lg">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="relative flex h-3 w-3">
                                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                                <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                                            </span>
                                            <span class="text-red-700 font-bold text-sm">
                                                ⚠️ RESIDIVIS - Terdeteksi di {{ getTrackRecordCount(tersangka.id) }} Kasus Lain
                                            </span>
                                        </div>
                                        <button 
                                            @click="toggleTrackRecord(tersangka.id)"
                                            class="text-xs text-red-600 hover:text-red-800 font-medium flex items-center gap-1"
                                        >
                                            {{ expandedTrackRecord[tersangka.id] ? 'Tutup' : 'Lihat Riwayat Perkara' }}
                                            <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': expandedTrackRecord[tersangka.id] }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                    </div>
                                    
                                    <!-- Accordion Content -->
                                    <div v-if="expandedTrackRecord[tersangka.id]" class="mt-3 pt-3 border-t border-red-200 space-y-2">
                                        <div v-for="(match, mIdx) in trackRecord[tersangka.id]" :key="mIdx" class="p-2 bg-white rounded border border-red-100 text-sm">
                                            <div class="flex items-start gap-2">
                                                <span class="text-red-500">•</span>
                                                <div class="flex-1">
                                                    <div class="font-medium text-slate-800">
                                                        {{ match.jenis_label }}: <span class="font-mono">{{ match.nilai }}</span>
                                                        <span v-if="match.platform" class="text-slate-500 text-xs">({{ match.platform }})</span>
                                                    </div>
                                                    <div class="text-xs text-slate-600 mt-1">
                                                        Perkara: <span class="font-mono text-amber-600">{{ match.nomor_stpa || 'Belum ada STPA' }}</span>
                                                        <span class="text-slate-400 mx-1">|</span>
                                                        <span class="font-medium text-blue-600">{{ match.subdit }}</span>
                                                        <span class="text-slate-400 mx-1">|</span>
                                                        Status: <span class="font-medium">{{ match.status }}</span>
                                                        <span class="text-slate-400 mx-1">|</span>
                                                        {{ match.tanggal_laporan }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Identitas Digital Tersangka -->
                                <div v-if="tersangka.identitas?.length > 0" class="space-y-1 ml-3">
                                    <div v-for="(id, idIdx) in tersangka.identitas" :key="idIdx" class="text-sm text-slate-700">
                                        • <span class="font-semibold">{{ getIdentitasLabel(id.jenis) }}:</span> 
                                        <span class="font-mono">{{ id.nilai }}</span>
                                        <span v-if="id.platform" class="text-xs text-slate-500 ml-1">({{ id.platform }})</span>
                                        <span v-if="id.nama_akun" class="text-xs text-slate-500 ml-1">- {{ id.nama_akun }}</span>
                                    </div>
                                </div>
                                <div v-else class="text-sm text-slate-400 italic ml-3">Tidak ada identitas digital</div>
                                <div v-if="tersangka.catatan" class="mt-2 text-xs text-slate-600 italic ml-3">
                                    Catatan: "{{ tersangka.catatan }}"
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-slate-500">Tidak ada data tersangka</p>
                    </div>

                    <!-- Data Kejadian -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                        <h2 class="text-lg font-semibold text-slate-800 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            Data Kejadian
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="block text-slate-500 text-xs mb-1">Waktu Kejadian</span>
                                <span class="font-medium">{{ formatDateTime(laporan?.waktu_kejadian) }}</span>
                            </div>
                            <div class="md:col-span-2">
                                <span class="block text-slate-500 text-xs mb-1">Lokasi Kejadian</span>
                                <span class="font-medium">
                                    {{ laporan?.alamat_kejadian || '' }}{{ laporan?.alamat_kejadian && laporan?.kabupaten_kejadian?.nama ? ', ' : '' }}{{ laporan?.kabupaten_kejadian?.nama || '' }}{{ laporan?.provinsi_kejadian?.nama ? ', ' + laporan?.provinsi_kejadian?.nama : '' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Modus Operandi -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                        <h2 class="text-lg font-semibold text-slate-800 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                            </svg>
                            Modus Operandi
                        </h2>
                        
                        <div class="text-sm text-slate-700 leading-relaxed whitespace-pre-wrap bg-slate-50 p-3 rounded-lg italic">
                            "{{ laporan?.modus || '-' }}"
                        </div>
                        <div class="mt-2 text-xs text-slate-500">
                            {{ laporan?.modus?.length || 0 }} karakter
                        </div>
                    </div>

                    <!-- Lampiran -->
                    <div v-if="laporan?.lampiran?.length > 0" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                        <h2 class="text-lg font-semibold text-slate-800 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                            </svg>
                            Lampiran ({{ laporan?.lampiran?.length }})
                        </h2>
                        
                        <div class="space-y-2">
                            <div v-for="(file, idx) in laporan?.lampiran" :key="idx" class="flex items-center gap-3 p-3 border border-slate-200 rounded-lg">
                                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-slate-800">{{ file.nama_file }}</p>
                                    <p class="text-xs text-slate-500">{{ file.jenis }}</p>
                                </div>
                                <a :href="`/storage/${file.path_file}`" target="_blank" class="text-blue-500 hover:text-blue-700 text-sm">
                                    Lihat
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Summary Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                        <h2 class="text-lg font-semibold text-slate-800 mb-4">Ringkasan</h2>
                        
                        <div class="space-y-3">
                            <div class="flex justify-between items-center py-2 border-b border-slate-100">
                                <span class="text-slate-500">Total Korban</span>
                                <span class="font-semibold text-slate-800">{{ laporan?.korban?.length || 0 }} orang</span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-slate-100">
                                <span class="text-slate-500">Total Tersangka</span>
                                <span class="font-semibold text-slate-800">{{ laporan?.tersangka?.length || 0 }} orang</span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-slate-100">
                                <span class="text-slate-500">Lampiran</span>
                                <span class="font-semibold text-slate-800">{{ laporan?.lampiran?.length || 0 }} file</span>
                            </div>
                            <div class="flex justify-between items-center py-2">
                                <span class="text-slate-500">Total Kerugian</span>
                                <span class="font-semibold text-red-600">{{ formatRupiah(getTotalKerugian) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Audit Info -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                        <h2 class="text-lg font-semibold text-slate-800 mb-4">Informasi</h2>
                        
                        <div class="space-y-3 text-sm">
                            <div>
                                <span class="block text-slate-500 text-xs mb-1">Dibuat pada</span>
                                <span class="font-medium text-slate-900">{{ formatDateTime(laporan?.created_at) }}</span>
                            </div>
                            <div v-if="laporan?.assigned_subdit">
                                <span class="block text-slate-500 text-xs mb-1">Ditugaskan ke</span>
                                <span class="font-medium text-slate-900">Subdit {{ laporan?.assigned_subdit }}</span>
                            </div>
                            <div v-if="laporan?.assigned_by">
                                <span class="block text-slate-500 text-xs mb-1">Ditugaskan oleh</span>
                                <span class="font-medium text-slate-900">{{ laporan?.assigned_by?.name || '-' }}</span>
                            </div>
                            <div v-if="laporan?.assigned_at">
                                <span class="block text-slate-500 text-xs mb-1">Ditugaskan pada</span>
                                <span class="font-medium text-slate-900">{{ formatDateTime(laporan?.assigned_at) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </SidebarLayout>
</template>
