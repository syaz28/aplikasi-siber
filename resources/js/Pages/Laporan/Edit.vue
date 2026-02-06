<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
// Use window.axios which has CSRF token configured in bootstrap.js
const axios = window.axios;
import SidebarLayout from '@/Layouts/SidebarLayout.vue';
import ToastContainer from '@/Components/ToastContainer.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import FormattedInput from '@/Components/FormattedInput.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    laporan: Object,
    statusOptions: Object,
});

const toast = useToast();
const isSubmitting = ref(false);
const errors = ref({});

// Master data
const masterData = reactive({
    anggota: [],
    kategori_kejahatan: [],
});
const jenisKejahatan = ref([]);

// Form data initialized from props
const form = reactive({
    nomor_stpa: props.laporan?.nomor_stpa || '',
    tanggal_laporan: props.laporan?.tanggal_laporan?.split('T')[0] || '',
    petugas_id: props.laporan?.petugas_id || '',
    kategori_kejahatan_id: props.laporan?.jenis_kejahatan?.kategori_kejahatan_id || '',
    jenis_kejahatan_id: props.laporan?.jenis_kejahatan_id || '',
    status: props.laporan?.status || 'draft',
    catatan: props.laporan?.catatan || '',
    modus: props.laporan?.modus || '',
});

// Formatted options
// Format: PANGKAT NAMA (NRP) - tanpa jabatan karena BA PIKET sudah ada di surat
const anggotaOptions = computed(() => {
    return masterData.anggota.map(a => ({
        ...a,
        displayName: `${a.pangkat || ''} ${a.nama || ''} (${a.nrp || ''})`.trim()
    }));
});

// Load master data
onMounted(async () => {
    try {
        const res = await axios.get('/api/master/form-init');
        if (res.data.success) {
            masterData.anggota = res.data.data.anggota || [];
            masterData.kategori_kejahatan = res.data.data.kategori_kejahatan || [];
        }
        
        // Load jenis kejahatan if kategori is set
        if (form.kategori_kejahatan_id) {
            await loadJenisKejahatan(form.kategori_kejahatan_id);
        }
    } catch (err) {
        console.error('Error loading master data:', err);
    }
});

const loadJenisKejahatan = async (kategoriId) => {
    if (!kategoriId) { jenisKejahatan.value = []; return; }
    try {
        const res = await axios.get(`/api/master/jenis-kejahatan/${kategoriId}`);
        if (res.data.success) jenisKejahatan.value = res.data.data;
    } catch (err) { console.error(err); }
};

watch(() => form.kategori_kejahatan_id, (val) => {
    form.jenis_kejahatan_id = '';
    loadJenisKejahatan(val);
});

// Submit form
const submitForm = async () => {
    isSubmitting.value = true;
    errors.value = {};

    try {
        await axios.put(`/laporan/${props.laporan.id}`, form);
        toast.success('Laporan berhasil diperbarui!');
        
        setTimeout(() => {
            router.visit(`/laporan/${props.laporan.id}`);
        }, 1000);
    } catch (err) {
        console.error('Update error:', err);
        if (err.response?.data?.errors) {
            errors.value = err.response.data.errors;
        }
        toast.error('Gagal memperbarui laporan');
    } finally {
        isSubmitting.value = false;
    }
};

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
</script>

