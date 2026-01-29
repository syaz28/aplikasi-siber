<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
// Use window.axios which has CSRF token configured in bootstrap.js
const axios = window.axios;
import SidebarLayout from '@/Layouts/SidebarLayout.vue';
import StepIndicator from '@/Components/Laporan/StepIndicator.vue';
import InputError from '@/Components/InputError.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import FormattedInput from '@/Components/FormattedInput.vue';
import PhoneInput from '@/Components/PhoneInput.vue';
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

// Current step (0-3, step 3 = review) - Step 1 (Administrasi) removed
const currentStep = ref(0);
const isSubmitting = ref(false);
const isSuccess = ref(false);
const errors = ref({});
const apiError = ref(null);
const showDraftModal = ref(false);

const masterData = reactive({
    provinsi: [],
    kategori_kejahatan: [],
    anggota: [],
    pekerjaan: [], // Standardized occupation list for dropdown
    pendidikan: [], // Standardized education level list for dropdown
    kabupaten_all: [], // ALL Kabupaten/Kota Indonesia (514 records) for Step 2 location
    platforms: [], // Platform list for identitas tersangka (dependent dropdown)
    countries: [], // Countries for WNA dropdown (193 countries with phone codes)
});

// Dynamic dropdown data - Alamat KTP
const kabupaten = ref([]);
const kecamatan = ref([]);
const kelurahan = ref([]);
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

// Dynamic dropdown data - Alamat Domisili (for WNA's current residence in Indonesia)
const kabupatenDomisili = ref([]);
const kecamatanDomisili = ref([]);
const kelurahanDomisili = ref([]);
const loadingWilayahDomisili = reactive({
    kabupaten: false,
    kecamatan: false,
    kelurahan: false,
});

