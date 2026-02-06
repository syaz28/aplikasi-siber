<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
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
import { usePersonDataParser } from '@/Composables/usePersonDataParser';

// Props from controller
defineProps({
    statusOptions: Object,
    hubunganPelaporOptions: Object,
});

// Toast & Storage & Person Data Parser
const toast = useToast();
const storage = useFormStorage();
const personParser = usePersonDataParser();

// Current step (0-3, step 3 = review) - Step 1 (Administrasi) removed
const currentStep = ref(0);
const isSubmitting = ref(false);
const isSuccess = ref(false);
const errors = ref({});
const apiError = ref(null);
const showDraftModal = ref(false);

// Auto-save countdown timer
const AUTO_SAVE_INTERVAL = 30; // seconds
const autoSaveCountdown = ref(AUTO_SAVE_INTERVAL);
const lastSaveTime = ref(null);
let countdownInterval = null;

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
        jenis_kelamin: 'LAKI-LAKI',
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
            jenis_kelamin: 'LAKI-LAKI',
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

// NIK Search for auto-fill pelapor
const nikSearchState = reactive({
    isSearching: false,
    found: false,
    notFound: false,
    foundData: null,
    message: '',
});

// Search orang by NIK/Passport and auto-fill form
const searchOrangByNik = async () => {
    const nik = form.pelapor.nik;
    const isWNI = form.pelapor.kewarganegaraan === 'WNI';
    const minLength = isWNI ? 10 : 5;
    const identityLabel = isWNI ? 'NIK' : 'No. Paspor/ID';
    
    if (!nik || nik.length < minLength) {
        toast.warning(`${identityLabel} harus minimal ${minLength} karakter untuk pencarian`);
        return;
    }
    
    nikSearchState.isSearching = true;
    nikSearchState.found = false;
    nikSearchState.notFound = false;
    nikSearchState.message = '';
    
    try {
        const res = await axios.get('/api/master/orang/search-nik', {
            params: { nik }
        });
        
        if (res.data.success && res.data.found) {
            nikSearchState.found = true;
            nikSearchState.foundData = res.data.data;
            nikSearchState.message = 'Data ditemukan! Klik "Gunakan Data" untuk mengisi otomatis.';
        } else {
            nikSearchState.notFound = true;
            nikSearchState.message = 'Data tidak ditemukan. Silakan isi data secara manual.';
        }
    } catch (error) {
        console.error('Error searching NIK:', error);
        nikSearchState.notFound = true;
        nikSearchState.message = 'Terjadi kesalahan saat mencari data.';
    } finally {
        nikSearchState.isSearching = false;
    }
};

// Auto-fill pelapor form from found data
const autoFillPelapor = async () => {
    const data = nikSearchState.foundData;
    if (!data) return;
    
    // Fill basic info
    form.pelapor.nik = data.nik || '';
    form.pelapor.nama = data.nama || '';
    form.pelapor.kewarganegaraan = data.kewarganegaraan || 'WNI';
    form.pelapor.negara_asal = data.negara_asal || '';
    form.pelapor.tempat_lahir = data.tempat_lahir || '';
    form.pelapor.tanggal_lahir = data.tanggal_lahir || '';
    form.pelapor.jenis_kelamin = data.jenis_kelamin || 'LAKI-LAKI';
    form.pelapor.pekerjaan = data.pekerjaan || '';
    form.pelapor.pendidikan = data.pendidikan || '';
    form.pelapor.telepon = data.telepon || '';
    
    // Fill alamat KTP if available
    if (data.alamat_ktp) {
        // Load wilayah cascade for KTP address
        if (data.alamat_ktp.kode_provinsi) {
            form.pelapor.alamat_ktp.kode_provinsi = data.alamat_ktp.kode_provinsi;
            await loadKabupaten(data.alamat_ktp.kode_provinsi);
            
            if (data.alamat_ktp.kode_kabupaten) {
                form.pelapor.alamat_ktp.kode_kabupaten = data.alamat_ktp.kode_kabupaten;
                await loadKecamatan(data.alamat_ktp.kode_kabupaten);
                
                if (data.alamat_ktp.kode_kecamatan) {
                    form.pelapor.alamat_ktp.kode_kecamatan = data.alamat_ktp.kode_kecamatan;
                    await loadKelurahan(data.alamat_ktp.kode_kecamatan);
                    
                    if (data.alamat_ktp.kode_kelurahan) {
                        form.pelapor.alamat_ktp.kode_kelurahan = data.alamat_ktp.kode_kelurahan;
                    }
                }
            }
        }
        form.pelapor.alamat_ktp.detail_alamat = data.alamat_ktp.detail_alamat || '';
    }
    
    // Fill alamat domisili if available
    if (data.alamat_domisili) {
        if (data.alamat_domisili.kode_provinsi) {
            form.pelapor.alamat_domisili.kode_provinsi = data.alamat_domisili.kode_provinsi;
            await loadKabupatenDomisili(data.alamat_domisili.kode_provinsi);
            
            if (data.alamat_domisili.kode_kabupaten) {
                form.pelapor.alamat_domisili.kode_kabupaten = data.alamat_domisili.kode_kabupaten;
                await loadKecamatanDomisili(data.alamat_domisili.kode_kabupaten);
                
                if (data.alamat_domisili.kode_kecamatan) {
                    form.pelapor.alamat_domisili.kode_kecamatan = data.alamat_domisili.kode_kecamatan;
                    await loadKelurahanDomisili(data.alamat_domisili.kode_kecamatan);
                    
                    if (data.alamat_domisili.kode_kelurahan) {
                        form.pelapor.alamat_domisili.kode_kelurahan = data.alamat_domisili.kode_kelurahan;
                    }
                }
            }
        }
        form.pelapor.alamat_domisili.detail_alamat = data.alamat_domisili.detail_alamat || '';
    }
    
    // Reset search state
    nikSearchState.found = false;
    nikSearchState.foundData = null;
    nikSearchState.message = '';
    
    toast.success('Data pelapor berhasil diisi otomatis!');
};

