 <script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, reactive, computed, onMounted, watch, nextTick } from 'vue';
import axios from 'axios';
import SidebarLayout from '@/Layouts/SidebarLayout.vue';
import ToastContainer from '@/Components/ToastContainer.vue';
import { useToast } from '@/Composables/useToast';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import FormattedInput from '@/Components/FormattedInput.vue';
import PhoneInput from '@/Components/PhoneInput.vue';
import { normalizeLaporanFormData } from '@/Composables/usePersonDataParser';

const props = defineProps({
    laporan: { type: Object, required: true },
    statusOptions: { type: Object, required: true },
    hubunganPelaporOptions: { type: Object, required: true },
    jenisIdentitasOptions: { type: Array, default: () => [] },
    jenisFileOptions: { type: Array, default: () => [] },
});

const toast = useToast();

// Form state - pre-populated from props.laporan
const form = reactive({
    hubungan_pelapor: props.laporan.hubungan_pelapor || 'diri_sendiri',
    
    // Pelapor data
    pelapor: {
        kewarganegaraan: props.laporan.pelapor?.kewarganegaraan || 'WNI',
        negara_asal: props.laporan.pelapor?.negara_asal || '',
        nik: props.laporan.pelapor?.nik || '',
        nama: props.laporan.pelapor?.nama || '',
        tempat_lahir: props.laporan.pelapor?.tempat_lahir || '',
        tanggal_lahir: props.laporan.pelapor?.tanggal_lahir || '',
        jenis_kelamin: props.laporan.pelapor?.jenis_kelamin || 'LAKI-LAKI',
        pekerjaan: props.laporan.pelapor?.pekerjaan || '',
        pendidikan: props.laporan.pelapor?.pendidikan || '',
        telepon: props.laporan.pelapor?.telepon || '',
        domisili_same_as_ktp: false, // Don't sync in edit mode
        alamat_ktp: {
            negara: 'Indonesia',
            kode_provinsi: props.laporan.pelapor?.alamat_ktp?.kode_provinsi || '',
            kode_kabupaten: props.laporan.pelapor?.alamat_ktp?.kode_kabupaten || '',
            kode_kecamatan: props.laporan.pelapor?.alamat_ktp?.kode_kecamatan || '',
            kode_kelurahan: props.laporan.pelapor?.alamat_ktp?.kode_kelurahan || '',
            detail_alamat: props.laporan.pelapor?.alamat_ktp?.detail_alamat || '',
        },
        alamat_domisili: {
            negara: 'Indonesia',
            kode_provinsi: props.laporan.pelapor?.alamat_domisili?.kode_provinsi || '',
            kode_kabupaten: props.laporan.pelapor?.alamat_domisili?.kode_kabupaten || '',
            kode_kecamatan: props.laporan.pelapor?.alamat_domisili?.kode_kecamatan || '',
            kode_kelurahan: props.laporan.pelapor?.alamat_domisili?.kode_kelurahan || '',
            detail_alamat: props.laporan.pelapor?.alamat_domisili?.detail_alamat || '',
        },
    },
    
    // Kejadian data
    kategori_kejahatan_id: props.laporan.kategori_kejahatan_id || '',
    waktu_kejadian: props.laporan.waktu_kejadian ? props.laporan.waktu_kejadian.substring(0, 16) : '',
    kode_kabupaten_kejadian: props.laporan.kode_kabupaten_kejadian || '',
    alamat_kejadian: props.laporan.alamat_kejadian || '',
    modus: props.laporan.modus || '',
    catatan: props.laporan.catatan || '',
    
    // Korban array
    korban: [],
    
    // Tersangka array
    tersangka: [],
});

// Initialize korban from props
const initKorban = () => {
    if (props.laporan.korban && props.laporan.korban.length > 0) {
        form.korban = props.laporan.korban.map(k => ({
            id: k.id,
            orang: {
                id: k.orang?.id || null,
                kewarganegaraan: k.orang?.kewarganegaraan || 'WNI',
                negara_asal: k.orang?.negara_asal || '',
                nik: k.orang?.nik || '',
                nama: k.orang?.nama || '',
                tempat_lahir: k.orang?.tempat_lahir || '',
                tanggal_lahir: k.orang?.tanggal_lahir || '',
                jenis_kelamin: k.orang?.jenis_kelamin || 'LAKI-LAKI',
                pekerjaan: k.orang?.pekerjaan || '',
                pendidikan: k.orang?.pendidikan || '',
                telepon: k.orang?.telepon || '',
            },
            kerugian_nominal: String(k.kerugian_nominal || '0'),
            keterangan: k.keterangan || '',
        }));
    } else {
        form.korban = [{
            orang: {
                kewarganegaraan: 'WNI',
                negara_asal: '',
                nik: '',
                nama: '',
                tempat_lahir: '',
                tanggal_lahir: '',
                jenis_kelamin: 'LAKI-LAKI',
                pekerjaan: '',
                pendidikan: '',
                telepon: '',
            },
            kerugian_nominal: '0',
            keterangan: '',
        }];
    }
};

// Initialize tersangka from props
const initTersangka = () => {
    if (props.laporan.tersangka && props.laporan.tersangka.length > 0) {
        form.tersangka = props.laporan.tersangka.map(t => ({
            id: t.id,
            catatan: t.catatan || '',
            identitas: t.identitas && t.identitas.length > 0 
                ? t.identitas.map(i => ({
                    id: i.id,
                    jenis: i.jenis || 'telepon',
                    nilai: i.nilai || '',
                    platform: i.platform || '',
                }))
                : [{ jenis: 'telepon', nilai: '', platform: '' }],
        }));
    } else {
        form.tersangka = [{
            catatan: '',
            identitas: [{ jenis: 'telepon', nilai: '', platform: '' }],
        }];
    }
};

// Master data
const masterData = reactive({
    provinsi: [],
    kategori_kejahatan: [],
    pekerjaan: [],
    pendidikan: [],
    kabupaten_all: [],
    platforms: [],
    countries: [],
});