// Form data with defaults (Step 1 Administrasi removed - auto-generated on backend)
const getDefaultForm = () => ({
    // Petugas (selected from dashboard, not shown in form)
    petugas_id: '',
    
    // Step 0: Pelapor (was Step 1)
    hubungan_pelapor: 'diri_sendiri',
    pelapor: {
        kewarganegaraan: 'WNI', // WNI or WNA
        negara_asal: '', // For WNA only
        nik: '', // NIK for WNI, Passport/KITAS for WNA
        nama: '',
        tempat_lahir: '',
        tanggal_lahir: '',
        jenis_kelamin: 'Laki-laki',
        pekerjaan: '',
        pendidikan: '', // Pendidikan terakhir
        telepon: '',
        alamat_ktp: {
            negara: 'Indonesia', // Default for WNI
            kode_provinsi: '',
            kode_kabupaten: '',
            kode_kecamatan: '',
            kode_kelurahan: '',
            detail_alamat: '',
        },
        // Flag for auto-sync: "Alamat Domisili sama dengan Alamat KTP"
        domisili_same_as_ktp: false,
        // Domisili saat ini di Indonesia (used for both WNI and WNA)
        alamat_domisili: {
            kode_provinsi: '',
            kode_kabupaten: '',
            kode_kecamatan: '',
            kode_kelurahan: '',
            detail_alamat: '',
        },
    },

    // Step 1: Kejadian & Korban (was Step 2)
    kategori_kejahatan_id: '',
    waktu_kejadian: new Date().toISOString().slice(0, 16), // Default to now
    
    // Lokasi kejadian denormalized untuk dashboard/statistik
    kode_provinsi_kejadian: '',
    kode_kabupaten_kejadian: '',
    kode_kecamatan_kejadian: '',
    kode_kelurahan_kejadian: '',
    alamat_kejadian: '',
    
    korban: [{
        orang: {
            kewarganegaraan: 'WNI', // Default WNI
            negara_asal: '', // For WNA only
            nik: '',
            nama: '',
            tempat_lahir: '',
            tanggal_lahir: '',
            jenis_kelamin: 'Laki-laki',
            pekerjaan: '',
            pendidikan: '', // Pendidikan terakhir
            telepon: '',
        },
        kerugian_nominal: '0', // String for FormattedInput component
        keterangan: '',
    }],

    // Step 2: Tersangka & Modus (was Step 3)
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

// Formatted options for SearchableSelect
// Format: PANGKAT NAMA (NRP) - tanpa jabatan karena BA PIKET sudah ada di surat
const anggotaOptions = computed(() => {
    return masterData.anggota.map(a => ({
        ...a,
        displayName: `${a.pangkat || ''} ${a.name} (${a.nrp || ''})`.trim()
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
    modus: '',
});

const validateField = (field, value) => {
    switch(field) {
        case 'petugas_id':
            validationErrors[field] = !value ? 'Petugas harus dipilih' : '';
            break;
        case 'pelapor.nik':
            // Dynamic validation based on citizenship
            if (form.pelapor.kewarganegaraan === 'WNI') {
                validationErrors[field] = value && value.length !== 16 ? 'NIK harus 16 digit' : '';
            } else {
                // WNA: passport can be up to 50 chars
                validationErrors[field] = value && value.length > 50 ? 'Maksimal 50 karakter' : '';
            }
            break;
        case 'pelapor.nama':
            validationErrors[field] = !value ? 'Nama harus diisi' : '';
            break;
        case 'pelapor.telepon':
            validationErrors[field] = value && value.length < 10 ? 'Nomor telepon tidak valid' : '';
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
            masterData.pekerjaan = res.data.data.pekerjaan || []; // Standardized occupation dropdown
            masterData.pendidikan = res.data.data.pendidikan || []; // Standardized education dropdown
            masterData.kabupaten_all = res.data.data.kabupaten_all || []; // ALL Kabupaten/Kota Indonesia (514 records)
            masterData.platforms = res.data.data.platforms || []; // Platform list for identitas tersangka
            masterData.countries = res.data.data.countries || []; // Countries for WNA dropdown (193 countries)
        }
        
        // Get petugas_id from URL query params (from dashboard)
        const urlParams = new URLSearchParams(window.location.search);
        const petugasIdFromUrl = urlParams.get('petugas_id');
        
        if (petugasIdFromUrl) {
            form.petugas_id = petugasIdFromUrl;
            toast.success('Petugas penerima telah dipilih dari dashboard');
        } else {
            // Load default petugas from localStorage
            const defaultPetugas = storage.getDefaultPetugas();
            if (defaultPetugas && !form.petugas_id) {
                form.petugas_id = defaultPetugas;
            }
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
        
        // Safety: Ensure kerugian_nominal is String (fix old drafts with Number type)
        if (form.korban && Array.isArray(form.korban)) {
            form.korban.forEach(k => {
                if (typeof k.kerugian_nominal === 'number') {
                    k.kerugian_nominal = String(k.kerugian_nominal);
                }
            });
        }
        
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

// Loaders for Alamat Domisili (WNA's current residence in Indonesia)
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

// Watchers for Alamat Domisili cascading (WNA's current residence in Indonesia)
// Flag to prevent cascade clear during auto-sync
const isSyncingDomisili = ref(false);

watch(() => form.pelapor.alamat_domisili.kode_provinsi, (val) => {
    // Skip cascade clear if syncing from KTP
    if (isSyncingDomisili.value) return;
    
    form.pelapor.alamat_domisili.kode_kabupaten = '';
    form.pelapor.alamat_domisili.kode_kecamatan = '';
    form.pelapor.alamat_domisili.kode_kelurahan = '';
    kecamatanDomisili.value = [];
    kelurahanDomisili.value = [];
    loadKabupatenDomisili(val);
});

watch(() => form.pelapor.alamat_domisili.kode_kabupaten, (val) => {
    // Skip cascade clear if syncing from KTP
    if (isSyncingDomisili.value) return;
    
    form.pelapor.alamat_domisili.kode_kecamatan = '';
    form.pelapor.alamat_domisili.kode_kelurahan = '';
    kelurahanDomisili.value = [];
    loadKecamatanDomisili(val);
});

watch(() => form.pelapor.alamat_domisili.kode_kecamatan, (val) => {
    // Skip cascade clear if syncing from KTP
    if (isSyncingDomisili.value) return;
    
    form.pelapor.alamat_domisili.kode_kelurahan = '';
    loadKelurahanDomisili(val);
});

// ========================
// WNI Domisili Auto-Sync Logic
// ========================

// Helper function to sync KTP -> Domisili
const syncKtpToDomisili = () => {
    if (form.pelapor.domisili_same_as_ktp && form.pelapor.kewarganegaraan === 'WNI') {
        // Set flag to prevent cascade watchers from clearing child values
        isSyncingDomisili.value = true;
        
        // Copy the dropdown options arrays FIRST (before setting values)
        kabupatenDomisili.value = [...kabupaten.value];
        kecamatanDomisili.value = [...kecamatan.value];
        kelurahanDomisili.value = [...kelurahan.value];
        
        // Copy all address values from KTP to Domisili
        form.pelapor.alamat_domisili.kode_provinsi = form.pelapor.alamat_ktp.kode_provinsi;
        form.pelapor.alamat_domisili.kode_kabupaten = form.pelapor.alamat_ktp.kode_kabupaten;
        form.pelapor.alamat_domisili.kode_kecamatan = form.pelapor.alamat_ktp.kode_kecamatan;
        form.pelapor.alamat_domisili.kode_kelurahan = form.pelapor.alamat_ktp.kode_kelurahan;
        form.pelapor.alamat_domisili.detail_alamat = form.pelapor.alamat_ktp.detail_alamat;
        
        // Reset flag after sync complete (use nextTick to ensure watchers have processed)
        setTimeout(() => {
            isSyncingDomisili.value = false;
        }, 100);
    }
};

// Watch: When checkbox is checked, copy KTP to Domisili
watch(() => form.pelapor.domisili_same_as_ktp, (val) => {
    if (val) {
        syncKtpToDomisili();
    }
});

// Watch: Auto-sync when any KTP field changes while checkbox is checked
watch(
    () => [
        form.pelapor.alamat_ktp.kode_provinsi,
        form.pelapor.alamat_ktp.kode_kabupaten,
        form.pelapor.alamat_ktp.kode_kecamatan,
        form.pelapor.alamat_ktp.kode_kelurahan,
        form.pelapor.alamat_ktp.detail_alamat
    ],
    () => {
        if (form.pelapor.domisili_same_as_ktp && form.pelapor.kewarganegaraan === 'WNI') {
            syncKtpToDomisili();
        }
    }
);

// Auto-sync: When WNA changes negara_asal, update alamat_ktp.negara too
watch(() => form.pelapor.negara_asal, (val) => {
    if (form.pelapor.kewarganegaraan === 'WNA' && val) {
        form.pelapor.alamat_ktp.negara = val;
    }
});

// Auto-sync: Pelapor = Korban based on hubungan_pelapor
watch(() => form.hubungan_pelapor, (val) => {
    if (val === 'diri_sendiri') {
        pelaporAdalahKorban.value = true;
        toast.info('Mode: Melapor untuk diri sendiri');
    } else {
        pelaporAdalahKorban.value = false;
    }
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
watch(() => form.modus, (v) => validateField('modus', v));

// Korban management
const addKorban = () => {
    form.korban.push({
        orang: {
            kewarganegaraan: 'WNI', // Default WNI
            negara_asal: '', // For WNA only
            nik: '',
            nama: '',
            tempat_lahir: '',
            tanggal_lahir: '',
            jenis_kelamin: 'Laki-laki',
            pekerjaan: '',
            pendidikan: '', // Pendidikan terakhir
            telepon: '',
        },
        kerugian_nominal: '0', // String for FormattedInput component
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
    // Safety: Normalize kerugian_nominal to String before validation
    if (form.korban && Array.isArray(form.korban)) {
        form.korban.forEach(k => {
            if (typeof k.kerugian_nominal === 'number') {
                k.kerugian_nominal = String(k.kerugian_nominal);
            }
        });
    }
    
    // Validate current step before proceeding
    let canProceed = true;
    
    if (currentStep.value === 0) {
        // Step 1: Pelapor validation
        validateField('pelapor.nama', form.pelapor.nama);
        validateField('pelapor.nik', form.pelapor.nik);
        if (validationErrors['pelapor.nama'] || validationErrors['pelapor.nik']) canProceed = false;
    } else if (currentStep.value === 1) {
        // Step 2: Kejadian & Korban validation - RELAXED (optional fields)
        // No strict validation, allow user to proceed
        canProceed = true;
    } else if (currentStep.value === 2) {
        // Step 3: Tersangka & Modus validation - RELAXED
        // Backend will handle final validation
        canProceed = true;
    }
    
    if (!canProceed) {
        toast.warning('Mohon lengkapi data yang diperlukan');
        return;
    }
    
    if (currentStep.value < 3) currentStep.value++; // Max step is 3 (review)
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

    // Safety: Normalize kerugian_nominal to String before submit
    if (form.korban && Array.isArray(form.korban)) {
        form.korban.forEach(k => {
            if (typeof k.kerugian_nominal === 'number') {
                k.kerugian_nominal = String(k.kerugian_nominal);
            }
        });
    }

    // Debug: Log form data before submit
    console.log('=== FORM DATA BEFORE SUBMIT ===');
    console.log('Petugas ID:', form.petugas_id);
    console.log('Pelapor Alamat KTP:', form.pelapor.alamat_ktp);
    console.log('Full Form:', JSON.stringify(form, null, 2));

    try {
        const response = await axios.post('/laporan', form);
        
        if (response.data.success) {
            // Save default petugas to localStorage
            storage.saveDefaultPetugas(form.petugas_id);
            
            // Clear draft
            storage.clearDraft();
            
            toast.success('Laporan berhasil disimpan!');
            
            // Show Success UI instead of opening PDF
            isSuccess.value = true;
            
            // Auto redirect after 3 seconds
            setTimeout(() => {
                router.visit('/laporan');
            }, 3000);
        }
    } catch (err) {
        console.error('Submit error:', err);
        
        // Parse validation errors
        if (err.response?.data?.errors) {
            errors.value = err.response.data.errors;
            
            // Log all validation errors to console for debugging
            console.error('❌ VALIDATION ERRORS:', errors.value);
            
            // Create user-friendly error list
            const errorList = Object.entries(errors.value).map(([field, messages]) => {
                const message = Array.isArray(messages) ? messages[0] : messages;
                return `• ${field}: ${message}`;
            });
            
            // Display in alert box
            apiError.value = `Mohon lengkapi/perbaiki data berikut:\n\n${errorList.join('\n')}`;
            
            // Show toast with first error
            const firstError = errorList[0];
            toast.error(`Validasi gagal! ${firstError}`);
        } else if (err.response?.data?.message) {
            apiError.value = err.response.data.message;
            toast.error(err.response.data.message);
        } else {
            apiError.value = 'Terjadi kesalahan saat menyimpan laporan. Silakan coba lagi.';
            toast.error('Gagal menyimpan laporan. Periksa kembali data Anda.');
        }
        
        // Scroll to top to show error message
        window.scrollTo({ top: 0, behavior: 'smooth' });
        
        // Go back to first step (pelapor) so user can see and fix validation issues
        currentStep.value = 0;
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
            <div v-if="apiError" class="mb-6 p-4 bg-red-50 border-2 border-red-500 rounded-lg text-red-700">
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 flex-shrink-0 text-red-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="flex-1">
                        <h4 class="font-bold text-red-800 mb-2">❌ Validasi Gagal</h4>
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
                    <h3 class="text-2xl font-bold text-navy mb-2">Laporan Berhasil Disimpan!</h3>
                    <p class="text-gray-600 mb-8">Data laporan telah masuk ke sistem. Anda akan dialihkan ke halaman arsip dalam beberapa saat.</p>
                    
                    <button 
                        @click="router.visit('/laporan')" 
                        class="px-6 py-3 bg-tactical-accent text-white rounded-lg font-semibold hover:bg-tactical-accent/90 transition-colors"
                    >
                        Kembali ke Arsip Sekarang
                    </button>
                </div>

                <!-- Form Steps View -->
                <div v-else>
                
                <!-- Step 0: Data Pelapor (was Step 1) -->
                <div v-show="currentStep === 0" class="p-6">
                    <div class="bg-navy px-4 py-3 -mx-6 -mt-6 mb-6 border-l-4 border-tactical-accent">
                        <h3 class="text-lg font-bold text-white">Step 1: Data Pelapor</h3>
                    </div>

                    <!-- WNI/WNA Toggle (Compact) - Paling Atas -->
                    <div class="mb-4 px-3 py-2 bg-gray-50 rounded-lg border border-gray-200 inline-block">
                        <div class="flex items-center gap-4">
                            <span class="text-xs font-medium text-gray-500">Kewarganegaraan:</span>
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input
                                    type="radio"
                                    v-model="form.pelapor.kewarganegaraan"
                                    value="WNI"
                                    class="w-3.5 h-3.5 text-tactical-accent focus:ring-tactical-accent"
                                />
                                <span class="text-xs font-medium text-gray-700">🇮🇩 WNI</span>
                            </label>
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input
                                    type="radio"
                                    v-model="form.pelapor.kewarganegaraan"
                                    value="WNA"
                                    class="w-3.5 h-3.5 text-tactical-accent focus:ring-tactical-accent"
                                />
                                <span class="text-xs font-medium text-gray-700">🌍 WNA</span>
                            </label>
                        </div>
                    </div>

                    <!-- Kapasitas Pelapor (Hubungan Pelapor) -->
                    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <label class="block text-sm font-bold text-navy mb-2">
                            Kapasitas Pelapor (Status Hubungan) <span class="text-red-500">*</span>
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

                    <!-- IDENTITY SECTION -->
                    <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <h4 class="font-semibold text-navy mb-4 flex items-center gap-2">
                            👤 Data Identitas
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Dynamic Identity Field: NIK for WNI, Passport for WNA -->
                            <div v-if="form.pelapor.kewarganegaraan === 'WNI'">
                                <FormattedInput
                                    v-model="form.pelapor.nik"
                                    type="nik"
                                    label="NIK"
                                    placeholder="Masukkan 16 digit NIK"
                                    required
                                    :error="validationErrors['pelapor.nik']"
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
                                    placeholder="Contoh: AB1234567"
                                    maxlength="50"
                                />
                                <p class="mt-1 text-xs text-gray-400">Huruf dan angka (alphanumeric)</p>
                                <p v-if="validationErrors['pelapor.nik']" class="mt-1 text-sm text-red-500">{{ validationErrors['pelapor.nik'] }}</p>
                            </div>

                            <!-- Negara Asal (WNA only) - SearchableSelect dropdown -->
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
                                    search-placeholder="Ketik untuk mencari negara..."
                                />
                            </div>

                            <FormattedInput
                                v-model="form.pelapor.nama"
                                type="name"
                                label="Nama Lengkap"
                                placeholder="Masukkan nama lengkap"
                                required
                                :error="validationErrors['pelapor.nama']"
                            />

                            <!-- Tempat Lahir - WNI Only (Kabupaten/Kota Dropdown) -->
                            <div v-if="form.pelapor.kewarganegaraan === 'WNI'">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tempat Lahir <span class="text-red-500">*</span></label>
                                <SearchableSelect
                                    v-model="form.pelapor.tempat_lahir"
                                    :options="masterData.kabupaten_all"
                                    value-key="nama"
                                    label-key="nama"
                                    placeholder="-- Pilih Kota/Kabupaten --"
                                    search-placeholder="Ketik untuk mencari..."
                                />
                            </div>

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

                            <!-- Pekerjaan Dropdown (Standardized) -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Pekerjaan <span class="text-red-500">*</span></label>
                                <SearchableSelect
                                    v-model="form.pelapor.pekerjaan"
                                    :options="masterData.pekerjaan"
                                    value-key="nama"
                                    label-key="nama"
                                    placeholder="-- Pilih Pekerjaan --"
                                    search-placeholder="Ketik untuk mencari pekerjaan..."
                                />
                            </div>

                            <!-- Phone Input: Split with Country Code Dropdown -->
                            <PhoneInput
                                v-model="form.pelapor.telepon"
                                label="Telepon"
                                required
                                :error="validationErrors['pelapor.telepon']"
                            />

                            <!-- Pendidikan Terakhir Dropdown (Standardized) -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Pendidikan Terakhir <span class="text-red-500">*</span></label>
                                <SearchableSelect
                                    v-model="form.pelapor.pendidikan"
                                    :options="masterData.pendidikan"
                                    value-key="nama"
                                    label-key="nama"
                                    placeholder="-- Pilih Pendidikan --"
                                    search-placeholder="Ketik untuk mencari..."
                                />
                            </div>
                        </div>
                    </div>
                    <!-- END IDENTITY SECTION -->

                    <!-- ADDRESS SECTION -->
                    <div class="p-4 bg-gray-50 rounded-lg border border-gray-200 mt-4">
                        <div class="md:col-span-2">
                            <h4 class="font-semibold text-navy mb-4">
                                📍 {{ form.pelapor.kewarganegaraan === 'WNI' ? 'Alamat KTP' : 'Alamat Asal (Negara Asal)' }}
                            </h4>
                            
                            <!-- WNI: Show Region Dropdowns -->
                            <template v-if="form.pelapor.kewarganegaraan === 'WNI'">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Provinsi <span class="text-red-500">*</span></label>
                                        <SearchableSelect
                                            v-model="form.pelapor.alamat_ktp.kode_provinsi"
                                            :options="masterData.provinsi"
                                            value-key="kode"
                                            label-key="nama"
                                            placeholder="-- Pilih Provinsi --"
                                            search-placeholder="Ketik nama provinsi..."
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Kabupaten/Kota <span class="text-red-500">*</span></label>
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
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Kecamatan <span class="text-red-500">*</span></label>
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
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Kelurahan/Desa <span class="text-red-500">*</span></label>
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
                                    </div>

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
                                
                                <!-- WNI: Alamat Domisili Block -->
                                <div class="p-4 bg-blue-50 rounded-lg border border-blue-200 mt-4">
                                    <div class="flex items-center justify-between mb-4">
                                        <h5 class="font-medium text-blue-800 flex items-center gap-2">
                                            🏠 Alamat Domisili (Tempat Tinggal Saat Ini)
                                        </h5>
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                v-model="form.pelapor.domisili_same_as_ktp"
                                                class="w-4 h-4 rounded text-tactical-accent focus:ring-tactical-accent"
                                            />
                                            <span class="text-sm text-blue-700 font-medium">Sama dengan Alamat KTP</span>
                                        </label>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Provinsi <span class="text-red-500">*</span></label>
                                            <SearchableSelect
                                                v-model="form.pelapor.alamat_domisili.kode_provinsi"
                                                :options="masterData.provinsi"
                                                value-key="kode"
                                                label-key="nama"
                                                placeholder="-- Pilih Provinsi --"
                                                search-placeholder="Ketik nama provinsi..."
                                                :disabled="form.pelapor.domisili_same_as_ktp"
                                            />
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Kabupaten/Kota <span class="text-red-500">*</span></label>
                                            <SearchableSelect
                                                v-model="form.pelapor.alamat_domisili.kode_kabupaten"
                                                :options="kabupatenDomisili"
                                                value-key="kode"
                                                label-key="nama"
                                                placeholder="-- Pilih Kabupaten --"
                                                search-placeholder="Ketik nama kabupaten..."
                                                :loading="loadingWilayahDomisili.kabupaten"
                                                :disabled="form.pelapor.domisili_same_as_ktp || !form.pelapor.alamat_domisili.kode_provinsi"
                                            />
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Kecamatan <span class="text-red-500">*</span></label>
                                            <SearchableSelect
                                                v-model="form.pelapor.alamat_domisili.kode_kecamatan"
                                                :options="kecamatanDomisili"
                                                value-key="kode"
                                                label-key="nama"
                                                placeholder="-- Pilih Kecamatan --"
                                                search-placeholder="Ketik nama kecamatan..."
                                                :loading="loadingWilayahDomisili.kecamatan"
                                                :disabled="form.pelapor.domisili_same_as_ktp || !form.pelapor.alamat_domisili.kode_kabupaten"
                                            />
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Kelurahan/Desa <span class="text-red-500">*</span></label>
                                            <SearchableSelect
                                                v-model="form.pelapor.alamat_domisili.kode_kelurahan"
                                                :options="kelurahanDomisili"
                                                value-key="kode"
                                                label-key="nama"
                                                placeholder="-- Pilih Kelurahan --"
                                                search-placeholder="Ketik nama kelurahan..."
                                                :loading="loadingWilayahDomisili.kelurahan"
                                                :disabled="form.pelapor.domisili_same_as_ktp || !form.pelapor.alamat_domisili.kode_kecamatan"
                                            />
                                        </div>

                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Detail Alamat <span class="text-red-500">*</span></label>
                                            <textarea
                                                v-model="form.pelapor.alamat_domisili.detail_alamat"
                                                rows="2"
                                                class="w-full rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent"
                                                :class="{ 'bg-gray-100': form.pelapor.domisili_same_as_ktp }"
                                                placeholder="Jalan, RT/RW, No. Rumah"
                                                :readonly="form.pelapor.domisili_same_as_ktp"
                                            ></textarea>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            
                            <!-- WNA: Only show Domisili di Indonesia (Simplified - No Overseas Address) -->
                            <template v-else>
                                <!-- WNA Domisili in Indonesia (Required) -->
                                <div class="p-4 bg-green-50 rounded-lg border border-green-200 mt-4">
                                    <h5 class="font-medium text-green-800 mb-2 flex items-center gap-2">
                                        🏨 Domisili Saat Ini di Indonesia
                                    </h5>
                                    <p class="text-xs text-green-700 mb-4 flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Masukkan alamat tempat tinggal/hotel saat ini di Indonesia
                                    </p>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <SearchableSelect
                                            v-model="form.pelapor.alamat_domisili.kode_provinsi"
                                            :options="masterData.provinsi"
                                            value-key="kode"
                                            label-key="nama"
                                            placeholder="-- Pilih Provinsi --"
                                            search-placeholder="Ketik nama provinsi..."
                                        />

                                        <SearchableSelect
                                            v-model="form.pelapor.alamat_domisili.kode_kabupaten"
                                            :options="kabupatenDomisili"
                                            value-key="kode"
                                            label-key="nama"
                                            placeholder="-- Pilih Kabupaten/Kota --"
                                            search-placeholder="Ketik nama kabupaten..."
                                            :loading="loadingWilayahDomisili.kabupaten"
                                            :disabled="!form.pelapor.alamat_domisili.kode_provinsi"
                                        />

                                        <SearchableSelect
                                            v-model="form.pelapor.alamat_domisili.kode_kecamatan"
                                            :options="kecamatanDomisili"
                                            value-key="kode"
                                            label-key="nama"
                                            placeholder="-- Pilih Kecamatan --"
                                            search-placeholder="Ketik nama kecamatan..."
                                            :loading="loadingWilayahDomisili.kecamatan"
                                            :disabled="!form.pelapor.alamat_domisili.kode_kabupaten"
                                        />

                                        <SearchableSelect
                                            v-model="form.pelapor.alamat_domisili.kode_kelurahan"
                                            :options="kelurahanDomisili"
                                            value-key="kode"
                                            label-key="nama"
                                            placeholder="-- Pilih Kelurahan --"
                                            search-placeholder="Ketik nama kelurahan..."
                                            :loading="loadingWilayahDomisili.kelurahan"
                                            :disabled="!form.pelapor.alamat_domisili.kode_kecamatan"
                                        />

                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Detail Alamat <span class="text-red-500">*</span></label>
                                            <textarea
                                                v-model="form.pelapor.alamat_domisili.detail_alamat"
                                                rows="2"
                                                class="w-full rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent"
                                                placeholder="Nama Hotel/Apartemen, Nomor Kamar, Jalan, RT/RW"
                                            ></textarea>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Kejadian & Korban -->
                <div v-show="currentStep === 1" class="p-6">
                    <div class="bg-navy px-4 py-3 -mx-6 -mt-6 mb-6 border-l-4 border-tactical-accent">
                        <h3 class="text-lg font-bold text-white">Step 2: Data Kejadian & Korban</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kategori Kejahatan <span class="text-red-500">*</span></label>
                            <SearchableSelect
                                v-model="form.kategori_kejahatan_id"
                                :options="masterData.kategori_kejahatan"
                                value-key="id"
                                label-key="nama"
                                placeholder="-- Pilih Kategori Kejahatan --"
                                search-placeholder="Ketik untuk mencari..."
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Waktu Kejadian <span class="text-red-500">*</span></label>
                            <input
                                type="datetime-local"
                                v-model="form.waktu_kejadian"
                                class="w-full rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent"
                                required
                            />
                        </div>
                    </div>

                    <!-- Info: Tanggal Laporan Auto-Generated -->
                    <div class="mt-6 p-3 bg-green-50 rounded-lg border border-green-200 flex items-center gap-3">
                        <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="text-sm">
                            <span class="font-semibold text-green-800">Tanggal Laporan:</span>
                            <span class="text-green-700 ml-2">{{ new Date().toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }) }}</span>
                            <span class="text-green-600 text-xs ml-2">(Otomatis)</span>
                        </div>
                    </div>

                    <!-- Lokasi Kejadian Section (ALL Indonesia) -->
                    <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                        <h4 class="font-semibold text-navy mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Lokasi Kejadian
                            <span class="text-xs font-normal text-gray-500">(Seluruh Indonesia)</span>
                        </h4>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Kabupaten/Kota Kejadian <span class="text-red-500">*</span>
                                </label>
                                <SearchableSelect
                                    v-model="form.kode_kabupaten_kejadian"
                                    :options="masterData.kabupaten_all"
                                    value-key="kode"
                                    label-key="nama"
                                    placeholder="-- Pilih Kabupaten/Kota (Ketik untuk cari) --"
                                    search-placeholder="Contoh: Jakarta Selatan, Semarang, Surabaya..."
                                />
                                <p class="mt-1 text-xs text-gray-500">
                                    🔍 Ketik nama kota/kabupaten di seluruh Indonesia (514 pilihan)
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Detail Alamat Kejadian</label>
                                <input
                                    type="text"
                                    v-model="form.alamat_kejadian"
                                    class="w-full rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent"
                                    placeholder="Jl. ..., RT/RW, No. ..., Kecamatan, Kelurahan"
                                />
                            </div>
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

                            <div v-else class="space-y-4">
                                <!-- WNI/WNA Toggle (Compact) -->
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
                                            <span class="text-xs font-medium text-gray-700">🇮🇩 WNI</span>
                                        </label>
                                        <label class="flex items-center gap-1.5 cursor-pointer">
                                            <input
                                                type="radio"
                                                v-model="korban.orang.kewarganegaraan"
                                                value="WNA"
                                                class="w-3.5 h-3.5 text-tactical-accent focus:ring-tactical-accent"
                                            />
                                            <span class="text-xs font-medium text-gray-700">🌍 WNA</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Negara Asal (Only for WNA) -->
                                <div v-if="korban.orang.kewarganegaraan === 'WNA'">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Negara Asal <span class="text-red-500">*</span>
                                    </label>
                                    <SearchableSelect
                                        v-model="korban.orang.negara_asal"
                                        :options="masterData.countries"
                                        value-key="name"
                                        label-key="name"
                                        placeholder="-- Pilih Negara Asal --"
                                        search-placeholder="Ketik nama negara..."
                                    />
                                </div>

                                <!-- Form Fields Grid -->
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
                                        placeholder="Nama lengkap korban" 
                                    />
                                    
                                    <!-- Tempat Lahir - Only for WNI (Kabupaten/Kota Dropdown) -->
                                    <div v-if="korban.orang.kewarganegaraan === 'WNI'">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Tempat Lahir <span class="text-red-500">*</span>
                                        </label>
                                        <SearchableSelect
                                            v-model="korban.orang.tempat_lahir"
                                            :options="masterData.kabupaten_all"
                                            value-key="nama"
                                            label-key="nama"
                                            placeholder="-- Pilih Kota/Kabupaten --"
                                            search-placeholder="Ketik untuk mencari kota..."
                                        />
                                        <p class="mt-1 text-xs text-gray-500">
                                            Pilih kota/kabupaten tempat lahir di Indonesia
                                        </p>
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
                                            <option value="Laki-laki">Laki-laki</option>
                                            <option value="Perempuan">Perempuan</option>
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
                                            search-placeholder="Ketik untuk mencari pekerjaan..."
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Pendidikan Terakhir</label>
                                        <SearchableSelect
                                            v-model="korban.orang.pendidikan"
                                            :options="masterData.pendidikan"
                                            value-key="nama"
                                            label-key="nama"
                                            placeholder="-- Pilih Pendidikan --"
                                            search-placeholder="Ketik untuk mencari..."
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
                <div v-show="currentStep === 2" class="p-6">
                    <div class="bg-navy px-4 py-3 -mx-6 -mt-6 mb-6 border-l-4 border-tactical-accent">
                        <h3 class="text-lg font-bold text-white">Step 3: Data Tersangka & Modus Operandi</h3>
                    </div>

                    <!-- Tersangka Section -->
                    <div class="mb-8">
                        <h4 class="font-semibold text-navy mb-4 flex items-center gap-2">
                            🔍 Data Tersangka
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
                                    <!-- Dynamic input based on type -->
                                    <PhoneInput
                                        v-if="identitas.jenis === 'telepon'"
                                        v-model="identitas.nilai"
                                        :label="null"
                                        placeholder=""
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
                                        v-else-if="identitas.jenis === 'ewallet'"
                                        type="text"
                                        v-model="identitas.nilai"
                                        class="flex-1 min-w-0 rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent text-sm"
                                        placeholder=""
                                    />
                                    <input
                                        v-else
                                        type="text"
                                        v-model="identitas.nilai"
                                        class="flex-1 min-w-0 rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent text-sm"
                                        placeholder="Username/ Link Profil"
                                    />
                                    <div class="w-full sm:w-48 flex-shrink-0">
                                        <SearchableSelect
                                            v-model="identitas.platform"
                                            :options="getFilteredPlatforms(identitas.jenis)"
                                            value-key="nama_platform"
                                            label-key="nama_platform"
                                            :placeholder="identitas.jenis === 'rekening' ? 'Pilih Bank' : 'Pilih Platform'"
                                            :search-placeholder="'Ketik untuk cari...'"
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
                <div v-show="currentStep === 3" class="p-6">
                    <ReviewSummary
                        :form="form"
                        :master-data="masterData"
                        @back="currentStep = 2"
                        @confirm="submitForm"
                    />
                </div>

                <!-- Navigation Buttons (except on review step) -->
                <div v-if="currentStep < 3" class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-between">
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
                        {{ currentStep === 2 ? 'Review & Submit' : 'Selanjutnya' }}
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>

                </div><!-- End v-else (Form Steps View) -->

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
