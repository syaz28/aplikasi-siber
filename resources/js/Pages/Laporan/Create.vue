<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import SidebarLayout from '@/Layouts/SidebarLayout.vue';
import StepIndicator from '@/Components/Laporan/StepIndicator.vue';
import InputError from '@/Components/InputError.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import FormattedInput from '@/Components/FormattedInput.vue';
import ReviewSummary from '@/Components/ReviewSummary.vue';
import ToastContainer from '@/Components/ToastContainer.vue';
import { useToast } from '@/Composables/useToast';
import { useFormStorage, formatUtils } from '@/Composables/useFormStorage';

// Props from controller
defineProps({
    statusOptions: Object,
    hubunganPelaporOptions: Object,
});

// Toast & Storage
const toast = useToast();
const storage = useFormStorage();

// Current step (0-4, step 4 = review)
const currentStep = ref(0);
const isSubmitting = ref(false);
const errors = ref({});
const apiError = ref(null);
const showDraftModal = ref(false);

// Master data
const masterData = reactive({
    provinsi: [],
    kategori_kejahatan: [],
    anggota: [],
});

// Dynamic dropdown data - Alamat KTP
const kabupaten = ref([]);
const kecamatan = ref([]);
const kelurahan = ref([]);
const jenisKejahatan = ref([]);
const loadingWilayah = reactive({
    kabupaten: false,
    kecamatan: false,
    kelurahan: false,
});

// Dynamic dropdown data - Lokasi Kejadian
const kabupatenKejadian = ref([]);
const kecamatanKejadian = ref([]);
const kelurahanKejadian = ref([]);
const loadingWilayahKejadian = reactive({
    kabupaten: false,
    kecamatan: false,
    kelurahan: false,
});

// Form data with defaults
const getDefaultForm = () => ({
    // Step 0: Administrasi
    nomor_stpa: '',
    tanggal_laporan: new Date().toISOString().split('T')[0],
    petugas_id: '',

    // Step 1: Pelapor
    hubungan_pelapor: 'diri_sendiri',
    pelapor: {
        nik: '',
        nama: '',
        tempat_lahir: '',
        tanggal_lahir: '',
        jenis_kelamin: 'Laki-laki',
        pekerjaan: '',
        telepon: '',
        email: '',
        alamat_ktp: {
            kode_provinsi: '',
            kode_kabupaten: '',
            kode_kecamatan: '',
            kode_kelurahan: '',
            detail_alamat: '',
        },
    },

    // Step 2: Kejadian & Korban
    kategori_kejahatan_id: '',
    jenis_kejahatan_id: '',
    waktu_kejadian: new Date().toISOString().slice(0, 16), // Default to now
    
    // Lokasi kejadian denormalized untuk dashboard/statistik
    kode_provinsi_kejadian: '',
    kode_kabupaten_kejadian: '',
    kode_kecamatan_kejadian: '',
    kode_kelurahan_kejadian: '',
    alamat_kejadian: '',
    
    korban: [{
        orang: {
            nik: '',
            nama: '',
            tempat_lahir: '',
            tanggal_lahir: '',
            jenis_kelamin: 'Laki-laki',
            pekerjaan: '',
            telepon: '',
        },
        kerugian_nominal: 0,
        keterangan: '',
    }],

    // Step 3: Tersangka & Modus
    tersangka: [{
        catatan: '',
        identitas: [{ jenis: 'telepon', nilai: '', platform: '' }],
    }],
    modus: '',
    catatan: '',
});

const form = reactive(getDefaultForm());

// Checkbox for "Pelapor adalah Korban"
const pelaporAdalahKorban = ref(true);

// Identity type options
const identitasTypes = [
    { value: 'telepon', label: 'Nomor Telepon' },
    { value: 'rekening', label: 'Rekening Bank' },
    { value: 'sosmed', label: 'Media Sosial' },
    { value: 'email', label: 'Email' },
    { value: 'ewallet', label: 'E-Wallet' },
    { value: 'lainnya', label: 'Lainnya' },
];

// Formatted options for SearchableSelect
// Format: PANGKAT NAMA (NRP) - tanpa jabatan karena BA PIKET sudah ada di surat
const anggotaOptions = computed(() => {
    return masterData.anggota.map(a => ({
        ...a,
        displayName: `${a.pangkat?.kode || ''} ${a.nama} (${a.nrp || ''})`.trim()
    }));
});