// Clear NIK search state when NIK changes
const clearNikSearchState = () => {
    nikSearchState.found = false;
    nikSearchState.notFound = false;
    nikSearchState.foundData = null;
    nikSearchState.message = '';
};

// ============================================
// TEXT EXTRACTION - Paste & Auto-fill Feature
// ============================================
const textExtractState = reactive({
    isOpen: false,
    rawText: '',
    extractedData: null,
    isProcessing: false,
});

// Toggle text extraction panel
const toggleTextExtract = () => {
    textExtractState.isOpen = !textExtractState.isOpen;
    if (!textExtractState.isOpen) {
        textExtractState.rawText = '';
        textExtractState.extractedData = null;
    }
};

// ============================================
// HELPER FUNCTIONS (from usePersonDataParser)
// ============================================
const { 
    toUpperCase, 
    cleanText,
    parseProvinsi: _parseProvinsi,
    parseKabupatenKota,
    parseKecamatan,
    parseKelurahan,
    extractProvinsiFromText,
    extractKabupatenKotaFromText,
    extractKecamatanFromText,
    extractKelurahanFromText,
    extractAlamatFromText,
    extractRtRwFromText,
    extractNikFromText,
    extractNamaFromText,
    extractJenisKelaminFromText,
    extractPekerjaanFromText,
    extractTeleponFromText,
    extractTanggalLahirFromText,
    extractTempatLahirFromText: _extractTempatLahirFromText,
    parseIndonesianDate,
    normalizeLaporanFormData,
} = personParser;

// Wrapper functions that use local masterData
const parseProvinsi = (searchName) => _parseProvinsi(searchName, masterData.provinsi);
const extractTempatLahirFromText = (text) => _extractTempatLahirFromText(text, masterData.kabupaten_all);

// Extract data from pasted text
const extractDataFromText = () => {
    const rawText = textExtractState.rawText.trim();
    if (!rawText) {
        toast.warning('Silakan paste text terlebih dahulu');
        return;
    }
    
    textExtractState.isProcessing = true;
    
    try {
        // ========================================
        // USE REUSABLE PARSER FUNCTIONS FROM HELPER
        // ========================================
        const extracted = {
            nik: extractNikFromText(rawText),
            nama: extractNamaFromText(rawText),
            tempat_lahir: extractTempatLahirFromText(rawText),
            tanggal_lahir: extractTanggalLahirFromText(rawText),
            jenis_kelamin: extractJenisKelaminFromText(rawText),
            pekerjaan: extractPekerjaanFromText(rawText),
            telepon: extractTeleponFromText(rawText),
            alamat: extractAlamatFromText(rawText),
            rt_rw: extractRtRwFromText(rawText),
            kelurahan: extractKelurahanFromText(rawText),
            kecamatan: extractKecamatanFromText(rawText),
            kabupaten: extractKabupatenKotaFromText(rawText),
            provinsi: extractProvinsiFromText(rawText),
        };
        
        // ========================================
        // RESULT
        // ========================================
        const hasData = Object.values(extracted).some(v => v !== null);
        if (!hasData) {
            toast.warning('Tidak dapat mengekstrak data dari text. Pastikan format text sesuai.');
            textExtractState.extractedData = null;
        } else {
            textExtractState.extractedData = extracted;
            toast.success('Data berhasil diekstrak! Periksa hasil dan klik "Gunakan Data" untuk mengisi form.');
        }
    } catch (error) {
        console.error('Error extracting data:', error);
        toast.error('Terjadi kesalahan saat memproses text');
    } finally {
        textExtractState.isProcessing = false;
    }
};