<template>
    <Head :title="`Edit Laporan #${laporan?.id}`" />

    <SidebarLayout :title="`Edit Laporan #${laporan?.id}`">
        <ToastContainer />
        
        <div class="max-w-4xl mx-auto">
            <!-- Back Link -->
            <div class="mb-6">
                <Link href="/laporan" class="text-gray-500 hover:text-navy flex items-center gap-2 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Kembali ke Arsip
                </Link>
            </div>

            <!-- Warning Banner -->
            <div class="mb-6 p-4 bg-tactical-warning/10 border border-tactical-warning/30 rounded-lg flex items-start gap-3">
                <svg class="w-6 h-6 text-tactical-warning flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div>
                    <h4 class="font-semibold text-tactical-warning">Perhatian</h4>
                    <p class="text-sm text-gray-600">Semua perubahan pada laporan akan dicatat dalam log sistem.</p>
                </div>
            </div>

            <!-- Current Info Card -->
            <div class="bg-white rounded-xl shadow-tactical border border-tactical-border overflow-hidden mb-6">
                <div class="bg-gray-50 px-6 py-3 border-b border-gray-200">
                    <h3 class="font-semibold text-gray-700">Informasi Laporan Saat Ini</h3>
                </div>
                <div class="p-6 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500 block">ID Laporan</span>
                        <span class="font-medium">#{{ laporan?.id }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 block">Pelapor</span>
                        <span class="font-medium">{{ laporan?.pelapor?.nama || '-' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 block">Tanggal Dibuat</span>
                        <span class="font-medium">{{ formatDate(laporan?.created_at) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 block">Terakhir Diubah</span>
                        <span class="font-medium">{{ formatDate(laporan?.updated_at) }}</span>
                    </div>
                </div>
            </div>

            <!-- Edit Form -->
            <form @submit.prevent="submitForm" class="bg-white rounded-xl shadow-tactical border border-tactical-border overflow-hidden">
                <div class="bg-navy px-6 py-4 border-l-4 border-tactical-accent">
                    <h3 class="text-lg font-bold text-white">Edit Data Laporan</h3>
                </div>

                <div class="p-6 space-y-6">
                    <!-- Nomor STPA & Tanggal -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nomor STPA</label>
                            <input
                                type="text"
                                v-model="form.nomor_stpa"
                                class="w-full rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Laporan</label>
                            <input
                                type="date"
                                v-model="form.tanggal_laporan"
                                class="w-full rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent"
                            />
                        </div>
                    </div>

                    <!-- Petugas -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Petugas Penerima</label>
                        <SearchableSelect
                            v-model="form.petugas_id"
                            :options="anggotaOptions"
                            value-key="id"
                            label-key="displayName"
                            display-key="displayName"
                            placeholder="-- Pilih Petugas --"
                            search-placeholder="Ketik nama petugas..."
                        />
                    </div>

                    <!-- Kategori & Jenis Kejahatan -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kategori Kejahatan</label>
                            <SearchableSelect
                                v-model="form.kategori_kejahatan_id"
                                :options="masterData.kategori_kejahatan"
                                value-key="id"
                                label-key="nama"
                                placeholder="-- Pilih Kategori --"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Kejahatan</label>
                            <SearchableSelect
                                v-model="form.jenis_kejahatan_id"
                                :options="jenisKejahatan"
                                value-key="id"
                                label-key="nama"
                                placeholder="-- Pilih Jenis --"
                                :disabled="!form.kategori_kejahatan_id"
                            />
                        </div>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select
                            v-model="form.status"
                            class="w-full rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent"
                        >
                            <option v-for="(label, value) in statusOptions" :key="value" :value="value">
                                {{ label }}
                            </option>
                        </select>
                    </div>

                    <!-- Modus -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Modus Operandi</label>
                        <textarea
                            v-model="form.modus"
                            rows="4"
                            class="w-full rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent"
                        ></textarea>
                    </div>

                    <!-- Catatan -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Internal</label>
                        <textarea
                            v-model="form.catatan"
                            rows="3"
                            class="w-full rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent"
                            placeholder="Catatan tambahan untuk internal..."
                        ></textarea>
                    </div>
                </div>

                <!-- Actions -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-between">
                    <Link
                        :href="`/laporan/${laporan?.id}`"
                        class="px-6 py-2.5 border border-gray-300 rounded-lg text-gray-700 font-semibold hover:bg-gray-100 transition-colors min-h-[44px] flex items-center"
                    >
                        Batal
                    </Link>
                    <button
                        type="submit"
                        :disabled="isSubmitting"
                        class="px-6 py-2.5 bg-tactical-accent text-white rounded-lg font-semibold hover:bg-blue-600 transition-colors min-h-[44px] flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <template v-if="isSubmitting">
                            <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Menyimpan...
                        </template>
                        <template v-else>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Simpan Perubahan
                        </template>
                    </button>
                </div>
            </form>
        </div>
    </SidebarLayout>
</template>