const kategoriOptions = computed(() => {
    return masterData.kategori_kejahatan.map(k => ({
        id: k.id,
        nama: k.nama
    }));
});

// Real-time validation
const validationErrors = reactive({
    petugas_id: '',
    'pelapor.nik': '',
    'pelapor.nama': '',
    'pelapor.telepon': '',
    jenis_kejahatan_id: '',
    modus: '',
});

const validateField = (field, value) => {
    switch(field) {
        case 'petugas_id':
            validationErrors[field] = !value ? 'Petugas harus dipilih' : '';
            break;
        case 'pelapor.nik':
            validationErrors[field] = value && value.length !== 16 ? 'NIK harus 16 digit' : '';
            break;
        case 'pelapor.nama':
            validationErrors[field] = !value ? 'Nama harus diisi' : '';
            break;
        case 'pelapor.telepon':
            validationErrors[field] = value && value.length < 10 ? 'Nomor telepon tidak valid' : '';
            break;
        case 'jenis_kejahatan_id':
            validationErrors[field] = !value ? 'Jenis kejahatan harus dipilih' : '';
            break;
        case 'modus':
            validationErrors[field] = !value ? 'Modus operandi harus diisi' : '';
            break;
    }
};

// Load master data on mount
onMounted(async () => {
    try {
        const res = await axios.get('/api/master/form-init');
        if (res.data.success) {
            masterData.provinsi = res.data.data.provinsi || [];
            masterData.kategori_kejahatan = res.data.data.kategori_kejahatan || [];
            masterData.anggota = res.data.data.anggota || [];
        }
        
        // Load default petugas from localStorage
        const defaultPetugas = storage.getDefaultPetugas();
        if (defaultPetugas && !form.petugas_id) {
            form.petugas_id = defaultPetugas;
        }
        
        // Check for draft
        if (storage.hasDraft()) {
            showDraftModal.value = true;
        }
        
        // Start auto-save
        storage.startAutoSave(form, 30000);
        
    } catch (err) {
        console.error('Error loading master data:', err);
        toast.error('Gagal memuat data master');
    }
});

onUnmounted(() => {
    storage.stopAutoSave();
});

// Load draft
const loadDraft = () => {
    const draft = storage.loadDraft();
    if (draft?.data) {
        Object.assign(form, draft.data);
        toast.success('Draft berhasil dimuat');
    }
    showDraftModal.value = false;
};

const discardDraft = () => {
    storage.clearDraft();
    showDraftModal.value = false;
};

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

const loadJenisKejahatan = async (kategoriId) => {
    if (!kategoriId) { jenisKejahatan.value = []; return; }
    try {
        const res = await axios.get(`/api/master/jenis-kejahatan/${kategoriId}`);
        if (res.data.success) jenisKejahatan.value = res.data.data;
    } catch (err) { console.error(err); }
};

// Loaders for Lokasi Kejadian
const loadKabupatenKejadian = async (kodeProvinsi) => {
    if (!kodeProvinsi) { kabupatenKejadian.value = []; return; }
    loadingWilayahKejadian.kabupaten = true;
    try {
        const res = await axios.get(`/api/master/kabupaten/${kodeProvinsi}`);
        if (res.data.success) kabupatenKejadian.value = res.data.data;
    } catch (err) { console.error(err); }
    finally { loadingWilayahKejadian.kabupaten = false; }
};

const loadKecamatanKejadian = async (kodeKabupaten) => {
    if (!kodeKabupaten) { kecamatanKejadian.value = []; return; }
    loadingWilayahKejadian.kecamatan = true;
    try {
        const res = await axios.get(`/api/master/kecamatan/${kodeKabupaten}`);
        if (res.data.success) kecamatanKejadian.value = res.data.data;
    } catch (err) { console.error(err); }
    finally { loadingWilayahKejadian.kecamatan = false; }
};

const loadKelurahanKejadian = async (kodeKecamatan) => {
    if (!kodeKecamatan) { kelurahanKejadian.value = []; return; }
    loadingWilayahKejadian.kelurahan = true;
    try {
        const res = await axios.get(`/api/master/kelurahan/${kodeKecamatan}`);
        if (res.data.success) kelurahanKejadian.value = res.data.data;
    } catch (err) { console.error(err); }
    finally { loadingWilayahKejadian.kelurahan = false; }
};

