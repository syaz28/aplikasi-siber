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

// Get petugas name
const petugasName = computed(() => {
    if (!props.form.petugas_id || !props.masterData.anggota) return '-';
    const petugas = props.masterData.anggota.find(a => a.id == props.form.petugas_id);
    return petugas ? `${petugas.pangkat?.kode || ''} ${petugas.nama}`.trim() : '-';
});

// Get jenis kejahatan name
const jenisKejahatanName = computed(() => {
    return props.masterData.jenisKejahatan?.find(j => j.id == props.form.jenis_kejahatan_id)?.nama || '-';
});

// Format date for display
const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
};

// Total kerugian
const totalKerugian = computed(() => {
    return props.form.korban?.reduce((sum, k) => sum + (parseInt(k.kerugian_nominal) || 0), 0) || 0;
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
                    <h3 class="font-bold text-navy">Review Data Laporan</h3>
                    <p class="text-sm text-gray-600">Periksa kembali data sebelum menyimpan</p>
                </div>
            </div>
        </div>

        <!-- Data Administrasi -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <h4 class="font-semibold text-gray-700">📋 Data Administrasi</h4>
            </div>
            <div class="p-4 grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">Nomor STPA:</span>
                    <span class="ml-2 font-medium">{{ form.nomor_stpa || '(Auto-generate)' }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Tanggal:</span>
                    <span class="ml-2 font-medium">{{ formatDate(form.tanggal_laporan) }}</span>
                </div>
                <div class="col-span-2">
                    <span class="text-gray-500">Petugas:</span>
                    <span class="ml-2 font-medium">{{ petugasName }}</span>
                </div>
            </div>
        </div>

        <!-- Data Pelapor -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <h4 class="font-semibold text-gray-700">👤 Data Pelapor</h4>
            </div>
            <div class="p-4 grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">Nama:</span>
                    <span class="ml-2 font-medium">{{ form.pelapor?.nama || '-' }}</span>
                </div>
                <div>
                    <span class="text-gray-500">NIK:</span>
                    <span class="ml-2 font-medium">{{ formatUtils.formatNik(form.pelapor?.nik) || '-' }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Telepon:</span>
                    <span class="ml-2 font-medium">{{ formatUtils.formatPhone(form.pelapor?.telepon) || '-' }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Pekerjaan:</span>
                    <span class="ml-2 font-medium">{{ form.pelapor?.pekerjaan || '-' }}</span>
                </div>
            </div>
        </div>

        <!-- Data Kejadian -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <h4 class="font-semibold text-gray-700">⚠️ Data Kejadian</h4>
            </div>
            <div class="p-4 space-y-3 text-sm">
                <div>
                    <span class="text-gray-500">Jenis Kejahatan:</span>
                    <span class="ml-2 font-medium text-tactical-danger">{{ jenisKejahatanName }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Waktu Kejadian:</span>
                    <span class="ml-2 font-medium">{{ formatDate(form.waktu_kejadian) }}</span>
                </div>
            </div>
        </div>

        <!-- Data Korban -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <h4 class="font-semibold text-gray-700">🎯 Data Korban ({{ form.korban?.length || 0 }})</h4>
            </div>
            <div class="p-4">
                <div v-for="(korban, index) in form.korban" :key="index" class="mb-3 pb-3 border-b border-gray-100 last:border-0 last:mb-0 last:pb-0">
                    <div class="flex justify-between text-sm">
                        <span class="font-medium">{{ korban.orang?.nama || 'Korban ' + (index + 1) }}</span>
                        <span class="text-tactical-danger font-semibold">
                            Rp {{ parseInt(korban.kerugian_nominal || 0).toLocaleString('id-ID') }}
                        </span>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-t border-gray-200 flex justify-between text-sm font-bold">
                    <span>Total Kerugian:</span>
                    <span class="text-tactical-danger">Rp {{ totalKerugian.toLocaleString('id-ID') }}</span>
                </div>
            </div>
        </div>

        <!-- Data Tersangka -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <h4 class="font-semibold text-gray-700">🔍 Data Tersangka ({{ form.tersangka?.length || 0 }})</h4>
            </div>
            <div class="p-4">
                <div v-for="(tersangka, index) in form.tersangka" :key="index" class="mb-3 text-sm">
                    <span class="font-medium">Tersangka {{ index + 1 }}:</span>
                    <div class="ml-4 mt-1 space-y-1">
                        <div v-for="(id, iIndex) in tersangka.identitas" :key="iIndex" class="text-gray-600">
                            • {{ id.jenis }}: {{ id.nilai }} {{ id.platform ? `(${id.platform})` : '' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modus -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <h4 class="font-semibold text-gray-700">📝 Modus Operandi</h4>
            </div>
            <div class="p-4 text-sm text-gray-700">
                {{ form.modus || '-' }}
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-between pt-4">
            <button
                type="button"
                @click="$emit('back')"
                class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 font-semibold hover:bg-gray-50 transition-colors flex items-center gap-2"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Kembali & Edit
            </button>

            <button
                type="button"
                @click="$emit('confirm')"
                class="px-8 py-3 bg-tactical-success text-white rounded-lg font-bold hover:bg-green-600 transition-colors flex items-center gap-2 min-h-[48px]"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Konfirmasi & Simpan
            </button>
        </div>
    </div>
</template>