// Apply extracted data to form
const applyExtractedData = async () => {
    const data = textExtractState.extractedData;
    if (!data) return;
    
    // ========================================
    // BASIC INFO (all UPPERCASE)
    // ========================================
    if (data.nik) form.pelapor.nik = data.nik;
    if (data.nama) form.pelapor.nama = data.nama.toUpperCase();
    if (data.tanggal_lahir) form.pelapor.tanggal_lahir = data.tanggal_lahir;
    if (data.jenis_kelamin) form.pelapor.jenis_kelamin = data.jenis_kelamin;
    if (data.pekerjaan) form.pelapor.pekerjaan = data.pekerjaan.toUpperCase();
    if (data.telepon) form.pelapor.telepon = data.telepon;
    
    // ========================================
    // TEMPAT LAHIR - Already formatted from extractTempatLahirFromText
    // ========================================
    if (data.tempat_lahir) {
        // Data already contains matched dropdown name or uppercase fallback
        form.pelapor.tempat_lahir = data.tempat_lahir;
    }
    
    // ========================================
    // DETAIL ALAMAT
    // ========================================
    let detailAlamat = [];
    if (data.alamat) detailAlamat.push(data.alamat.toUpperCase());
    if (data.rt_rw) detailAlamat.push(data.rt_rw.toUpperCase());
    
    if (detailAlamat.length > 0) {
        form.pelapor.alamat_ktp.detail_alamat = detailAlamat.join(', ');
    }
    
    // ========================================
    // AUTO-SELECT WILAYAH DROPDOWNS
    // Using reusable parser functions
    // ========================================
    
    // Step 1: Find and select Provinsi
    if (data.provinsi) {
        const foundProv = parseProvinsi(data.provinsi);
        if (foundProv) {
            form.pelapor.alamat_ktp.kode_provinsi = foundProv.kode;
            await loadKabupaten(foundProv.kode);
            
            // Step 2: Find and select Kabupaten/Kota
            if (data.kabupaten) {
                await new Promise(resolve => setTimeout(resolve, 300));
                const foundKab = parseKabupatenKota(data.kabupaten, kabupaten.value);
                if (foundKab) {
                    form.pelapor.alamat_ktp.kode_kabupaten = foundKab.kode;
                    await loadKecamatan(foundKab.kode);
                    
                    // Step 3: Find and select Kecamatan
                    if (data.kecamatan) {
                        await new Promise(resolve => setTimeout(resolve, 300));
                        const foundKec = parseKecamatan(data.kecamatan, kecamatan.value);
                        if (foundKec) {
                            form.pelapor.alamat_ktp.kode_kecamatan = foundKec.kode;
                            await loadKelurahan(foundKec.kode);
                            
                            // Step 4: Find and select Kelurahan
                            if (data.kelurahan) {
                                await new Promise(resolve => setTimeout(resolve, 300));
                                const foundKel = parseKelurahan(data.kelurahan, kelurahan.value);
                                if (foundKel) {
                                    form.pelapor.alamat_ktp.kode_kelurahan = foundKel.kode;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    // Keep panel open but clear extracted data preview
    textExtractState.extractedData = null;
    
    toast.success('Data berhasil diisi ke form!');
};

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
        displayName: `${a.pangkat || ''} ${a.nama || ''} (${a.nrp || ''})`.trim()
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
        
        // Start auto-save with text extraction state
        storage.startAutoSave(form, AUTO_SAVE_INTERVAL * 1000, () => ({
            textExtract: {
                isOpen: textExtractState.isOpen,
                rawText: textExtractState.rawText,
            }
        }));
        
        // Start countdown timer
        startCountdownTimer();
        
    } catch (err) {
        console.error('Error loading master data:', err);
        toast.error('Gagal memuat data master');
    }
});

// Countdown timer functions
const startCountdownTimer = () => {
    autoSaveCountdown.value = AUTO_SAVE_INTERVAL;
    countdownInterval = setInterval(() => {
        autoSaveCountdown.value--;
        if (autoSaveCountdown.value <= 0) {
            // Reset countdown after save
            autoSaveCountdown.value = AUTO_SAVE_INTERVAL;
            lastSaveTime.value = new Date();
        }
    }, 1000);
};

const formatCountdown = computed(() => {
    const seconds = autoSaveCountdown.value;
    return `${seconds}s`;
});

const formatLastSave = computed(() => {
    if (!lastSaveTime.value) return null;
    return lastSaveTime.value.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
});

onUnmounted(() => {
    storage.stopAutoSave();
    if (countdownInterval) {
        clearInterval(countdownInterval);
    }
});

// Flag to skip watchers during draft loading
const isLoadingDraft = ref(false);

// Load draft
const loadDraft = async () => {
    const draft = storage.loadDraft();
    if (draft?.data) {
        isLoadingDraft.value = true;
        
        // Store wilayah codes before assigning (watchers will reset them)
        const savedAlamatKtp = { ...draft.data.pelapor?.alamat_ktp };
        const savedAlamatDomisili = { ...draft.data.pelapor?.alamat_domisili };
        const savedKejadian = {
            kode_provinsi: draft.data.kode_provinsi_kejadian,
            kode_kabupaten: draft.data.kode_kabupaten_kejadian,
            kode_kecamatan: draft.data.kode_kecamatan_kejadian,
            kode_kelurahan: draft.data.kode_kelurahan_kejadian,
        };
        
        Object.assign(form, draft.data);
        
        // Safety: Ensure kerugian_nominal is String (fix old drafts with Number type)
        if (form.korban && Array.isArray(form.korban)) {
            form.korban.forEach(k => {
                if (typeof k.kerugian_nominal === 'number') {
                    k.kerugian_nominal = String(k.kerugian_nominal);
                }
            });
        }
        
        // Load extra state (text extraction)
        if (draft.extraState?.textExtract) {
            textExtractState.isOpen = draft.extraState.textExtract.isOpen || false;
            textExtractState.rawText = draft.extraState.textExtract.rawText || '';
            textExtractState.extractedData = null; // Don't restore extracted data, user should re-extract
        }
        
        // Wait a tick for watchers to settle, then load cascading dropdowns
        await nextTick();
        
        // Load cascading wilayah for Alamat KTP
        if (savedAlamatKtp.kode_provinsi) {
            await loadKabupaten(savedAlamatKtp.kode_provinsi);
            form.pelapor.alamat_ktp.kode_kabupaten = savedAlamatKtp.kode_kabupaten || '';
            
            if (savedAlamatKtp.kode_kabupaten) {
                await loadKecamatan(savedAlamatKtp.kode_kabupaten);
                form.pelapor.alamat_ktp.kode_kecamatan = savedAlamatKtp.kode_kecamatan || '';
                
                if (savedAlamatKtp.kode_kecamatan) {
                    await loadKelurahan(savedAlamatKtp.kode_kecamatan);
                    form.pelapor.alamat_ktp.kode_kelurahan = savedAlamatKtp.kode_kelurahan || '';
                }
            }
        }
        
        // Load cascading wilayah for Alamat Domisili
        if (savedAlamatDomisili.kode_provinsi) {
            await loadKabupatenDomisili(savedAlamatDomisili.kode_provinsi);
            form.pelapor.alamat_domisili.kode_kabupaten = savedAlamatDomisili.kode_kabupaten || '';
            
            if (savedAlamatDomisili.kode_kabupaten) {
                await loadKecamatanDomisili(savedAlamatDomisili.kode_kabupaten);
                form.pelapor.alamat_domisili.kode_kecamatan = savedAlamatDomisili.kode_kecamatan || '';
                
                if (savedAlamatDomisili.kode_kecamatan) {
                    await loadKelurahanDomisili(savedAlamatDomisili.kode_kecamatan);
                    form.pelapor.alamat_domisili.kode_kelurahan = savedAlamatDomisili.kode_kelurahan || '';
                }
            }
        }
        
        // Load cascading wilayah for Lokasi Kejadian
        if (savedKejadian.kode_provinsi) {
            await loadKabupatenKejadian(savedKejadian.kode_provinsi);
            form.kode_kabupaten_kejadian = savedKejadian.kode_kabupaten || '';
            
            if (savedKejadian.kode_kabupaten) {
                await loadKecamatanKejadian(savedKejadian.kode_kabupaten);
                form.kode_kecamatan_kejadian = savedKejadian.kode_kecamatan || '';
                
                if (savedKejadian.kode_kecamatan) {
                    await loadKelurahanKejadian(savedKejadian.kode_kecamatan);
                    form.kode_kelurahan_kejadian = savedKejadian.kode_kelurahan || '';
                }
            }
        }
        
        isLoadingDraft.value = false;
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
    // Skip cascade clear if loading draft
    if (isLoadingDraft.value) return;
    
    form.pelapor.alamat_ktp.kode_kabupaten = '';
    form.pelapor.alamat_ktp.kode_kecamatan = '';
    form.pelapor.alamat_ktp.kode_kelurahan = '';
    kecamatan.value = [];
    kelurahan.value = [];
    loadKabupaten(val);
});

watch(() => form.pelapor.alamat_ktp.kode_kabupaten, (val) => {
    // Skip cascade clear if loading draft
    if (isLoadingDraft.value) return;
    
    form.pelapor.alamat_ktp.kode_kecamatan = '';
    form.pelapor.alamat_ktp.kode_kelurahan = '';
    kelurahan.value = [];
    loadKecamatan(val);
});

watch(() => form.pelapor.alamat_ktp.kode_kecamatan, (val) => {
    // Skip cascade clear if loading draft
    if (isLoadingDraft.value) return;
    
    form.pelapor.alamat_ktp.kode_kelurahan = '';
    loadKelurahan(val);
});

// Watchers for Lokasi Kejadian cascading
watch(() => form.kode_provinsi_kejadian, (val) => {
    // Skip cascade clear if loading draft
    if (isLoadingDraft.value) return;
    
    form.kode_kabupaten_kejadian = '';
    form.kode_kecamatan_kejadian = '';
    form.kode_kelurahan_kejadian = '';
    kecamatanKejadian.value = [];
    kelurahanKejadian.value = [];
    loadKabupatenKejadian(val);
});

watch(() => form.kode_kabupaten_kejadian, (val) => {
    // Skip cascade clear if loading draft
    if (isLoadingDraft.value) return;
    
    form.kode_kecamatan_kejadian = '';
    form.kode_kelurahan_kejadian = '';
    kelurahanKejadian.value = [];
    loadKecamatanKejadian(val);
});

watch(() => form.kode_kecamatan_kejadian, (val) => {
    // Skip cascade clear if loading draft
    if (isLoadingDraft.value) return;
    
    form.kode_kelurahan_kejadian = '';
    loadKelurahanKejadian(val);
});

// Watchers for Alamat Domisili cascading (WNA's current residence in Indonesia)
// Flag to prevent cascade clear during auto-sync
const isSyncingDomisili = ref(false);

watch(() => form.pelapor.alamat_domisili.kode_provinsi, (val) => {
    // Skip cascade clear if syncing from KTP or loading draft
    if (isSyncingDomisili.value || isLoadingDraft.value) return;
    
    form.pelapor.alamat_domisili.kode_kabupaten = '';
    form.pelapor.alamat_domisili.kode_kecamatan = '';
    form.pelapor.alamat_domisili.kode_kelurahan = '';
    kecamatanDomisili.value = [];
    kelurahanDomisili.value = [];
    loadKabupatenDomisili(val);
});

watch(() => form.pelapor.alamat_domisili.kode_kabupaten, (val) => {
    // Skip cascade clear if syncing from KTP or loading draft
    if (isSyncingDomisili.value || isLoadingDraft.value) return;
    
    form.pelapor.alamat_domisili.kode_kecamatan = '';
    form.pelapor.alamat_domisili.kode_kelurahan = '';
    kelurahanDomisili.value = [];
    loadKecamatanDomisili(val);
});

watch(() => form.pelapor.alamat_domisili.kode_kecamatan, (val) => {
    // Skip cascade clear if syncing from KTP or loading draft
    if (isSyncingDomisili.value || isLoadingDraft.value) return;
    
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
            jenis_kelamin: 'LAKI-LAKI',
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

    // ========================================
    // NORMALIZE DATA BEFORE SUBMIT
    // - UPPERCASE for identity fields (nama, nik, alamat, etc.)
    // - PRESERVE case for narrative fields (modus, catatan)
    // ========================================
    const normalizedForm = normalizeLaporanFormData(form);

    // Debug: Log form data before submit
    console.log('=== FORM DATA BEFORE SUBMIT (NORMALIZED) ===');
    console.log('Petugas ID:', normalizedForm.petugas_id);
    console.log('Pelapor Alamat KTP:', normalizedForm.pelapor.alamat_ktp);
    console.log('Modus (preserved):', normalizedForm.modus);
    console.log('Full Form:', JSON.stringify(normalizedForm, null, 2));

    try {
        const response = await axios.post('/laporan', normalizedForm);
        
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

                    <!-- NIK/PASSPORT SEARCH SECTION - Auto-fill dari database -->
                    <div class="mb-6 p-4 bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-lg">
                        <div class="flex items-center gap-2 mb-3">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <span class="font-semibold text-green-800">Cari Data Pelapor dari Database</span>
                        </div>
                        <p class="text-sm text-green-700 mb-3">
                            Masukkan {{ form.pelapor.kewarganegaraan === 'WNI' ? 'NIK' : 'No. Paspor/ID Asing' }} untuk mencari data yang sudah ada di sistem. Jika ditemukan, data akan terisi otomatis.
                        </p>
                        <div class="flex items-start gap-2">
                            <div class="flex-1">
                                <!-- WNI: NIK Input - Direct input without FormattedInput to avoid helper text -->
                                <input
                                    v-if="form.pelapor.kewarganegaraan === 'WNI'"
                                    type="text"
                                    v-model="form.pelapor.nik"
                                    @input="clearNikSearchState"
                                    class="w-full h-[42px] rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent"
                                    placeholder="Masukkan 16 digit NIK"
                                    maxlength="16"
                                    inputmode="numeric"
                                    pattern="[0-9]*"
                                />
                                <!-- WNA: Passport/ID Input -->
                                <input
                                    v-else
                                    type="text"
                                    v-model="form.pelapor.nik"
                                    @input="clearNikSearchState"
                                    class="w-full h-[42px] rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent"
                                    placeholder="Masukkan No. Paspor/ID Asing"
                                    maxlength="50"
                                />
                            </div>
                            <button
                                type="button"
                                @click="searchOrangByNik"
                                :disabled="nikSearchState.isSearching || !form.pelapor.nik || form.pelapor.nik.length < 5"
                                class="px-4 py-2 bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white rounded-lg font-medium transition-colors flex items-center gap-2 h-[42px]"
                            >
                                <svg v-if="nikSearchState.isSearching" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <span>{{ nikSearchState.isSearching ? 'Mencari...' : 'Cari' }}</span>
                            </button>
                        </div>
                        
                        <!-- Search Result - Found -->
                        <div v-if="nikSearchState.found" class="mt-4 p-3 bg-white border border-green-300 rounded-lg">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="flex items-center gap-2 text-green-700 font-semibold mb-1">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                        Data Ditemukan!
                                    </div>
                                    <div class="text-sm text-gray-700">
                                        <strong>{{ nikSearchState.foundData?.nama }}</strong>
                                        <span class="text-gray-500"> • {{ nikSearchState.foundData?.jenis_kelamin }}</span>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        {{ nikSearchState.foundData?.tempat_lahir }}, {{ nikSearchState.foundData?.tanggal_lahir }}
                                    </div>
                                    <div class="flex gap-2 mt-2">
                                        <span v-if="nikSearchState.foundData?.is_pelapor" class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-green-100 text-green-700">
                                            Pernah Pelapor
                                        </span>
                                        <span v-if="nikSearchState.foundData?.is_korban" class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-yellow-100 text-yellow-700">
                                            Pernah Korban
                                        </span>
                                        <span v-if="nikSearchState.foundData?.is_tersangka" class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-red-100 text-red-700">
                                            Pernah Tersangka
                                        </span>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @click="autoFillPelapor"
                                    class="px-4 py-2 bg-tactical-accent hover:bg-tactical-accent-dark text-white rounded-lg font-medium text-sm transition-colors"
                                >
                                    Gunakan Data
                                </button>
                            </div>
                        </div>
                        
                        <!-- Search Result - Not Found -->
                        <div v-if="nikSearchState.notFound" class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <div class="flex items-center gap-2 text-yellow-700">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-sm">{{ nikSearchState.message }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- TEXT EXTRACTION SECTION - Paste & Extract -->
                    <div class="mb-6 p-4 bg-gradient-to-r from-purple-50 to-indigo-50 border border-purple-200 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span class="font-semibold text-purple-800">Paste & Ekstrak Data Otomatis</span>
                            </div>
                            <button
                                type="button"
                                @click="toggleTextExtract"
                                class="text-purple-600 hover:text-purple-800 text-sm font-medium flex items-center gap-1"
                            >
                                <span>{{ textExtractState.isOpen ? 'Tutup' : 'Buka' }}</span>
                                <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': textExtractState.isOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                        </div>
                        <p class="text-sm text-purple-700 mt-1">
                            Paste text identitas (dari KTP, dokumen, dll) dan sistem akan mengekstrak data secara otomatis.
                        </p>
                        
                        <!-- Expandable Panel -->
                        <div v-if="textExtractState.isOpen" class="mt-4">
                            <textarea
                                v-model="textExtractState.rawText"
                                rows="6"
                                class="w-full rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 text-sm"
                                placeholder="Paste text identitas di sini, contoh:

NIK: 3374012345678901
Nama: BUDI SANTOSO
Tempat/Tanggal Lahir: SEMARANG, 15-05-1990
Jenis Kelamin: LAKI-LAKI
Alamat: Jl. Pemuda No. 123
RT/RW: 001/002
Kel. Sekayu Kec. Semarang Tengah
Kota Semarang, Jawa Tengah
Pekerjaan: Wiraswasta
No. HP: 081234567890"
                            ></textarea>
                            
                            <div class="flex gap-2 mt-3">
                                <button
                                    type="button"
                                    @click="extractDataFromText"
                                    :disabled="textExtractState.isProcessing || !textExtractState.rawText.trim()"
                                    class="px-4 py-2 bg-purple-600 hover:bg-purple-700 disabled:bg-gray-400 text-white rounded-lg font-medium text-sm transition-colors flex items-center gap-2"
                                >
                                    <svg v-if="textExtractState.isProcessing" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    <span>Ekstrak Data</span>
                                </button>
                                <button
                                    type="button"
                                    @click="textExtractState.rawText = ''; textExtractState.extractedData = null;"
                                    class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-medium text-sm transition-colors"
                                >
                                    Reset
                                </button>
                            </div>
                            
                            <!-- Extracted Data Preview -->
                            <div v-if="textExtractState.extractedData" class="mt-4 p-4 bg-white border border-purple-200 rounded-lg">
                                <div class="flex items-center justify-between mb-3">
                                    <h5 class="font-semibold text-purple-800 flex items-center gap-2">
                                        <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                        Data Berhasil Diekstrak
                                    </h5>
                                    <button
                                        type="button"
                                        @click="applyExtractedData"
                                        class="px-4 py-2 bg-tactical-accent hover:bg-tactical-accent-dark text-white rounded-lg font-medium text-sm transition-colors flex items-center gap-2"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Gunakan Data Ini
                                    </button>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                                    <div v-if="textExtractState.extractedData.nik" class="flex">
                                        <span class="font-medium text-gray-600 w-28">NIK:</span>
                                        <span class="text-gray-900">{{ textExtractState.extractedData.nik }}</span>
                                    </div>
                                    <div v-if="textExtractState.extractedData.nama" class="flex">
                                        <span class="font-medium text-gray-600 w-28">Nama:</span>
                                        <span class="text-gray-900">{{ textExtractState.extractedData.nama }}</span>
                                    </div>
                                    <div v-if="textExtractState.extractedData.tempat_lahir" class="flex">
                                        <span class="font-medium text-gray-600 w-28">Tempat Lahir:</span>
                                        <span class="text-gray-900">{{ textExtractState.extractedData.tempat_lahir }}</span>
                                    </div>
                                    <div v-if="textExtractState.extractedData.tanggal_lahir" class="flex">
                                        <span class="font-medium text-gray-600 w-28">Tgl Lahir:</span>
                                        <span class="text-gray-900">{{ textExtractState.extractedData.tanggal_lahir }}</span>
                                    </div>
                                    <div v-if="textExtractState.extractedData.jenis_kelamin" class="flex">
                                        <span class="font-medium text-gray-600 w-28">Jenis Kelamin:</span>
                                        <span class="text-gray-900">{{ textExtractState.extractedData.jenis_kelamin }}</span>
                                    </div>
                                    <div v-if="textExtractState.extractedData.pekerjaan" class="flex">
                                        <span class="font-medium text-gray-600 w-28">Pekerjaan:</span>
                                        <span class="text-gray-900">{{ textExtractState.extractedData.pekerjaan }}</span>
                                    </div>
                                    <div v-if="textExtractState.extractedData.telepon" class="flex">
                                        <span class="font-medium text-gray-600 w-28">Telepon:</span>
                                        <span class="text-gray-900">{{ textExtractState.extractedData.telepon }}</span>
                                    </div>
                                    <div v-if="textExtractState.extractedData.alamat" class="flex md:col-span-2">
                                        <span class="font-medium text-gray-600 w-28">Alamat:</span>
                                        <span class="text-gray-900">{{ textExtractState.extractedData.alamat }}</span>
                                    </div>
                                    <div v-if="textExtractState.extractedData.rt_rw" class="flex">
                                        <span class="font-medium text-gray-600 w-28">RT/RW:</span>
                                        <span class="text-gray-900">{{ textExtractState.extractedData.rt_rw }}</span>
                                    </div>
                                    <div v-if="textExtractState.extractedData.kelurahan" class="flex">
                                        <span class="font-medium text-gray-600 w-28">Kelurahan:</span>
                                        <span class="text-gray-900">{{ textExtractState.extractedData.kelurahan }}</span>
                                    </div>
                                    <div v-if="textExtractState.extractedData.kecamatan" class="flex">
                                        <span class="font-medium text-gray-600 w-28">Kecamatan:</span>
                                        <span class="text-gray-900">{{ textExtractState.extractedData.kecamatan }}</span>
                                    </div>
                                    <div v-if="textExtractState.extractedData.kabupaten" class="flex">
                                        <span class="font-medium text-gray-600 w-28">Kabupaten:</span>
                                        <span class="text-gray-900">{{ textExtractState.extractedData.kabupaten }}</span>
                                    </div>
                                    <div v-if="textExtractState.extractedData.provinsi" class="flex">
                                        <span class="font-medium text-gray-600 w-28">Provinsi:</span>
                                        <span class="text-gray-900">{{ textExtractState.extractedData.provinsi }}</span>
                                    </div>
                                </div>
                                
                                <p class="mt-3 text-xs text-gray-500 italic">
                                    * Periksa data di atas sebelum menggunakan. Beberapa field mungkin perlu dilengkapi secara manual.
                                </p>
                            </div>
                        </div>
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
                                    @update:modelValue="clearNikSearchState"
                                />
                                <p class="mt-1 text-xs text-gray-400">Sudah diisi dari pencarian di atas, atau isi manual</p>
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
                                    <option value="LAKI-LAKI">LAKI-LAKI</option>
                                    <option value="PEREMPUAN">PEREMPUAN</option>
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

        </div>
        
        <!-- Floating Auto-save Countdown Timer -->
        <Teleport to="body">
            <div 
                v-if="!isSuccess"
                class="fixed bottom-4 right-4 z-40"
            >
                <div class="bg-white/95 backdrop-blur-sm border border-gray-200 rounded-lg shadow-lg px-3 py-2 flex items-center gap-2 text-xs">
                    <!-- Countdown circle -->
                    <div class="relative w-8 h-8">
                        <svg class="w-8 h-8 transform -rotate-90" viewBox="0 0 32 32">
                            <!-- Background circle -->
                            <circle 
                                cx="16" cy="16" r="14" 
                                fill="none" 
                                stroke="#e5e7eb" 
                                stroke-width="3"
                            />
                            <!-- Progress circle -->
                            <circle 
                                cx="16" cy="16" r="14" 
                                fill="none" 
                                :stroke="autoSaveCountdown <= 5 ? '#f59e0b' : '#3b82f6'" 
                                stroke-width="3"
                                stroke-linecap="round"
                                :stroke-dasharray="87.96"
                                :stroke-dashoffset="87.96 - (87.96 * autoSaveCountdown / AUTO_SAVE_INTERVAL)"
                                class="transition-all duration-1000"
                            />
                        </svg>
                        <!-- Countdown number -->
                        <span class="absolute inset-0 flex items-center justify-center text-[10px] font-bold text-gray-700">
                            {{ autoSaveCountdown }}
                        </span>
                    </div>
                    
                    <!-- Text -->
                    <div class="flex flex-col">
                        <span class="text-gray-500 font-medium">Auto-save</span>
                        <span v-if="lastSaveTime" class="text-gray-400 text-[10px]">
                            Terakhir: {{ formatLastSave }}
                        </span>
                    </div>
                    
                    <!-- Save icon indicator when saving -->
                    <div v-if="autoSaveCountdown >= AUTO_SAVE_INTERVAL - 1 && lastSaveTime" class="text-green-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </div>
            </div>
        </Teleport>
    </SidebarLayout>
</template>