// Watch for cascading changes
watch(() => form.pelapor.alamat_ktp.kode_provinsi, (val) => {
    form.pelapor.alamat_ktp.kode_kabupaten = '';
    form.pelapor.alamat_ktp.kode_kecamatan = '';
    form.pelapor.alamat_ktp.kode_kelurahan = '';
    kecamatan.value = [];
    kelurahan.value = [];
    loadKabupaten(val);
});

watch(() => form.pelapor.alamat_ktp.kode_kabupaten, (val) => {
    form.pelapor.alamat_ktp.kode_kecamatan = '';
    form.pelapor.alamat_ktp.kode_kelurahan = '';
    kelurahan.value = [];
    loadKecamatan(val);
});

watch(() => form.pelapor.alamat_ktp.kode_kecamatan, (val) => {
    form.pelapor.alamat_ktp.kode_kelurahan = '';
    loadKelurahan(val);
});

watch(() => form.kategori_kejahatan_id, (val) => {
    form.jenis_kejahatan_id = '';
    loadJenisKejahatan(val);
});

// Watchers for Lokasi Kejadian cascading
watch(() => form.kode_provinsi_kejadian, (val) => {
    form.kode_kabupaten_kejadian = '';
    form.kode_kecamatan_kejadian = '';
    form.kode_kelurahan_kejadian = '';
    kecamatanKejadian.value = [];
    kelurahanKejadian.value = [];
    loadKabupatenKejadian(val);
});

watch(() => form.kode_kabupaten_kejadian, (val) => {
    form.kode_kecamatan_kejadian = '';
    form.kode_kelurahan_kejadian = '';
    kelurahanKejadian.value = [];
    loadKecamatanKejadian(val);
});

watch(() => form.kode_kecamatan_kejadian, (val) => {
    form.kode_kelurahan_kejadian = '';
    loadKelurahanKejadian(val);
});

// Copy pelapor data to first korban
watch(pelaporAdalahKorban, (val) => {
    if (val && form.korban.length > 0) {
        form.korban[0].orang = { ...form.pelapor };
    }
});

// Real-time validation watchers
watch(() => form.petugas_id, (v) => validateField('petugas_id', v));
watch(() => form.pelapor.nik, (v) => validateField('pelapor.nik', v));
watch(() => form.pelapor.nama, (v) => validateField('pelapor.nama', v));
watch(() => form.pelapor.telepon, (v) => validateField('pelapor.telepon', v));
watch(() => form.jenis_kejahatan_id, (v) => validateField('jenis_kejahatan_id', v));
watch(() => form.modus, (v) => validateField('modus', v));