// Wilayah dropdowns for Alamat KTP
const kabupaten = ref([]);
const kecamatan = ref([]);
const kelurahan = ref([]);
const loadingWilayah = reactive({
    kabupaten: false,
    kecamatan: false,
    kelurahan: false,
});

// Wilayah dropdowns for Alamat Domisili
const kabupatenDomisili = ref([]);
const kecamatanDomisili = ref([]);
const kelurahanDomisili = ref([]);
const loadingWilayahDomisili = reactive({
    kabupaten: false,
    kecamatan: false,
    kelurahan: false,
});

// UI state
const isSubmitting = ref(false);
const isSuccess = ref(false);
const errors = ref({});
const apiError = ref(null);

// Format date for display
const formatDateTime = (date) => {
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

// Section collapsed state
const sections = reactive({
    pelapor: false,
    kejadian: false,
    tersangka: false,
});

const toggleSection = (section) => {
    sections[section] = !sections[section];
};

// Pelapor adalah Korban flag
const pelaporAdalahKorban = ref(false);

// Identity type options
const identitasTypes = [
    { value: 'telepon', label: 'Nomor Telepon' },
    { value: 'rekening', label: 'Rekening Bank' },
    { value: 'sosmed', label: 'Media Sosial' },
    { value: 'email', label: 'Email' },
    { value: 'ewallet', label: 'E-Wallet' },
    { value: 'lainnya', label: 'Lainnya' },
];

// Map identitas jenis to platform kategori
const getKategoriPlatform = (jenisIdentitas) => {
    const mapping = {
        'telepon': 'Nomor Telepon',
        'rekening': 'Rekening Bank',
        'sosmed': 'Media Sosial',
        'email': 'Email',
        'ewallet': 'E-Wallet',
        'lainnya': 'Lainnya',
    };
    return mapping[jenisIdentitas] || 'Lainnya';
};

// Get filtered platforms based on identitas jenis
const getFilteredPlatforms = (jenisIdentitas) => {
    const kategori = getKategoriPlatform(jenisIdentitas);
    return masterData.platforms.filter(p => p.kategori === kategori);
};

// Flag to skip watchers during initial load
const isLoadingData = ref(true);

// Cascading dropdown loaders
const loadKabupaten = async (kodeProvinsi) => {
    if (!kodeProvinsi) { kabupaten.value = []; return; }
    loadingWilayah.kabupaten = true;
    try {
        const res = await axios.get(`/api/master/kabupaten/${kodeProvinsi}`);
        if (res.data.success) kabupaten.value = res.data.data;
    } catch (err) { console.error(err); }
    finally { loadingWilayah.kabupaten = false; }
};

const loadKecamatan = async (kodeKabupaten) => {
    if (!kodeKabupaten) { kecamatan.value = []; return; }
    loadingWilayah.kecamatan = true;
    try {
        const res = await axios.get(`/api/master/kecamatan/${kodeKabupaten}`);
        if (res.data.success) kecamatan.value = res.data.data;
    } catch (err) { console.error(err); }
    finally { loadingWilayah.kecamatan = false; }
};

const loadKelurahan = async (kodeKecamatan) => {
    if (!kodeKecamatan) { kelurahan.value = []; return; }
    loadingWilayah.kelurahan = true;
    try {
        const res = await axios.get(`/api/master/kelurahan/${kodeKecamatan}`);
        if (res.data.success) kelurahan.value = res.data.data;
    } catch (err) { console.error(err); }
    finally { loadingWilayah.kelurahan = false; }
};

// Loaders for Alamat Domisili
const loadKabupatenDomisili = async (kodeProvinsi) => {
    if (!kodeProvinsi) { kabupatenDomisili.value = []; return; }
    loadingWilayahDomisili.kabupaten = true;
    try {
        const res = await axios.get(`/api/master/kabupaten/${kodeProvinsi}`);
        if (res.data.success) kabupatenDomisili.value = res.data.data;
    } catch (err) { console.error(err); }
    finally { loadingWilayahDomisili.kabupaten = false; }
};

const loadKecamatanDomisili = async (kodeKabupaten) => {
    if (!kodeKabupaten) { kecamatanDomisili.value = []; return; }
    loadingWilayahDomisili.kecamatan = true;
    try {
        const res = await axios.get(`/api/master/kecamatan/${kodeKabupaten}`);
        if (res.data.success) kecamatanDomisili.value = res.data.data;
    } catch (err) { console.error(err); }
    finally { loadingWilayahDomisili.kecamatan = false; }
};

const loadKelurahanDomisili = async (kodeKecamatan) => {
    if (!kodeKecamatan) { kelurahanDomisili.value = []; return; }
    loadingWilayahDomisili.kelurahan = true;
    try {
        const res = await axios.get(`/api/master/kelurahan/${kodeKecamatan}`);
        if (res.data.success) kelurahanDomisili.value = res.data.data;
    } catch (err) { console.error(err); }
    finally { loadingWilayahDomisili.kelurahan = false; }
};

// Watch for cascading changes - Alamat KTP
watch(() => form.pelapor.alamat_ktp.kode_provinsi, (val) => {
    if (isLoadingData.value) return;
    form.pelapor.alamat_ktp.kode_kabupaten = '';
    form.pelapor.alamat_ktp.kode_kecamatan = '';
    form.pelapor.alamat_ktp.kode_kelurahan = '';
    kecamatan.value = [];
    kelurahan.value = [];
    loadKabupaten(val);
});

watch(() => form.pelapor.alamat_ktp.kode_kabupaten, (val) => {
    if (isLoadingData.value) return;
    form.pelapor.alamat_ktp.kode_kecamatan = '';
    form.pelapor.alamat_ktp.kode_kelurahan = '';
    kelurahan.value = [];
    loadKecamatan(val);
});

watch(() => form.pelapor.alamat_ktp.kode_kecamatan, (val) => {
    if (isLoadingData.value) return;
    form.pelapor.alamat_ktp.kode_kelurahan = '';
    loadKelurahan(val);
});

// Watch for cascading changes - Alamat Domisili
watch(() => form.pelapor.alamat_domisili.kode_provinsi, (val) => {
    if (isLoadingData.value) return;
    form.pelapor.alamat_domisili.kode_kabupaten = '';
    form.pelapor.alamat_domisili.kode_kecamatan = '';
    form.pelapor.alamat_domisili.kode_kelurahan = '';
    kecamatanDomisili.value = [];
    kelurahanDomisili.value = [];
    loadKabupatenDomisili(val);
});

watch(() => form.pelapor.alamat_domisili.kode_kabupaten, (val) => {
    if (isLoadingData.value) return;
    form.pelapor.alamat_domisili.kode_kecamatan = '';
    form.pelapor.alamat_domisili.kode_kelurahan = '';
    kelurahanDomisili.value = [];
    loadKecamatanDomisili(val);
});

watch(() => form.pelapor.alamat_domisili.kode_kecamatan, (val) => {
    if (isLoadingData.value) return;
    form.pelapor.alamat_domisili.kode_kelurahan = '';
    loadKelurahanDomisili(val);
});

// Watch hubungan_pelapor
watch(() => form.hubungan_pelapor, (val) => {
    if (val === 'diri_sendiri') {
        pelaporAdalahKorban.value = true;
    } else {
        pelaporAdalahKorban.value = false;
    }
});

// Load master data and pre-populate cascading dropdowns
onMounted(async () => {
    try {
        // Initialize korban and tersangka
        initKorban();
        initTersangka();
        
        // Set pelaporAdalahKorban based on hubungan_pelapor
        if (form.hubungan_pelapor === 'diri_sendiri') {
            pelaporAdalahKorban.value = true;
        }
        
        // Load master data
        const res = await axios.get('/api/master/form-init');
        if (res.data.success) {
            masterData.provinsi = res.data.data.provinsi || [];
            masterData.kategori_kejahatan = res.data.data.kategori_kejahatan || [];
            masterData.pekerjaan = res.data.data.pekerjaan || [];
            masterData.pendidikan = res.data.data.pendidikan || [];
            masterData.kabupaten_all = res.data.data.kabupaten_all || [];
            masterData.platforms = res.data.data.platforms || [];
            masterData.countries = res.data.data.countries || [];
        }
        
        await nextTick();
        
        // Pre-load cascading dropdowns for Alamat KTP
        if (form.pelapor.alamat_ktp.kode_provinsi) {
            await loadKabupaten(form.pelapor.alamat_ktp.kode_provinsi);
            
            if (form.pelapor.alamat_ktp.kode_kabupaten) {
                await loadKecamatan(form.pelapor.alamat_ktp.kode_kabupaten);
                
                if (form.pelapor.alamat_ktp.kode_kecamatan) {
                    await loadKelurahan(form.pelapor.alamat_ktp.kode_kecamatan);
                }
            }
        }
        
        // Pre-load cascading dropdowns for Alamat Domisili
        if (form.pelapor.alamat_domisili.kode_provinsi) {
            await loadKabupatenDomisili(form.pelapor.alamat_domisili.kode_provinsi);
            
            if (form.pelapor.alamat_domisili.kode_kabupaten) {
                await loadKecamatanDomisili(form.pelapor.alamat_domisili.kode_kabupaten);
                
                if (form.pelapor.alamat_domisili.kode_kecamatan) {
                    await loadKelurahanDomisili(form.pelapor.alamat_domisili.kode_kecamatan);
                }
            }
        }
        
        // Done loading initial data
        isLoadingData.value = false;
        
    } catch (err) {
        console.error('Error loading master data:', err);
        toast.error('Gagal memuat data master');
        isLoadingData.value = false;
    }
});

// Korban management
const addKorban = () => {
    form.korban.push({
        orang: {
            kewarganegaraan: 'WNI',
            negara_asal: '',
            nik: '',
            nama: '',
            tempat_lahir: '',
            tanggal_lahir: '',
            jenis_kelamin: 'LAKI-LAKI',
            pekerjaan: '',
            pendidikan: '',
            telepon: '',
        },
        kerugian_nominal: '0',
        keterangan: '',
    });
};

const removeKorban = (index) => {
    if (form.korban.length > 1) {
        form.korban.splice(index, 1);
    }
};

// Tersangka management
const addTersangka = () => {
    form.tersangka.push({
        catatan: '',
        identitas: [{ jenis: 'telepon', nilai: '', platform: '' }],
    });
};

const removeTersangka = (index) => {
    if (form.tersangka.length > 1) {
        form.tersangka.splice(index, 1);
    }
};

// Identitas management
const addIdentitas = (tersangkaIndex) => {
    form.tersangka[tersangkaIndex].identitas.push({ jenis: 'telepon', nilai: '', platform: '' });
};

const removeIdentitas = (tersangkaIndex, identitasIndex) => {
    if (form.tersangka[tersangkaIndex].identitas.length > 1) {
        form.tersangka[tersangkaIndex].identitas.splice(identitasIndex, 1);
    }
};

// Submit form
const submitForm = async () => {
    isSubmitting.value = true;
    errors.value = {};
    apiError.value = null;

    // If pelapor adalah korban, copy data
    if (pelaporAdalahKorban.value && form.korban.length > 0) {
        form.korban[0].orang = {
            nik: form.pelapor.nik,
            nama: form.pelapor.nama,
            tempat_lahir: form.pelapor.tempat_lahir,
            tanggal_lahir: form.pelapor.tanggal_lahir,
            jenis_kelamin: form.pelapor.jenis_kelamin,
            pekerjaan: form.pelapor.pekerjaan,
            telepon: form.pelapor.telepon,
        };
    }

    // Safety: Normalize kerugian_nominal to String
    if (form.korban && Array.isArray(form.korban)) {
        form.korban.forEach(k => {
            if (typeof k.kerugian_nominal === 'number') {
                k.kerugian_nominal = String(k.kerugian_nominal);
            }
        });
    }

    // Normalize data
    const normalizedForm = normalizeLaporanFormData(form);

    try {
        const response = await axios.put(`/laporan/${props.laporan.id}`, normalizedForm);
        
        if (response.data.success) {
            toast.smartSuccess(response.data.message || 'Laporan berhasil diperbarui!');
            isSuccess.value = true;
            
            // Redirect after 2 seconds
            setTimeout(() => {
                router.visit(`/laporan/${props.laporan.id}`);
            }, 2000);
        }
    } catch (err) {
        console.error('Submit error:', err);
        
        if (err.response?.data?.errors) {
            errors.value = err.response.data.errors;
            
            const errorList = Object.entries(errors.value).map(([field, messages]) => {
                const message = Array.isArray(messages) ? messages[0] : messages;
                return `• ${field}: ${message}`;
            });
            
            apiError.value = `Mohon lengkapi/perbaiki data berikut:\n\n${errorList.join('\n')}`;
            toast.error(`Validasi gagal! ${errorList[0]}`);
        } else if (err.response?.data?.message) {
            apiError.value = err.response.data.message;
            toast.error(err.response.data.message);
        } else {
            apiError.value = 'Terjadi kesalahan saat menyimpan laporan.';
            toast.error('Gagal menyimpan laporan.');
        }
        
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <Head :title="`Edit Laporan - ${laporan.nomor_stpa || 'Draft'}`" />

    <SidebarLayout title="Edit Laporan Kejahatan Siber">
        <ToastContainer />

        <div class="max-w-4xl mx-auto">
            <!-- Header with Back Button -->
            <div class="mb-6 flex items-center justify-between">
                <button
                    @click="router.visit(`/laporan/${laporan.id}`)"
                    class="flex items-center gap-2 text-gray-600 hover:text-gray-900 transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Kembali ke Detail
                </button>
                <span class="text-sm text-gray-500">{{ laporan.nomor_stpa || 'Draft' }}</span>
            </div>

            <!-- Timestamp Info Card -->
            <div class="mb-6 bg-white rounded-xl shadow-tactical border border-tactical-border overflow-hidden">
                <div class="bg-gray-50 px-6 py-3 border-b border-gray-200">
                    <h3 class="font-semibold text-gray-700">Informasi Laporan</h3>
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
                        <span class="font-medium">{{ formatDateTime(laporan?.created_at) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 block">Terakhir Diubah</span>
                        <span class="font-medium">{{ formatDateTime(laporan?.updated_at) }}</span>
                    </div>
                </div>
            </div>

            <!-- Error Alert -->
            <div v-if="apiError" class="mb-6 p-4 bg-red-50 border-2 border-red-500 rounded-lg text-red-700">
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 flex-shrink-0 text-red-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="flex-1">
                        <h4 class="font-bold text-red-800 mb-2">Validasi Gagal</h4>
                        <div class="text-sm whitespace-pre-line">{{ apiError }}</div>
                    </div>
                    <button @click="apiError = null" class="text-red-500 hover:text-red-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Form Card -->
            <div class="bg-white rounded-xl shadow-tactical border border-tactical-border overflow-visible">
                
                <!-- Success View -->
                <div v-if="isSuccess" class="p-12 text-center">
                    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-navy mb-2">Laporan Berhasil Diperbarui!</h3>
                    <p class="text-gray-600 mb-8">Data laporan telah diperbarui. Anda akan dialihkan ke halaman detail.</p>
                </div>

                <!-- Form View -->
                <div v-else class="divide-y divide-gray-200">
                    
                    <!-- Section 1: Data Pelapor -->
                    <div class="overflow-visible">
                        <button
                            type="button"
                            @click="toggleSection('pelapor')"
                            class="w-full bg-navy px-4 py-3 flex items-center justify-between hover:bg-navy/90 transition-colors"
                        >
                            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                                <span class="bg-tactical-accent text-white px-2 py-0.5 rounded text-sm">1</span>
                                Data Pelapor
                            </h3>
                            <svg 
                                class="w-5 h-5 text-white transition-transform" 
                                :class="{ 'rotate-180': !sections.pelapor }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div v-show="!sections.pelapor" class="p-6 space-y-6">
                            <!-- WNI/WNA Toggle -->
                            <div class="px-3 py-2 bg-gray-50 rounded-lg border border-gray-200 inline-block">
                                <div class="flex items-center gap-4">
                                    <span class="text-xs font-medium text-gray-500">Kewarganegaraan:</span>
                                    <label class="flex items-center gap-1.5 cursor-pointer">
                                        <input
                                            type="radio"
                                            v-model="form.pelapor.kewarganegaraan"
                                            value="WNI"
                                            class="w-3.5 h-3.5 text-tactical-accent focus:ring-tactical-accent"
                                        />
                                        <span class="text-xs font-medium text-gray-700">WNI</span>
                                    </label>
                                    <label class="flex items-center gap-1.5 cursor-pointer">
                                        <input
                                            type="radio"
                                            v-model="form.pelapor.kewarganegaraan"
                                            value="WNA"
                                            class="w-3.5 h-3.5 text-tactical-accent focus:ring-tactical-accent"
                                        />
                                        <span class="text-xs font-medium text-gray-700">WNA</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Hubungan Pelapor -->
                            <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                <label class="block text-sm font-bold text-navy mb-2">
                                    Kapasitas Pelapor <span class="text-red-500">*</span>
                                </label>
                                <select
                                    v-model="form.hubungan_pelapor"
                                    class="w-full md:w-1/2 rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent"
                                >
                                    <option v-for="(label, value) in hubunganPelaporOptions" :key="value" :value="value">
                                        {{ label }}
                                    </option>
                                </select>
                            </div>

                            <!-- Identity Section -->
                            <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <h4 class="font-semibold text-navy mb-4 flex items-center gap-2">
                                    Data Identitas
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- NIK / Passport -->
                                    <div v-if="form.pelapor.kewarganegaraan === 'WNI'">
                                        <FormattedInput
                                            v-model="form.pelapor.nik"
                                            type="nik"
                                            label="NIK"
                                            placeholder="16 digit NIK"
                                            required
                                        />
                                    </div>
                                    <div v-else>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            No. Paspor / ID Asing <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            v-model="form.pelapor.nik"
                                            class="w-full rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent"
                                            placeholder="No. Paspor"
                                            maxlength="50"
                                        />
                                    </div>

                                    <!-- Negara Asal (WNA only) -->
                                    <div v-if="form.pelapor.kewarganegaraan === 'WNA'">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Negara Asal <span class="text-red-500">*</span>
                                        </label>
                                        <SearchableSelect
                                            v-model="form.pelapor.negara_asal"
                                            :options="masterData.countries"
                                            value-key="name"
                                            label-key="name"
                                            placeholder="-- Pilih Negara --"
                                        />
                                    </div>

                                    <FormattedInput
                                        v-model="form.pelapor.nama"
                                        type="name"
                                        label="Nama Lengkap"
                                        placeholder="Nama lengkap"
                                        required
                                    />

                                    <!-- Tempat Lahir (WNI only) -->
                                    <div v-if="form.pelapor.kewarganegaraan === 'WNI'">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Tempat Lahir <span class="text-red-500">*</span>
                                        </label>
                                        <SearchableSelect
                                            v-model="form.pelapor.tempat_lahir"
                                            :options="masterData.kabupaten_all"
                                            value-key="nama"
                                            label-key="nama"
                                            placeholder="-- Pilih Kota/Kabupaten --"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Tanggal Lahir <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="date"
                                            v-model="form.pelapor.tanggal_lahir"
                                            class="w-full rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Jenis Kelamin <span class="text-red-500">*</span>
                                        </label>
                                        <select 
                                            v-model="form.pelapor.jenis_kelamin" 
                                            class="w-full rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent"
                                        >
                                            <option value="LAKI-LAKI">LAKI-LAKI</option>
                                            <option value="PEREMPUAN">PEREMPUAN</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Pekerjaan <span class="text-red-500">*</span>
                                        </label>
                                        <SearchableSelect
                                            v-model="form.pelapor.pekerjaan"
                                            :options="masterData.pekerjaan"
                                            value-key="nama"
                                            label-key="nama"
                                            placeholder="-- Pilih Pekerjaan --"
                                        />
                                    </div>

                                    <PhoneInput
                                        v-model="form.pelapor.telepon"
                                        label="Telepon"
                                        required
                                    />

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Pendidikan Terakhir <span class="text-red-500">*</span>
                                        </label>
                                        <SearchableSelect
                                            v-model="form.pelapor.pendidikan"
                                            :options="masterData.pendidikan"
                                            value-key="nama"
                                            label-key="nama"
                                            placeholder="-- Pilih Pendidikan --"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Address Section -->
                            <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <h4 class="font-semibold text-navy mb-4">
                                    {{ form.pelapor.kewarganegaraan === 'WNI' ? 'Alamat KTP' : 'Alamat Asal' }}
                                </h4>

                                <!-- WNI: Region Dropdowns -->
                                <template v-if="form.pelapor.kewarganegaraan === 'WNI'">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Provinsi <span class="text-red-500">*</span>
                                            </label>
                                            <SearchableSelect
                                                v-model="form.pelapor.alamat_ktp.kode_provinsi"
                                                :options="masterData.provinsi"
                                                value-key="kode"
                                                label-key="nama"
                                                placeholder="-- Pilih Provinsi --"
                                            />
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Kabupaten/Kota <span class="text-red-500">*</span>
                                            </label>
                                            <SearchableSelect
                                                v-model="form.pelapor.alamat_ktp.kode_kabupaten"
                                                :options="kabupaten"
                                                value-key="kode"
                                                label-key="nama"
                                                placeholder="-- Pilih Kabupaten --"
                                                :loading="loadingWilayah.kabupaten"
                                                :disabled="!form.pelapor.alamat_ktp.kode_provinsi"
                                            />
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Kecamatan <span class="text-red-500">*</span>
                                            </label>
                                            <SearchableSelect
                                                v-model="form.pelapor.alamat_ktp.kode_kecamatan"
                                                :options="kecamatan"
                                                value-key="kode"
                                                label-key="nama"
                                                placeholder="-- Pilih Kecamatan --"
                                                :loading="loadingWilayah.kecamatan"
                                                :disabled="!form.pelapor.alamat_ktp.kode_kabupaten"
                                            />
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Kelurahan/Desa <span class="text-red-500">*</span>
                                            </label>
                                            <SearchableSelect
                                                v-model="form.pelapor.alamat_ktp.kode_kelurahan"
                                                :options="kelurahan"
                                                value-key="kode"
                                                label-key="nama"
                                                placeholder="-- Pilih Kelurahan --"
                                                :loading="loadingWilayah.kelurahan"
                                                :disabled="!form.pelapor.alamat_ktp.kode_kecamatan"
                                            />
                                        </div>

                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Detail Alamat <span class="text-red-500">*</span>
                                            </label>
                                            <textarea
                                                v-model="form.pelapor.alamat_ktp.detail_alamat"
                                                rows="2"
                                                class="w-full rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent"
                                                placeholder="Jalan, RT/RW, No. Rumah"
                                            ></textarea>
                                        </div>
                                    </div>

                                    <!-- Alamat Domisili for WNI -->
                                    <div class="p-4 bg-blue-50 rounded-lg border border-blue-200 mt-4">
                                        <h5 class="font-medium text-blue-800 mb-4">
                                            Alamat Domisili (Tempat Tinggal Saat Ini)
                                        </h5>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                                    Provinsi <span class="text-red-500">*</span>
                                                </label>
                                                <SearchableSelect
                                                    v-model="form.pelapor.alamat_domisili.kode_provinsi"
                                                    :options="masterData.provinsi"
                                                    value-key="kode"
                                                    label-key="nama"
                                                    placeholder="-- Pilih Provinsi --"
                                                />
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                                    Kabupaten/Kota <span class="text-red-500">*</span>
                                                </label>
                                                <SearchableSelect
                                                    v-model="form.pelapor.alamat_domisili.kode_kabupaten"
                                                    :options="kabupatenDomisili"
                                                    value-key="kode"
                                                    label-key="nama"
                                                    placeholder="-- Pilih Kabupaten --"
                                                    :loading="loadingWilayahDomisili.kabupaten"
                                                    :disabled="!form.pelapor.alamat_domisili.kode_provinsi"
                                                />
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                                    Kecamatan <span class="text-red-500">*</span>
                                                </label>
                                                <SearchableSelect
                                                    v-model="form.pelapor.alamat_domisili.kode_kecamatan"
                                                    :options="kecamatanDomisili"
                                                    value-key="kode"
                                                    label-key="nama"
                                                    placeholder="-- Pilih Kecamatan --"
                                                    :loading="loadingWilayahDomisili.kecamatan"
                                                    :disabled="!form.pelapor.alamat_domisili.kode_kabupaten"
                                                />
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                                    Kelurahan/Desa <span class="text-red-500">*</span>
                                                </label>
                                                <SearchableSelect
                                                    v-model="form.pelapor.alamat_domisili.kode_kelurahan"
                                                    :options="kelurahanDomisili"
                                                    value-key="kode"
                                                    label-key="nama"
                                                    placeholder="-- Pilih Kelurahan --"
                                                    :loading="loadingWilayahDomisili.kelurahan"
                                                    :disabled="!form.pelapor.alamat_domisili.kode_kecamatan"
                                                />
                                            </div>

                                            <div class="md:col-span-2">
                                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                                    Detail Alamat <span class="text-red-500">*</span>
                                                </label>
                                                <textarea
                                                    v-model="form.pelapor.alamat_domisili.detail_alamat"
                                                    rows="2"
                                                    class="w-full rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent"
                                                    placeholder="Jalan, RT/RW, No. Rumah"
                                                ></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <!-- WNA: Domisili in Indonesia -->
                                <template v-else>
                                    <div class="p-4 bg-green-50 rounded-lg border border-green-200">
                                        <h5 class="font-medium text-green-800 mb-4">
                                            Domisili Saat Ini di Indonesia
                                        </h5>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <SearchableSelect
                                                v-model="form.pelapor.alamat_domisili.kode_provinsi"
                                                :options="masterData.provinsi"
                                                value-key="kode"
                                                label-key="nama"
                                                placeholder="-- Pilih Provinsi --"
                                            />

                                            <SearchableSelect
                                                v-model="form.pelapor.alamat_domisili.kode_kabupaten"
                                                :options="kabupatenDomisili"
                                                value-key="kode"
                                                label-key="nama"
                                                placeholder="-- Pilih Kabupaten/Kota --"
                                                :loading="loadingWilayahDomisili.kabupaten"
                                                :disabled="!form.pelapor.alamat_domisili.kode_provinsi"
                                            />

                                            <SearchableSelect
                                                v-model="form.pelapor.alamat_domisili.kode_kecamatan"
                                                :options="kecamatanDomisili"
                                                value-key="kode"
                                                label-key="nama"
                                                placeholder="-- Pilih Kecamatan --"
                                                :loading="loadingWilayahDomisili.kecamatan"
                                                :disabled="!form.pelapor.alamat_domisili.kode_kabupaten"
                                            />

                                            <SearchableSelect
                                                v-model="form.pelapor.alamat_domisili.kode_kelurahan"
                                                :options="kelurahanDomisili"
                                                value-key="kode"
                                                label-key="nama"
                                                placeholder="-- Pilih Kelurahan --"
                                                :loading="loadingWilayahDomisili.kelurahan"
                                                :disabled="!form.pelapor.alamat_domisili.kode_kecamatan"
                                            />

                                            <div class="md:col-span-2">
                                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                                    Detail Alamat <span class="text-red-500">*</span>
                                                </label>
                                                <textarea
                                                    v-model="form.pelapor.alamat_domisili.detail_alamat"
                                                    rows="2"
                                                    class="w-full rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent"
                                                    placeholder="Nama Hotel/Apartemen, Nomor Kamar, Jalan"
                                                ></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Data Kejadian & Korban -->
                    <div class="overflow-visible">
                        <button
                            type="button"
                            @click="toggleSection('kejadian')"
                            class="w-full bg-navy px-4 py-3 flex items-center justify-between hover:bg-navy/90 transition-colors"
                        >
                            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                                <span class="bg-tactical-accent text-white px-2 py-0.5 rounded text-sm">2</span>
                                Data Kejadian & Korban
                            </h3>
                            <svg 
                                class="w-5 h-5 text-white transition-transform" 
                                :class="{ 'rotate-180': !sections.kejadian }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div v-show="!sections.kejadian" class="p-6 space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Kategori Kejahatan <span class="text-red-500">*</span>
                                    </label>
                                    <SearchableSelect
                                        v-model="form.kategori_kejahatan_id"
                                        :options="masterData.kategori_kejahatan"
                                        value-key="id"
                                        label-key="nama"
                                        placeholder="-- Pilih Kategori --"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Waktu Kejadian <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="datetime-local"
                                        v-model="form.waktu_kejadian"
                                        class="w-full rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent"
                                    />
                                </div>
                            </div>

                            <!-- Lokasi Kejadian -->
                            <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
                                <h4 class="font-semibold text-navy mb-4">Lokasi Kejadian</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Kabupaten/Kota <span class="text-red-500">*</span>
                                        </label>
                                        <SearchableSelect
                                            v-model="form.kode_kabupaten_kejadian"
                                            :options="masterData.kabupaten_all"
                                            value-key="kode"
                                            label-key="nama"
                                            placeholder="-- Pilih Kabupaten/Kota --"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Detail Alamat Kejadian
                                        </label>
                                        <input
                                            type="text"
                                            v-model="form.alamat_kejadian"
                                            class="w-full rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent"
                                            placeholder="Jl. ..., RT/RW, No. ..."
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Korban Section -->
                            <div class="border-t pt-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="font-semibold text-navy flex items-center gap-2">
                                        Data Korban
                                        <span class="text-sm font-normal text-gray-500">({{ form.korban.length }} korban)</span>
                                    </h4>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input 
                                            type="checkbox" 
                                            v-model="pelaporAdalahKorban" 
                                            class="rounded border-gray-300 text-tactical-accent focus:ring-tactical-accent" 
                                        />
                                        <span class="text-sm text-gray-600">Pelapor adalah Korban</span>
                                    </label>
                                </div>

                                <div v-for="(korban, kIndex) in form.korban" :key="kIndex" class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                                    <div class="flex justify-between items-center mb-4">
                                        <span class="font-medium text-navy">Korban {{ kIndex + 1 }}</span>
                                        <button
                                            v-if="form.korban.length > 1"
                                            type="button"
                                            @click="removeKorban(kIndex)"
                                            class="text-tactical-danger hover:text-red-700 p-2"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>

                                    <div v-if="kIndex === 0 && pelaporAdalahKorban" class="mb-4 p-3 bg-tactical-accent/10 rounded-lg text-sm text-tactical-accent flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Data korban akan diambil dari data pelapor
                                    </div>

                                    <div v-else class="space-y-4">
                                        <!-- WNI/WNA Toggle -->
                                        <div class="px-3 py-2 bg-gray-100 rounded-lg border border-gray-300 inline-block">
                                            <div class="flex items-center gap-4">
                                                <span class="text-xs font-medium text-gray-600">Kewarganegaraan:</span>
                                                <label class="flex items-center gap-1.5 cursor-pointer">
                                                    <input
                                                        type="radio"
                                                        v-model="korban.orang.kewarganegaraan"
                                                        value="WNI"
                                                        class="w-3.5 h-3.5 text-tactical-accent focus:ring-tactical-accent"
                                                    />
                                                    <span class="text-xs font-medium text-gray-700">WNI</span>
                                                </label>
                                                <label class="flex items-center gap-1.5 cursor-pointer">
                                                    <input
                                                        type="radio"
                                                        v-model="korban.orang.kewarganegaraan"
                                                        value="WNA"
                                                        class="w-3.5 h-3.5 text-tactical-accent focus:ring-tactical-accent"
                                                    />
                                                    <span class="text-xs font-medium text-gray-700">WNA</span>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Negara Asal (WNA only) -->
                                        <div v-if="korban.orang.kewarganegaraan === 'WNA'">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Negara Asal <span class="text-red-500">*</span>
                                            </label>
                                            <SearchableSelect
                                                v-model="korban.orang.negara_asal"
                                                :options="masterData.countries"
                                                value-key="name"
                                                label-key="name"
                                                placeholder="-- Pilih Negara --"
                                            />
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <FormattedInput 
                                                v-model="korban.orang.nik" 
                                                type="nik" 
                                                :label="korban.orang.kewarganegaraan === 'WNA' ? 'Passport / KITAS' : 'NIK Korban'"
                                            />
                                            <FormattedInput 
                                                v-model="korban.orang.nama" 
                                                type="name" 
                                                label="Nama Korban" 
                                            />

                                            <div v-if="korban.orang.kewarganegaraan === 'WNI'">
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Tempat Lahir</label>
                                                <SearchableSelect
                                                    v-model="korban.orang.tempat_lahir"
                                                    :options="masterData.kabupaten_all"
                                                    value-key="nama"
                                                    label-key="nama"
                                                    placeholder="-- Pilih Kota/Kabupaten --"
                                                />
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Lahir</label>
                                                <input 
                                                    type="date" 
                                                    v-model="korban.orang.tanggal_lahir" 
                                                    class="w-full rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent" 
                                                />
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Kelamin</label>
                                                <select 
                                                    v-model="korban.orang.jenis_kelamin" 
                                                    class="w-full rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent"
                                                >
                                                    <option value="LAKI-LAKI">LAKI-LAKI</option>
                                                    <option value="PEREMPUAN">PEREMPUAN</option>
                                                </select>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Pekerjaan</label>
                                                <SearchableSelect
                                                    v-model="korban.orang.pekerjaan"
                                                    :options="masterData.pekerjaan"
                                                    value-key="nama"
                                                    label-key="nama"
                                                    placeholder="-- Pilih Pekerjaan --"
                                                />
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Pendidikan</label>
                                                <SearchableSelect
                                                    v-model="korban.orang.pendidikan"
                                                    :options="masterData.pendidikan"
                                                    value-key="nama"
                                                    label-key="nama"
                                                    placeholder="-- Pilih Pendidikan --"
                                                />
                                            </div>

                                            <PhoneInput v-model="korban.orang.telepon" label="Telepon" />
                                        </div>
                                    </div>

                                    <FormattedInput
                                        v-model="korban.kerugian_nominal"
                                        type="currency"
                                        label="Kerugian"
                                        placeholder="0"
                                        class="mt-4"
                                    />
                                </div>

                                <button
                                    type="button"
                                    @click="addKorban"
                                    class="w-full py-3 border-2 border-dashed border-gray-300 rounded-lg text-gray-500 hover:border-tactical-accent hover:text-tactical-accent transition-colors flex items-center justify-center gap-2"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Tambah Korban
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Data Tersangka & Modus -->
                    <div class="overflow-visible">
                        <button
                            type="button"
                            @click="toggleSection('tersangka')"
                            class="w-full bg-navy px-4 py-3 flex items-center justify-between hover:bg-navy/90 transition-colors"
                        >
                            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                                <span class="bg-tactical-accent text-white px-2 py-0.5 rounded text-sm">3</span>
                                Data Tersangka & Modus
                            </h3>
                            <svg 
                                class="w-5 h-5 text-white transition-transform" 
                                :class="{ 'rotate-180': !sections.tersangka }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div v-show="!sections.tersangka" class="p-6 space-y-6">
                            <!-- Tersangka Section -->
                            <div>
                                <h4 class="font-semibold text-navy mb-4 flex items-center gap-2">
                                    Data Tersangka
                                    <span class="text-sm font-normal text-gray-500">({{ form.tersangka.length }} tersangka)</span>
                                </h4>

                                <div v-for="(tersangka, tIndex) in form.tersangka" :key="tIndex" class="mb-6 p-4 bg-red-50 rounded-lg border border-red-200 overflow-visible relative">
                                    <div class="flex justify-between items-center mb-4">
                                        <span class="font-medium text-tactical-danger">Tersangka {{ tIndex + 1 }}</span>
                                        <button
                                            v-if="form.tersangka.length > 1"
                                            type="button"
                                            @click="removeTersangka(tIndex)"
                                            class="text-tactical-danger hover:text-red-700 p-2"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Identitas Digital -->
                                    <div class="space-y-3">
                                        <label class="block text-sm font-medium text-gray-700">Identitas Digital</label>
                                        
                                        <div v-for="(identitas, iIndex) in tersangka.identitas" :key="iIndex" class="flex gap-2 flex-wrap sm:flex-nowrap items-start">
                                            <select v-model="identitas.jenis" class="w-full sm:w-40 rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent text-sm">
                                                <option v-for="type in identitasTypes" :key="type.value" :value="type.value">
                                                    {{ type.label }}
                                                </option>
                                            </select>

                                            <PhoneInput
                                                v-if="identitas.jenis === 'telepon'"
                                                v-model="identitas.nilai"
                                                :label="null"
                                                class="flex-1 min-w-0"
                                            />
                                            <FormattedInput
                                                v-else-if="identitas.jenis === 'email'"
                                                v-model="identitas.nilai"
                                                type="email"
                                                placeholder="email@domain.com"
                                                class="flex-1 min-w-0"
                                                :show-valid-icon="false"
                                            />
                                            <FormattedInput
                                                v-else-if="identitas.jenis === 'rekening'"
                                                v-model="identitas.nilai"
                                                type="number"
                                                placeholder="Nomor rekening"
                                                class="flex-1 min-w-0"
                                                :show-valid-icon="false"
                                            />
                                            <input
                                                v-else
                                                type="text"
                                                v-model="identitas.nilai"
                                                class="flex-1 min-w-0 rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent text-sm"
                                                placeholder="Username / Link Profil"
                                            />

                                            <div class="w-full sm:w-48 flex-shrink-0">
                                                <SearchableSelect
                                                    v-model="identitas.platform"
                                                    :options="getFilteredPlatforms(identitas.jenis)"
                                                    value-key="nama_platform"
                                                    label-key="nama_platform"
                                                    :placeholder="identitas.jenis === 'rekening' ? 'Pilih Bank' : 'Pilih Platform'"
                                                    class="text-sm"
                                                />
                                            </div>

                                            <button
                                                v-if="tersangka.identitas.length > 1"
                                                type="button"
                                                @click="removeIdentitas(tIndex, iIndex)"
                                                class="p-2 text-gray-400 hover:text-tactical-danger flex-shrink-0"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>

                                        <button
                                            type="button"
                                            @click="addIdentitas(tIndex)"
                                            class="text-sm text-tactical-accent hover:text-blue-700 flex items-center gap-1"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                            Tambah Identitas
                                        </button>
                                    </div>

                                    <div class="mt-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Tersangka</label>
                                        <textarea
                                            v-model="tersangka.catatan"
                                            rows="2"
                                            class="w-full rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent text-sm"
                                            placeholder="Catatan tambahan tentang tersangka..."
                                        ></textarea>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    @click="addTersangka"
                                    class="w-full py-3 border-2 border-dashed border-red-300 rounded-lg text-red-500 hover:border-tactical-danger hover:text-tactical-danger transition-colors flex items-center justify-center gap-2"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Tambah Tersangka
                                </button>
                            </div>

                            <!-- Modus Operandi -->
                            <div class="border-t pt-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Modus Operandi / Kronologi <span class="text-red-500">*</span>
                                </label>
                                <textarea
                                    v-model="form.modus"
                                    rows="6"
                                    class="w-full rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent"
                                    placeholder="Jelaskan kronologi kejadian secara detail..."
                                ></textarea>
                                <p class="mt-1 text-xs text-gray-400">Minimal 50 karakter. Saat ini: {{ form.modus?.length || 0 }} karakter</p>
                            </div>

                            <!-- Catatan -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                                <textarea
                                    v-model="form.catatan"
                                    rows="3"
                                    class="w-full rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent"
                                    placeholder="Catatan tambahan..."
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-between">
                        <button
                            type="button"
                            @click="router.visit(`/laporan/${laporan.id}`)"
                            class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 font-semibold hover:bg-gray-100 transition-colors"
                        >
                            Batal
                        </button>

                        <button
                            type="button"
                            @click="submitForm"
                            :disabled="isSubmitting"
                            class="px-6 py-3 bg-tactical-accent text-white rounded-lg font-semibold hover:bg-blue-600 transition-colors flex items-center gap-2 disabled:opacity-50"
                        >
                            <svg v-if="isSubmitting" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ isSubmitting ? 'Menyimpan...' : 'Simpan Perubahan' }}
                        </button>
                    </div>
                </div>

                <!-- Loading Overlay -->
                <div v-if="isSubmitting" class="absolute inset-0 bg-white/80 flex items-center justify-center z-50 rounded-xl">
                    <div class="text-center">
                        <svg class="animate-spin h-12 w-12 text-tactical-accent mx-auto mb-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-navy font-semibold">Menyimpan perubahan...</p>
                    </div>
                </div>
            </div>
        </div>
    </SidebarLayout>
</template>
