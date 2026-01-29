<script setup>
import { computed } from 'vue';
import { formatUtils } from '@/Composables/useFormStorage';

const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    masterData: {
        type: Object,
        default: () => ({})
    }
});

const emit = defineEmits(['confirm', 'back']);

// Helper function to find name by ID or kode
const find = (arr, id, key = 'nama') => {
    if (!arr || !id) return '-';
    const item = arr.find(x => x.id == id || x.kode == id || x.nama == id);
    return item ? item[key] : '-';
};

// Today's date
const today = computed(() => {
    return new Date().toLocaleDateString('id-ID', { 
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
});

// Kategori Kejahatan
const kategori = computed(() => find(props.masterData?.kategori_kejahatan, props.form.kategori_kejahatan_id));

// Kabupaten Kejadian
const kabKejadian = computed(() => find(props.masterData?.kabupaten_all, props.form.kode_kabupaten_kejadian));

// Format date
const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Total kerugian
const totalKerugian = computed(() => {
    return props.form.korban?.reduce((sum, k) => sum + (parseInt(k.kerugian_nominal) || 0), 0) || 0;
});

// Hubungan Pelapor label
const hubunganPelaporLabel = computed(() => {
    const mapping = {
        'diri_sendiri': 'Diri Sendiri',
        'keluarga': 'Keluarga / Kerabat',
        'kuasa_hukum': 'Kuasa Hukum / Pengacara',
        'rekan_kerja': 'Rekan Kerja / Perusahaan',
        'lainnya': 'Lainnya'
    };
    return mapping[props.form.hubungan_pelapor] || props.form.hubungan_pelapor || '-';
});
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="bg-tactical-accent/10 border border-tactical-accent/30 rounded-lg p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-tactical-accent rounded-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-navy text-lg">Review Data Laporan</h3>
                    <p class="text-sm text-gray-600">Periksa kembali data sebelum menyimpan</p>
                </div>
            </div>
        </div>

        <!-- Data Administrasi -->
        <section class="bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm">
            <div class="bg-gray-50 px-4 py-2 border-b font-bold text-gray-700">📄 Data Administrasi</div>
            <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="block text-gray-500 text-xs mb-1">Tanggal Laporan</span>
                    <span class="font-medium text-gray-900">{{ today }}</span>
                </div>
                <div>
                    <span class="block text-gray-500 text-xs mb-1">Nomor STPA</span>
                    <span class="font-medium text-tactical-accent">{{ form.nomor_stpa || '(Auto-generate)' }}</span>
                </div>
                <div class="col-span-2">
                    <span class="block text-gray-500 text-xs mb-1">Kapasitas Pelapor</span>
                    <span class="font-medium text-gray-900">{{ hubunganPelaporLabel }}</span>
                </div>
            </div>
        </section>

        <!-- Data Pelapor -->
        <section class="bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm">
            <div class="bg-gray-50 px-4 py-2 border-b font-bold text-gray-700">👤 Data Pelapor</div>
            <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="block text-gray-500 text-xs mb-1">Kewarganegaraan</span>
                    <span class="font-medium">{{ form.pelapor?.kewarganegaraan || 'WNI' }}</span>
                </div>
                <div v-if="form.pelapor?.kewarganegaraan === 'WNA'">
                    <span class="block text-gray-500 text-xs mb-1">Negara Asal</span>
                    <span class="font-medium">{{ form.pelapor?.negara_asal || '-' }}</span>
                </div>
                <div>
                    <span class="block text-gray-500 text-xs mb-1">{{ form.pelapor?.kewarganegaraan === 'WNA' ? 'Passport/KITAS' : 'NIK' }}</span>
                    <span class="font-medium font-mono">{{ formatUtils.formatNik(form.pelapor?.nik) || '-' }}</span>
                </div>
                <div>
                    <span class="block text-gray-500 text-xs mb-1">Nama Lengkap</span>
                    <span class="font-medium">{{ form.pelapor?.nama || '-' }}</span>
                </div>
                <div v-if="form.pelapor?.kewarganegaraan === 'WNI'">
                    <span class="block text-gray-500 text-xs mb-1">Tempat Lahir</span>
                    <span class="font-medium">{{ form.pelapor?.tempat_lahir || '-' }}</span>
                </div>
                <div>
                    <span class="block text-gray-500 text-xs mb-1">Tanggal Lahir</span>
                    <span class="font-medium">{{ form.pelapor?.tanggal_lahir || '-' }}</span>
                </div>
                <div>
                    <span class="block text-gray-500 text-xs mb-1">Jenis Kelamin</span>
                    <span class="font-medium">{{ form.pelapor?.jenis_kelamin || '-' }}</span>
                </div>
                <div>
                    <span class="block text-gray-500 text-xs mb-1">Pekerjaan</span>
                    <span class="font-medium">{{ form.pelapor?.pekerjaan || '-' }}</span>
                </div>
                <div>
                    <span class="block text-gray-500 text-xs mb-1">Pendidikan Terakhir</span>
                    <span class="font-medium">{{ form.pelapor?.pendidikan || '-' }}</span>
                </div>
                <div>
                    <span class="block text-gray-500 text-xs mb-1">Telepon</span>
                    <span class="font-medium font-mono">{{ form.pelapor?.telepon || '-' }}</span>
                </div>
                <div class="col-span-2">
                    <span class="block text-gray-500 text-xs mb-1">Alamat</span>
                    <span class="font-medium">{{ form.pelapor?.alamat_ktp?.detail_alamat || '-' }}</span>
                </div>
            </div>
        </section>

        <!-- Data Kejadian -->
        <section class="bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm">
            <div class="bg-gray-50 px-4 py-2 border-b font-bold text-gray-700">⚠️ Data Kejadian</div>
            <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="block text-gray-500 text-xs mb-1">Kategori Kejahatan</span>
                    <span class="font-bold text-red-600">{{ kategori }}</span>
                </div>
                <div>
                    <span class="block text-gray-500 text-xs mb-1">Waktu Kejadian</span>
                    <span class="font-medium">{{ formatDate(form.waktu_kejadian) }}</span>
                </div>
                <div class="col-span-2">
                    <span class="block text-gray-500 text-xs mb-1">Lokasi Kejadian</span>
                    <span class="font-medium">{{ form.alamat_kejadian || '-' }}, {{ kabKejadian }}</span>
                </div>
            </div>
        </section>

        <!-- Data Korban -->
        <section class="bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm">
            <div class="bg-gray-50 px-4 py-2 border-b font-bold text-gray-700">🎯 Data Korban ({{ form.korban?.length || 0 }})</div>
            <div class="p-4">
                <div v-for="(korban, index) in form.korban" :key="index" class="mb-4 pb-4 border-b border-gray-100 last:border-0 last:mb-0 last:pb-0">
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <div class="font-bold text-gray-900">{{ korban.orang?.nama || 'Korban ' + (index + 1) }}</div>
                            <div class="text-xs text-gray-500 mt-1">
                                <span v-if="korban.orang?.kewarganegaraan">{{ korban.orang.kewarganegaraan }}</span>
                                <span v-if="korban.orang?.negara_asal" class="ml-1">({{ korban.orang.negara_asal }})</span>
                            </div>
                        </div>
                        <span class="text-tactical-danger font-bold text-base">
                            Rp {{ parseInt(korban.kerugian_nominal || 0).toLocaleString('id-ID') }}
                        </span>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-xs text-gray-600 mt-2">
                        <div v-if="korban.orang?.nik">NIK: {{ formatUtils.formatNik(korban.orang.nik) }}</div>
                        <div v-if="korban.orang?.telepon">Telepon: {{ korban.orang.telepon }}</div>
                        <div v-if="korban.orang?.pekerjaan">Pekerjaan: {{ korban.orang.pekerjaan }}</div>
                        <div v-if="korban.orang?.pendidikan">Pendidikan: {{ korban.orang.pendidikan }}</div>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t-2 border-gray-300 flex justify-between text-base font-bold">
                    <span class="text-gray-700">Total Kerugian:</span>
                    <span class="text-tactical-danger">Rp {{ totalKerugian.toLocaleString('id-ID') }}</span>
                </div>
            </div>
        </section>

        <!-- Data Tersangka -->
        <section class="bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm">
            <div class="bg-gray-50 px-4 py-2 border-b font-bold text-gray-700">🔍 Data Tersangka ({{ form.tersangka?.length || 0 }})</div>
            <div class="p-4 space-y-3">
                <div v-for="(tersangka, index) in form.tersangka" :key="index" class="border-b border-gray-100 pb-3 last:border-0 last:pb-0">
                    <div class="text-xs text-red-500 font-bold mb-2">Tersangka {{ index + 1 }}</div>
                    <div v-if="tersangka.identitas && tersangka.identitas.length > 0" class="space-y-1 ml-3">
                        <div v-for="(id, iIndex) in tersangka.identitas" :key="iIndex" class="text-sm text-gray-700">
                            • <span class="font-semibold">{{ id.jenis }}:</span> 
                            <span class="font-mono">{{ id.nilai }}</span>
                            <span v-if="id.platform" class="text-xs text-gray-500 ml-1">({{ id.platform }})</span>
                        </div>
                    </div>
                    <div v-else class="text-sm text-gray-400 italic ml-3">Tidak ada identitas digital</div>
                    <div v-if="tersangka.catatan" class="mt-2 text-xs text-gray-600 italic ml-3">
                        Catatan: "{{ tersangka.catatan }}"
                    </div>
                </div>
            </div>
        </section>

        <!-- Modus Operandi -->
        <section class="bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm">
            <div class="bg-gray-50 px-4 py-2 border-b font-bold text-gray-700">📝 Modus Operandi</div>
            <div class="p-4">
                <div class="text-sm text-gray-700 leading-relaxed whitespace-pre-wrap bg-gray-50 p-3 rounded-lg italic">
                    "{{ form.modus || '-' }}"
                </div>
                <div class="mt-2 text-xs text-gray-500">
                    {{ form.modus?.length || 0 }} karakter
                </div>
            </div>
        </section>

        <!-- Action Buttons -->
        <div class="flex justify-between items-center pt-6 border-t border-gray-200">
            <button
                type="button"
                @click="$emit('back')"
                class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 font-semibold hover:bg-gray-50 transition-colors flex items-center gap-2 min-h-[48px]"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Kembali & Edit
            </button>

            <button
                type="button"
                @click="$emit('confirm')"
                class="px-8 py-3 bg-tactical-success text-white rounded-lg font-bold hover:bg-green-600 transition-colors flex items-center gap-2 min-h-[48px] shadow-lg"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Konfirmasi & Simpan Laporan
            </button>
        </div>
    </div>
</template>