// Korban management
const addKorban = () => {
    form.korban.push({
        orang: {
            nik: '',
            nama: '',
            tempat_lahir: '',
            tanggal_lahir: '',
            jenis_kelamin: 'Laki-laki',
            pekerjaan: '',
            telepon: '',
        },
        kerugian_nominal: 0,
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

// Navigation
const nextStep = () => {
    // Validate current step before proceeding
    let canProceed = true;
    
    if (currentStep.value === 0) {
        validateField('petugas_id', form.petugas_id);
        if (validationErrors.petugas_id) canProceed = false;
    } else if (currentStep.value === 1) {
        validateField('pelapor.nama', form.pelapor.nama);
        validateField('pelapor.nik', form.pelapor.nik);
        if (validationErrors['pelapor.nama'] || validationErrors['pelapor.nik']) canProceed = false;
    } else if (currentStep.value === 2) {
        validateField('jenis_kejahatan_id', form.jenis_kejahatan_id);
        if (validationErrors.jenis_kejahatan_id) canProceed = false;
    } else if (currentStep.value === 3) {
        validateField('modus', form.modus);
        if (validationErrors.modus) canProceed = false;
    }
    
    if (!canProceed) {
        toast.warning('Mohon lengkapi data yang diperlukan');
        return;
    }
    
    if (currentStep.value < 4) currentStep.value++;
};

const prevStep = () => {
    if (currentStep.value > 0) currentStep.value--;
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

    try {
        const response = await axios.post('/laporan', form);
        
        if (response.data.success) {
            // Save default petugas to localStorage
            storage.saveDefaultPetugas(form.petugas_id);
            
            // Clear draft
            storage.clearDraft();
            
            toast.success('Laporan berhasil disimpan!');
            
            // Open PDF in new tab
            const laporanId = response.data.data.id;
            window.open(`/laporan/${laporanId}/pdf`, '_blank');
            
            // Redirect to index after delay
            setTimeout(() => {
                router.visit('/laporan');
            }, 1000);
        }
    } catch (err) {
        console.error('Submit error:', err);
        if (err.response?.data?.errors) {
            errors.value = err.response.data.errors;
        }
        if (err.response?.data?.message) {
            apiError.value = err.response.data.message;
        }
        toast.error('Gagal menyimpan laporan');
        // Go back to form on error
        currentStep.value = 3;
    } finally {
        isSubmitting.value = false;
    }
};

// Keyboard navigation
const handleKeydown = (event) => {
    if (event.key === 'Enter' && !event.shiftKey) {
        // Don't submit on Enter in textareas
        if (event.target.tagName === 'TEXTAREA') return;
        
        // Move to next input or next step
        const inputs = document.querySelectorAll('input:not([type=hidden]), select, textarea');
        const currentIndex = Array.from(inputs).indexOf(event.target);
        
        if (currentIndex < inputs.length - 1) {
            event.preventDefault();
            inputs[currentIndex + 1].focus();
        }
    }
};
</script>

<template>
    <Head title="Entry Laporan Baru" />

    <SidebarLayout title="Entry Laporan Kejahatan Siber">
        <!-- Toast Container -->
        <ToastContainer />
        
        <!-- Draft Modal -->
        <Teleport to="body">
            <div v-if="showDraftModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                <div class="bg-white rounded-xl shadow-2xl p-6 max-w-md mx-4">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-2 bg-tactical-warning/20 rounded-full">
                            <svg class="w-6 h-6 text-tactical-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-navy">Draft Ditemukan</h3>
                    </div>
                    <p class="text-gray-600 mb-6">
                        Anda memiliki draft laporan yang belum diselesaikan. Mau lanjutkan?
                    </p>
                    <div class="flex gap-3">
                        <button
                            @click="discardDraft"
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
                        >
                            Buang Draft
                        </button>
                        <button
                            @click="loadDraft"
                            class="flex-1 px-4 py-2 bg-tactical-accent text-white rounded-lg hover:bg-blue-600 transition-colors"
                        >
                            Lanjutkan
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <div class="max-w-4xl mx-auto" @keydown="handleKeydown">
            <!-- Step Indicator -->
            <StepIndicator :current-step="Math.min(currentStep, 3)" @step-click="currentStep = $event" />

            <!-- Error Alert -->
            <div v-if="apiError" class="mb-6 p-4 bg-tactical-danger/10 border border-tactical-danger rounded-lg text-tactical-danger flex items-center gap-3">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ apiError }}
            </div>

            <!-- Form Card -->
            <div class="bg-white rounded-xl shadow-tactical border border-tactical-border overflow-hidden">
                
                <!-- Step 0: Administrasi -->
                <div v-show="currentStep === 0" class="p-6">
                    <div class="bg-navy px-4 py-3 -mx-6 -mt-6 mb-6 border-l-4 border-tactical-accent">
                        <h3 class="text-lg font-bold text-white">Step 1: Data Administrasi</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nomor STPA (Opsional)</label>
                            <input
                                type="text"
                                v-model="form.nomor_stpa"
                                class="w-full rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent"
                                placeholder="Kosongkan jika belum ada"
                            />
                            <p class="mt-1 text-xs text-gray-400">Akan di-generate otomatis jika kosong</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Laporan <span class="text-red-500">*</span></label>
                            <input
                                type="date"
                                v-model="form.tanggal_laporan"
                                class="w-full rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent"
                            />
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Petugas Penerima <span class="text-red-500">*</span></label>
                            <SearchableSelect
                                v-model="form.petugas_id"
                                :options="anggotaOptions"
                                value-key="id"
                                label-key="displayName"
                                display-key="displayName"
                                placeholder="-- Pilih Petugas --"
                                search-placeholder="Ketik nama petugas..."
                                :error="validationErrors.petugas_id"
                            />
                            <p v-if="storage.getDefaultPetugas() && form.petugas_id == storage.getDefaultPetugas()" class="mt-1 text-xs text-tactical-success flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Petugas default (akan diingat)
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Step 1: Data Pelapor -->
                <div v-show="currentStep === 1" class="p-6">
                    <div class="bg-navy px-4 py-3 -mx-6 -mt-6 mb-6 border-l-4 border-tactical-accent">
                        <h3 class="text-lg font-bold text-white">Step 2: Data Pelapor</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <FormattedInput
                            v-model="form.pelapor.nik"
                            type="nik"
                            label="NIK"
                            placeholder="Masukkan 16 digit NIK"
                            required
                            :error="validationErrors['pelapor.nik']"
                        />

                        <FormattedInput
                            v-model="form.pelapor.nama"
                            type="name"
                            label="Nama Lengkap"
                            placeholder="Masukkan nama lengkap"
                            required
                            :error="validationErrors['pelapor.nama']"
                        />

                        <FormattedInput
                            v-model="form.pelapor.tempat_lahir"
                            type="name"
                            label="Tempat Lahir"
                            placeholder="Contoh: Semarang"
                            required
                        />

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Lahir <span class="text-red-500">*</span></label>
                            <input
                                type="date"
                                v-model="form.pelapor.tanggal_lahir"
                                class="w-full rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Kelamin <span class="text-red-500">*</span></label>
                            <select v-model="form.pelapor.jenis_kelamin" class="w-full rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent">
                                <option value="Laki-laki">Laki-laki</option>
                                <option value="Perempuan">Perempuan</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Pekerjaan <span class="text-red-500">*</span></label>
                            <input
                                type="text"
                                v-model="form.pelapor.pekerjaan"
                                class="w-full rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent"
                            />
                        </div>

                        <FormattedInput
                            v-model="form.pelapor.telepon"
                            type="phone"
                            label="Telepon"
                            placeholder="08xxxxxxxxxx"
                            required
                            :error="validationErrors['pelapor.telepon']"
                        />

                        <FormattedInput
                            v-model="form.pelapor.email"
                            type="email"
                            label="Email"
                            placeholder="email@contoh.com"
                        />

                        <!-- Alamat KTP -->
                        <div class="md:col-span-2 border-t pt-4 mt-2">
                            <h4 class="font-semibold text-navy mb-4">📍 Alamat KTP</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <SearchableSelect
                                    v-model="form.pelapor.alamat_ktp.kode_provinsi"
                                    :options="masterData.provinsi"
                                    value-key="kode"
                                    label-key="nama"
                                    placeholder="-- Pilih Provinsi --"
                                    search-placeholder="Ketik nama provinsi..."
                                />

                                <SearchableSelect
                                    v-model="form.pelapor.alamat_ktp.kode_kabupaten"
                                    :options="kabupaten"
                                    value-key="kode"
                                    label-key="nama"
                                    placeholder="-- Pilih Kabupaten --"
                                    search-placeholder="Ketik nama kabupaten..."
                                    :loading="loadingWilayah.kabupaten"
                                    :disabled="!form.pelapor.alamat_ktp.kode_provinsi"
                                />

                                <SearchableSelect
                                    v-model="form.pelapor.alamat_ktp.kode_kecamatan"
                                    :options="kecamatan"
                                    value-key="kode"
                                    label-key="nama"
                                    placeholder="-- Pilih Kecamatan --"
                                    search-placeholder="Ketik nama kecamatan..."
                                    :loading="loadingWilayah.kecamatan"
                                    :disabled="!form.pelapor.alamat_ktp.kode_kabupaten"
                                />

                                <SearchableSelect
                                    v-model="form.pelapor.alamat_ktp.kode_kelurahan"
                                    :options="kelurahan"
                                    value-key="kode"
                                    label-key="nama"
                                    placeholder="-- Pilih Kelurahan --"
                                    search-placeholder="Ketik nama kelurahan..."
                                    :loading="loadingWilayah.kelurahan"
                                    :disabled="!form.pelapor.alamat_ktp.kode_kecamatan"
                                />

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Detail Alamat <span class="text-red-500">*</span></label>
                                    <textarea
                                        v-model="form.pelapor.alamat_ktp.detail_alamat"
                                        rows="2"
                                        class="w-full rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent"
                                        placeholder="Jalan, RT/RW, No. Rumah"
                                    ></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Kejadian & Korban -->
                <div v-show="currentStep === 2" class="p-6">
                    <div class="bg-navy px-4 py-3 -mx-6 -mt-6 mb-6 border-l-4 border-tactical-accent">
                        <h3 class="text-lg font-bold text-white">Step 3: Data Kejadian & Korban</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <SearchableSelect
                            v-model="form.kategori_kejahatan_id"
                            :options="kategoriOptions"
                            value-key="id"
                            label-key="nama"
                            placeholder="-- Pilih Kategori --"
                            search-placeholder="Ketik kategori..."
                        />

                        <SearchableSelect
                            v-model="form.jenis_kejahatan_id"
                            :options="jenisKejahatan"
                            value-key="id"
                            label-key="nama"
                            placeholder="-- Pilih Jenis --"
                            search-placeholder="Ketik jenis kejahatan..."
                            :disabled="!form.kategori_kejahatan_id"
                            :error="validationErrors.jenis_kejahatan_id"
                        />

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Waktu Kejadian <span class="text-red-500">*</span></label>
                            <input
                                type="datetime-local"
                                v-model="form.waktu_kejadian"
                                class="w-full rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Waktu Laporan</label>
                            <input
                                type="date"
                                v-model="form.tanggal_laporan"
                                class="w-full rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent"
                            />
                        </div>
                    </div>

                    <!-- Lokasi Kejadian Section (Denormalized) -->
                    <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                        <h4 class="font-semibold text-navy mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Lokasi Kejadian
                            <span class="text-xs font-normal text-gray-500">(untuk statistik per wilayah)</span>
                        </h4>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <SearchableSelect
                                v-model="form.kode_provinsi_kejadian"
                                :options="masterData.provinsi"
                                value-key="kode"
                                label-key="nama"
                                placeholder="-- Pilih Provinsi --"
                                search-placeholder="Cari provinsi..."
                            />

                            <SearchableSelect
                                v-model="form.kode_kabupaten_kejadian"
                                :options="kabupatenKejadian"
                                value-key="kode"
                                label-key="nama"
                                placeholder="-- Pilih Kabupaten/Kota --"
                                search-placeholder="Cari kabupaten/kota..."
                                :disabled="!form.kode_provinsi_kejadian"
                                :loading="loadingWilayahKejadian.kabupaten"
                            />

                            <SearchableSelect
                                v-model="form.kode_kecamatan_kejadian"
                                :options="kecamatanKejadian"
                                value-key="kode"
                                label-key="nama"
                                placeholder="-- Pilih Kecamatan --"
                                search-placeholder="Cari kecamatan..."
                                :disabled="!form.kode_kabupaten_kejadian"
                                :loading="loadingWilayahKejadian.kecamatan"
                            />

                            <SearchableSelect
                                v-model="form.kode_kelurahan_kejadian"
                                :options="kelurahanKejadian"
                                value-key="kode"
                                label-key="nama"
                                placeholder="-- Pilih Kelurahan --"
                                search-placeholder="Cari kelurahan..."
                                :disabled="!form.kode_kecamatan_kejadian"
                                :loading="loadingWilayahKejadian.kelurahan"
                            />
                        </div>

                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Detail Alamat Kejadian</label>
                            <input
                                type="text"
                                v-model="form.alamat_kejadian"
                                class="w-full rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent"
                                placeholder="Jl. ..., RT/RW, No. ..."
                            />
                        </div>
                    </div>

                    <!-- Korban Section -->
                    <div class="mt-8 border-t pt-6">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="font-semibold text-navy flex items-center gap-2">
                                🎯 Data Korban
                                <span class="text-sm font-normal text-gray-500">({{ form.korban.length }} korban)</span>
                            </h4>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" v-model="pelaporAdalahKorban" class="rounded border-gray-300 text-tactical-accent focus:ring-tactical-accent" />
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

                            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <FormattedInput v-model="korban.orang.nik" type="nik" label="NIK Korban" />
                                <FormattedInput v-model="korban.orang.nama" type="name" label="Nama Korban" placeholder="Nama lengkap korban" />
                                <FormattedInput v-model="korban.orang.tempat_lahir" type="name" label="Tempat Lahir" placeholder="Contoh: Semarang" />
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Lahir</label>
                                    <input type="date" v-model="korban.orang.tanggal_lahir" class="w-full rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Kelamin</label>
                                    <select v-model="korban.orang.jenis_kelamin" class="w-full rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent">
                                        <option value="Laki-laki">Laki-laki</option>
                                        <option value="Perempuan">Perempuan</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Pekerjaan</label>
                                    <input type="text" v-model="korban.orang.pekerjaan" class="w-full rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent" />
                                </div>
                                <FormattedInput v-model="korban.orang.telepon" type="phone" label="Telepon" placeholder="08xxxxxxxxxx" />
                            </div>

                            <FormattedInput
                                v-model="korban.kerugian_nominal"
                                type="currency"
                                label="Kerugian"
                                placeholder="0"
                                required
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

                <!-- Step 3: Tersangka & Modus -->
                <div v-show="currentStep === 3" class="p-6">
                    <div class="bg-navy px-4 py-3 -mx-6 -mt-6 mb-6 border-l-4 border-tactical-accent">
                        <h3 class="text-lg font-bold text-white">Step 4: Data Tersangka & Modus Operandi</h3>
                    </div>

                    <!-- Tersangka Section -->
                    <div class="mb-8">
                        <h4 class="font-semibold text-navy mb-4 flex items-center gap-2">
                            🔍 Data Tersangka
                            <span class="text-sm font-normal text-gray-500">({{ form.tersangka.length }} tersangka)</span>
                        </h4>

                        <div v-for="(tersangka, tIndex) in form.tersangka" :key="tIndex" class="mb-6 p-4 bg-red-50 rounded-lg border border-red-200">
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
                                    <!-- Dynamic input based on type -->
                                    <FormattedInput
                                        v-if="identitas.jenis === 'telepon'"
                                        v-model="identitas.nilai"
                                        type="phone"
                                        placeholder="08xxxxxxxxxx"
                                        class="flex-1 min-w-0"
                                        :show-valid-icon="false"
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
                                        placeholder="Username/ID"
                                    />
                                    <input
                                        type="text"
                                        v-model="identitas.platform"
                                        class="w-full sm:w-32 rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent text-sm"
                                        :placeholder="identitas.jenis === 'rekening' ? 'Nama Bank' : 'Platform'"
                                    />
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
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            📝 Modus Operandi / Kronologi <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            v-model="form.modus"
                            rows="6"
                            class="w-full rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent"
                            :class="{ 'border-tactical-danger': validationErrors.modus }"
                            placeholder="Jelaskan kronologi kejadian secara detail..."
                        ></textarea>
                        <p v-if="validationErrors.modus" class="mt-1 text-sm text-tactical-danger">{{ validationErrors.modus }}</p>
                        <p v-else class="mt-1 text-xs text-gray-400">Minimal 50 karakter. Saat ini: {{ form.modus?.length || 0 }} karakter</p>
                    </div>
                </div>

                <!-- Step 4: Review -->
                <div v-show="currentStep === 4" class="p-6">
                    <ReviewSummary
                        :form="form"
                        :master-data="{ anggota: masterData.anggota, jenisKejahatan }"
                        @back="currentStep = 3"
                        @confirm="submitForm"
                    />
                </div>

                <!-- Navigation Buttons (except on review step) -->
                <div v-if="currentStep < 4" class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-between">
                    <button
                        v-if="currentStep > 0"
                        type="button"
                        @click="prevStep"
                        class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 font-semibold hover:bg-gray-100 transition-colors flex items-center gap-2 min-h-[48px]"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Sebelumnya
                    </button>
                    <div v-else></div>

                    <button
                        type="button"
                        @click="nextStep"
                        class="px-6 py-3 bg-tactical-accent text-white rounded-lg font-semibold hover:bg-blue-600 transition-colors flex items-center gap-2 min-h-[48px]"
                    >
                        {{ currentStep === 3 ? 'Review & Submit' : 'Selanjutnya' }}
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>

                <!-- Loading Overlay -->
                <div v-if="isSubmitting" class="absolute inset-0 bg-white/80 flex items-center justify-center z-50 rounded-xl">
                    <div class="text-center">
                        <svg class="animate-spin h-12 w-12 text-tactical-accent mx-auto mb-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-navy font-semibold">Menyimpan laporan...</p>
                    </div>
                </div>
            </div>

            <!-- Auto-save indicator -->
            <div class="mt-4 text-center text-xs text-gray-400">
                💾 Draft otomatis tersimpan setiap 30 detik
            </div>
        </div>
    </SidebarLayout>
</template>
