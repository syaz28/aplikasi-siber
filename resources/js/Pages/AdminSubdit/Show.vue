<script setup>
import SidebarLayout from '@/Layouts/SidebarLayout.vue';
import ToastContainer from '@/Components/ToastContainer.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    laporan: Object,
    trackRecord: Object,
    statusOptions: Object,
    unitOptions: Array,
});

const toast = useToast();
const page = usePage();

// Track record accordion state (per suspect)
const expandedTrackRecord = ref({});

const toggleTrackRecord = (suspectId) => {
    expandedTrackRecord.value[suspectId] = !expandedTrackRecord.value[suspectId];
};

const hasTrackRecord = (suspectId) => {
    return props.trackRecord && props.trackRecord[suspectId] && props.trackRecord[suspectId].length > 0;
};

const getTrackRecordCount = (suspectId) => {
    if (!props.trackRecord || !props.trackRecord[suspectId]) return 0;
    // Count unique cases
    const uniqueCases = new Set(props.trackRecord[suspectId].map(m => m.related_case.id));
    return uniqueCases.size;
};

onMounted(() => {
    if (page.props.flash?.success) {
        toast.success(page.props.flash.success);
    }
    if (page.props.flash?.error) {
        toast.error(page.props.flash.error);
    }
});

// Control panel states
const selectedUnit = ref(props.laporan?.disposisi_unit || '');
const selectedStatus = ref(props.laporan?.status || 'Penyelidikan');
const savingUnit = ref(false);
const savingStatus = ref(false);

const updateUnit = () => {
    if (!selectedUnit.value) return;
    savingUnit.value = true;
    
    router.patch(`/min-ops/kasus/${props.laporan.id}/unit`, {
        disposisi_unit: selectedUnit.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(`Unit berhasil diperbarui ke Unit ${selectedUnit.value}`);
            savingUnit.value = false;
        },
        onError: () => {
            toast.error('Gagal memperbarui unit');
            savingUnit.value = false;
        }
    });
};

const updateStatus = () => {
    savingStatus.value = true;
    
    router.patch(`/min-ops/kasus/${props.laporan.id}/status`, {
        status: selectedStatus.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(`Status berhasil diperbarui ke ${getStatusLabel(selectedStatus.value)}`);
            savingStatus.value = false;
        },
        onError: () => {
            toast.error('Gagal memperbarui status');
            savingStatus.value = false;
        }
    });
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
        'Penyelidikan': 'bg-blue-100 text-blue-800 border-blue-300',
        'Penyidikan': 'bg-indigo-100 text-indigo-800 border-indigo-300',
        'Tahap I': 'bg-yellow-100 text-yellow-800 border-yellow-300',
        'Tahap II': 'bg-orange-100 text-orange-800 border-orange-300',
        'SP3': 'bg-red-100 text-red-800 border-red-300',
        'RJ': 'bg-green-100 text-green-800 border-green-300',
        'Diversi': 'bg-purple-100 text-purple-800 border-purple-300',
    };
    return classes[stat] || 'bg-gray-100 text-gray-800 border-gray-300';
};

const getStatusLabel = (stat) => {
    const labels = {
        'Penyelidikan': 'Penyelidikan',
        'Penyidikan': 'Penyidikan',
        'Tahap I': 'Tahap I',
        'Tahap II': 'Tahap II',
        'SP3': 'SP3',
        'RJ': 'Restorative Justice',
        'Diversi': 'Diversi',
    };
    return labels[stat] || stat;
};

const getUnitClass = (unit) => {
    const classes = {
        1: 'bg-emerald-100 text-emerald-800 border-emerald-300',
        2: 'bg-sky-100 text-sky-800 border-sky-300',
        3: 'bg-amber-100 text-amber-800 border-amber-300',
        4: 'bg-rose-100 text-rose-800 border-rose-300',
        5: 'bg-violet-100 text-violet-800 border-violet-300',
    };
    return classes[unit] || 'bg-gray-100 text-gray-800 border-gray-300';
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
    <Head :title="`Kasus - ${laporan.nomor_stpa || 'Detail'}`" />

    <SidebarLayout :title="laporan.nomor_stpa || 'Detail Kasus'">
        <ToastContainer />
        
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <Link href="/min-ops" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-800 transition mb-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Kembali ke Daftar Kasus
                    </Link>
                    <h1 class="text-2xl font-bold text-navy">Detail Kasus</h1>
                </div>
                <div class="flex items-center gap-2">
                    <span v-if="laporan.disposisi_unit" :class="['px-3 py-1.5 rounded-lg text-sm font-medium border', getUnitClass(laporan.disposisi_unit)]">
                        Unit {{ laporan.disposisi_unit }}
                    </span>
                    <span :class="['px-3 py-1.5 rounded-full text-sm font-medium', getStatusClass(laporan.status)]">
                        {{ getStatusLabel(laporan.status) }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content (Left Column) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Data Administrasi -->
                    <div class="bg-white rounded-xl shadow-tactical border border-tactical-border p-6">
                        <h2 class="text-lg font-semibold text-navy mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Data Administrasi
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="block text-gray-500 text-xs mb-1">Tanggal Laporan</span>
                                <span class="font-medium text-gray-900">{{ formatDate(laporan.tanggal_laporan) }}</span>
                            </div>
                            <div>
                                <span class="block text-gray-500 text-xs mb-1">Nomor STPA</span>
                                <span class="font-medium font-mono text-tactical-accent">{{ laporan.nomor_stpa || '(Belum dibuat)' }}</span>
                            </div>
                            <div>
                                <span class="block text-gray-500 text-xs mb-1">Kategori Kejahatan</span>
                                <span class="font-medium text-gray-900">{{ laporan.kategori_kejahatan?.nama || '-' }}</span>
                            </div>
                            <div>
                                <span class="block text-gray-500 text-xs mb-1">Kapasitas Pelapor</span>
                                <span class="font-medium text-gray-900">{{ getHubunganPelaporLabel(laporan.hubungan_pelapor) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Pelapor -->
                    <div class="bg-white rounded-xl shadow-tactical border border-tactical-border p-6">
                        <h2 class="text-lg font-semibold text-navy mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Data Pelapor
                            <span v-if="laporan.hubungan_pelapor" class="ml-2 px-2 py-0.5 bg-blue-100 text-blue-700 text-xs rounded-full">
                                {{ getHubunganPelaporLabel(laporan.hubungan_pelapor) }}
                            </span>
                        </h2>
                        
                        <div v-if="laporan.pelapor" class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="block text-gray-500 text-xs mb-1">Kewarganegaraan</span>
                                <span class="font-medium text-gray-900">{{ laporan.pelapor.kewarganegaraan || 'WNI' }}</span>
                            </div>
                            <div v-if="laporan.pelapor.kewarganegaraan === 'WNA'">
                                <span class="block text-gray-500 text-xs mb-1">Negara Asal</span>
                                <span class="font-medium text-gray-900">{{ laporan.pelapor.negara_asal || '-' }}</span>
                            </div>
                            <div>
                                <span class="block text-gray-500 text-xs mb-1">{{ laporan.pelapor.kewarganegaraan === 'WNA' ? 'Passport/KITAS' : 'NIK' }}</span>
                                <span class="font-medium text-gray-900 font-mono">{{ formatNik(laporan.pelapor.nik) }}</span>
                            </div>
                            <div>
                                <span class="block text-gray-500 text-xs mb-1">Nama Lengkap</span>
                                <span class="font-medium text-gray-900">{{ laporan.pelapor.nama || '-' }}</span>
                            </div>
                            <div v-if="laporan.pelapor.kewarganegaraan !== 'WNA'">
                                <span class="block text-gray-500 text-xs mb-1">Tempat Lahir</span>
                                <span class="font-medium text-gray-900">{{ laporan.pelapor.tempat_lahir || '-' }}</span>
                            </div>
                            <div>
                                <span class="block text-gray-500 text-xs mb-1">Tanggal Lahir</span>
                                <span class="font-medium text-gray-900">{{ laporan.pelapor.tanggal_lahir || '-' }}</span>
                            </div>
                            <div>
                                <span class="block text-gray-500 text-xs mb-1">Jenis Kelamin</span>
                                <span class="font-medium text-gray-900">{{ laporan.pelapor.jenis_kelamin || '-' }}</span>
                            </div>
                            <div>
                                <span class="block text-gray-500 text-xs mb-1">Pekerjaan</span>
                                <span class="font-medium text-gray-900">{{ laporan.pelapor.pekerjaan || '-' }}</span>
                            </div>
                            <div>
                                <span class="block text-gray-500 text-xs mb-1">Pendidikan Terakhir</span>
                                <span class="font-medium text-gray-900">{{ laporan.pelapor.pendidikan || '-' }}</span>
                            </div>
                            <div>
                                <span class="block text-gray-500 text-xs mb-1">Telepon</span>
                                <span class="font-medium text-gray-900 font-mono">{{ laporan.pelapor.telepon || '-' }}</span>
                            </div>
                            <div class="md:col-span-2">
                                <span class="block text-gray-500 text-xs mb-1">Alamat</span>
                                <span class="font-medium text-gray-900">{{ getAlamatLengkap(laporan.pelapor.alamat_ktp) }}</span>
                            </div>
                        </div>
                        <div v-else class="text-gray-500 text-sm italic">Data pelapor tidak tersedia</div>
                    </div>

                    <!-- Data Kejadian -->
                    <div class="bg-white rounded-xl shadow-tactical border border-tactical-border p-6">
                        <h2 class="text-lg font-semibold text-navy mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            Data Kejadian
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="block text-gray-500 text-xs mb-1">Waktu Kejadian</span>
                                <span class="font-medium text-gray-900">{{ formatDateTime(laporan.waktu_kejadian) }}</span>
                            </div>
                            <div>
                                <span class="block text-gray-500 text-xs mb-1">Lokasi</span>
                                <span class="font-medium text-gray-900">{{ laporan.kabupaten_kejadian?.nama || '-' }}, {{ laporan.provinsi_kejadian?.nama || '-' }}</span>
                            </div>
                            <div class="md:col-span-2">
                                <span class="block text-gray-500 text-xs mb-1">Alamat Kejadian</span>
                                <span class="font-medium text-gray-900">{{ laporan.alamat_kejadian || '-' }}</span>
                            </div>
                            <div class="md:col-span-2">
                                <span class="block text-gray-500 text-xs mb-1">Modus Operandi</span>
                                <p class="font-medium text-gray-900 whitespace-pre-wrap">{{ laporan.modus || '-' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Data Korban -->
                    <div class="bg-white rounded-xl shadow-tactical border border-tactical-border p-6">
                        <h2 class="text-lg font-semibold text-navy mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            Data Korban ({{ laporan.korban?.length || 0 }})
                        </h2>
                        
                        <div v-if="laporan.korban?.length > 0" class="space-y-4">
                            <div v-for="(korban, idx) in laporan.korban" :key="idx" class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-start justify-between mb-3">
                                    <div>
                                        <div class="font-bold text-gray-800">{{ korban.orang?.nama || 'Korban ' + (idx + 1) }}</div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            <span v-if="korban.orang?.kewarganegaraan">{{ korban.orang.kewarganegaraan }}</span>
                                            <span v-if="korban.orang?.negara_asal" class="ml-1">({{ korban.orang.negara_asal }})</span>
                                        </div>
                                    </div>
                                    <span class="text-red-600 font-bold text-base">{{ formatRupiah(korban.kerugian_nominal) }}</span>
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-sm text-gray-600">
                                    <div v-if="korban.orang?.nik">
                                        <span class="text-gray-500">NIK:</span> 
                                        <span class="font-mono">{{ formatNik(korban.orang.nik) }}</span>
                                    </div>
                                    <div v-if="korban.orang?.telepon">
                                        <span class="text-gray-500">Telepon:</span> {{ korban.orang.telepon }}
                                    </div>
                                    <div v-if="korban.orang?.jenis_kelamin">
                                        <span class="text-gray-500">Jenis Kelamin:</span> {{ korban.orang.jenis_kelamin }}
                                    </div>
                                    <div v-if="korban.orang?.pekerjaan">
                                        <span class="text-gray-500">Pekerjaan:</span> {{ korban.orang.pekerjaan }}
                                    </div>
                                    <div v-if="korban.orang?.pendidikan">
                                        <span class="text-gray-500">Pendidikan:</span> {{ korban.orang.pendidikan }}
                                    </div>
                                </div>
                                <div v-if="korban.keterangan" class="mt-2 pt-2 border-t border-gray-100 text-sm text-gray-600">
                                    <span class="text-gray-500">Keterangan:</span> {{ korban.keterangan }}
                                </div>
                            </div>
                            <!-- Total Kerugian -->
                            <div class="mt-4 pt-4 border-t-2 border-gray-300 flex justify-between text-base font-bold">
                                <span class="text-gray-700">Total Kerugian:</span>
                                <span class="text-red-600">{{ formatRupiah(getTotalKerugian) }}</span>
                            </div>
                        </div>
                        <div v-else class="text-gray-500 text-sm italic">Belum ada data korban</div>
                    </div>

                    <!-- Data Tersangka -->
                    <div class="bg-white rounded-xl shadow-tactical border border-tactical-border p-6">
                        <h2 class="text-lg font-semibold text-navy mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                            </svg>
                            Data Tersangka ({{ laporan.tersangka?.length || 0 }})
                        </h2>
                        
                        <div v-if="laporan.tersangka?.length > 0" class="space-y-4">
                            <div v-for="(tersangka, idx) in laporan.tersangka" :key="tersangka.id || idx" class="border border-gray-200 rounded-lg p-4">
                                <!-- Header with Alert Badge -->
                                <div class="flex items-center justify-between mb-3">
                                    <div class="text-sm text-red-600 font-bold">Tersangka {{ idx + 1 }}</div>
                                    
                                    <!-- RESIDIVIS ALERT BADGE -->
                                    <div v-if="hasTrackRecord(tersangka.id)" class="flex items-center gap-2">
                                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-red-100 text-red-700 text-xs font-bold rounded-full border border-red-300 animate-pulse">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                            RESIDIVIS - Terdeteksi di {{ getTrackRecordCount(tersangka.id) }} Kasus Lain
                                        </span>
                                    </div>
                                </div>

                                <!-- Identitas Digital Tersangka -->
                                <div v-if="tersangka.identitas?.length > 0" class="space-y-1 ml-3 mb-3">
                                    <div v-for="(id, idIdx) in tersangka.identitas" :key="idIdx" class="text-sm text-gray-700">
                                        • <span class="font-semibold">{{ getIdentitasLabel(id.jenis) }}:</span> 
                                        <span class="font-mono">{{ id.nilai }}</span>
                                        <span v-if="id.platform" class="text-xs text-gray-500 ml-1">({{ id.platform }})</span>
                                        <span v-if="id.nama_akun" class="text-xs text-gray-500 ml-1">- {{ id.nama_akun }}</span>
                                    </div>
                                </div>
                                <div v-else class="text-sm text-gray-400 italic ml-3 mb-3">Tidak ada identitas digital</div>
                                
                                <div v-if="tersangka.catatan" class="text-xs text-gray-600 italic ml-3 mb-3">
                                    Catatan: "{{ tersangka.catatan }}"
                                </div>

                                <!-- TRACK RECORD ACCORDION -->
                                <div v-if="hasTrackRecord(tersangka.id)" class="mt-3 border-t border-red-200 pt-3">
                                    <!-- Toggle Button -->
                                    <button 
                                        @click="toggleTrackRecord(tersangka.id)"
                                        class="w-full flex items-center justify-between px-3 py-2 bg-red-50 hover:bg-red-100 rounded-lg text-sm font-medium text-red-700 transition-colors"
                                    >
                                        <span class="flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                            Lihat Riwayat Perkara ({{ trackRecord[tersangka.id]?.length || 0 }} kecocokan)
                                        </span>
                                        <svg 
                                            class="w-4 h-4 transition-transform duration-200" 
                                            :class="{ 'rotate-180': expandedTrackRecord[tersangka.id] }"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>

                                    <!-- Expanded Content -->
                                    <div v-if="expandedTrackRecord[tersangka.id]" class="mt-3 space-y-2">
                                        <div class="text-xs font-bold text-red-600 uppercase tracking-wider mb-2">
                                            🔍 Identitas Digital yang Cocok:
                                        </div>
                                        
                                        <div 
                                            v-for="(match, matchIdx) in trackRecord[tersangka.id]" 
                                            :key="matchIdx"
                                            class="p-3 bg-red-50 border border-red-200 rounded-lg"
                                        >
                                            <!-- Match Info -->
                                            <div class="flex items-start gap-2 mb-2">
                                                <span class="text-red-500 font-bold">🔴</span>
                                                <div class="flex-1">
                                                    <span class="font-semibold text-red-800">{{ match.jenis_label }}:</span>
                                                    <span class="font-mono text-red-700 ml-1">{{ match.nilai }}</span>
                                                    <span v-if="match.platform" class="text-xs text-red-500 ml-1">({{ match.platform }})</span>
                                                </div>
                                            </div>
                                            
                                            <!-- Related Case Details -->
                                            <div class="ml-5 text-xs space-y-1 text-red-700">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-red-500">↳</span>
                                                    <span>Juga ditemukan di:</span>
                                                    <a 
                                                        :href="`/min-ops/kasus/${match.laporan_id}`"
                                                        class="font-bold underline hover:text-red-900"
                                                    >
                                                        {{ match.nomor_stpa }}
                                                    </a>
                                                </div>
                                                <div class="ml-4 grid grid-cols-3 gap-x-4 gap-y-1 text-red-600">
                                                    <div>
                                                        <span class="text-red-500">Subdit:</span> 
                                                        <span class="font-medium">{{ match.subdit }}</span>
                                                    </div>
                                                    <div>
                                                        <span class="text-red-500">Status:</span> 
                                                        <span class="font-medium">{{ match.status }}</span>
                                                    </div>
                                                    <div>
                                                        <span class="text-red-500">Tanggal:</span> 
                                                        <span class="font-medium">{{ match.tanggal_laporan }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-gray-500 text-sm italic">Belum ada data tersangka</div>
                    </div>

                    <!-- Modus Operandi -->
                    <div class="bg-white rounded-xl shadow-tactical border border-tactical-border p-6">
                        <h2 class="text-lg font-semibold text-navy mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                            </svg>
                            Modus Operandi
                        </h2>
                        
                        <div class="text-sm text-gray-700 leading-relaxed whitespace-pre-wrap bg-gray-50 p-3 rounded-lg italic">
                            "{{ laporan.modus || '-' }}"
                        </div>
                        <div class="mt-2 text-xs text-gray-500">
                            {{ laporan.modus?.length || 0 }} karakter
                        </div>
                    </div>
                </div>

                <!-- Sidebar (Right Column) - Control Panel -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Quick Actions -->
                    <div class="bg-white rounded-xl shadow-tactical border border-tactical-border p-6">
                        <h3 class="text-lg font-semibold text-navy mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-tactical-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Kontrol Kasus
                        </h3>
                        
                        <!-- Unit Disposisi -->
                        <div class="mb-5">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Disposisi Unit</label>
                            <div class="flex gap-2">
                                <select
                                    v-model="selectedUnit"
                                    class="flex-1 rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent text-sm"
                                >
                                    <option value="">Pilih Unit</option>
                                    <option v-for="unit in unitOptions" :key="unit" :value="unit">
                                        Unit {{ unit }}
                                    </option>
                                </select>
                                <button
                                    @click="updateUnit"
                                    :disabled="!selectedUnit || savingUnit"
                                    class="px-4 py-2 bg-tactical-accent text-white rounded-lg hover:bg-blue-600 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors text-sm font-medium"
                                >
                                    <span v-if="savingUnit">...</span>
                                    <span v-else>Simpan</span>
                                </button>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="mb-5">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status Kasus</label>
                            <div class="flex gap-2">
                                <select
                                    v-model="selectedStatus"
                                    class="flex-1 rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent text-sm"
                                >
                                    <option v-for="(label, value) in statusOptions" :key="value" :value="value">
                                        {{ label }}
                                    </option>
                                </select>
                                <button
                                    @click="updateStatus"
                                    :disabled="savingStatus"
                                    class="px-4 py-2 bg-tactical-accent text-white rounded-lg hover:bg-blue-600 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors text-sm font-medium"
                                >
                                    <span v-if="savingStatus">...</span>
                                    <span v-else>Simpan</span>
                                </button>
                            </div>
                        </div>

                        <!-- Download PDF -->
                        <div class="pt-4 border-t border-gray-200">
                            <a
                                :href="`/laporan/${laporan.id}/pdf`"
                                target="_blank"
                                class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors text-sm font-medium"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Download PDF
                            </a>
                        </div>
                    </div>

                    <!-- Info Panel -->
                    <div class="bg-white rounded-xl shadow-tactical border border-tactical-border p-6">
                        <h3 class="text-lg font-semibold text-navy mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Informasi
                        </h3>
                        
                        <div class="space-y-3 text-sm">
                            <div>
                                <span class="block text-gray-500 text-xs mb-1">Dibuat pada</span>
                                <span class="font-medium text-gray-900">{{ formatDateTime(laporan.created_at) }}</span>
                            </div>
                            <div v-if="laporan.assigned_at">
                                <span class="block text-gray-500 text-xs mb-1">Ditugaskan pada</span>
                                <span class="font-medium text-gray-900">{{ formatDateTime(laporan.assigned_at) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Lampiran -->
                    <div v-if="laporan.lampiran?.length" class="bg-white rounded-xl shadow-tactical border border-tactical-border p-6">
                        <h3 class="text-lg font-semibold text-navy mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                            </svg>
                            Lampiran ({{ laporan.lampiran.length }})
                        </h3>
                        
                        <div class="space-y-2">
                            <a
                                v-for="lamp in laporan.lampiran"
                                :key="lamp.id"
                                :href="`/storage/${lamp.path}`"
                                target="_blank"
                                class="flex items-center gap-2 p-2 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors text-sm"
                            >
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                <span class="truncate text-gray-700">{{ lamp.nama_file || lamp.path }}</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </SidebarLayout>
</template>
