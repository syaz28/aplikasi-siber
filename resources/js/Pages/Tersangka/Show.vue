<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import axios from 'axios';
import SidebarLayout from '@/Layouts/SidebarLayout.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PimpinanLayout from '@/Layouts/PimpinanLayout.vue';
import PhoneInput from '@/Components/PhoneInput.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    tersangka: Object,
    relatedSuspects: Array,
    identityAnalysis: Array,
    timeline: Array,
});

const toast = useToast();
const page = usePage();
const userRole = computed(() => page.props.auth?.user?.role);

// Identify form state
const showIdentifyModal = ref(false);
const isSubmitting = ref(false);
const isSearchingNik = ref(false);
const existingOrang = ref(null);
const identifyForm = reactive({
    nik: '',
    nama: '',
    jenis_kelamin: 'LAKI-LAKI',
    tempat_lahir: '',
    tanggal_lahir: '',
    pekerjaan: '',
    telepon: '',
});
const identifyErrors = ref({});

// Get API base URL based on role
const getApiBaseUrl = () => {
    switch (userRole.value) {
        case 'admin_subdit':
            return '/subdit/tersangka';
        case 'pimpinan':
            return '/pimpinan/tersangka';
        default:
            return '/tersangka';
    }
};

// Search NIK with debounce
let nikSearchTimeout = null;
watch(() => identifyForm.nik, (newNik) => {
    clearTimeout(nikSearchTimeout);
    existingOrang.value = null;
    
    if (newNik.length === 16) {
        nikSearchTimeout = setTimeout(async () => {
            isSearchingNik.value = true;
            try {
                const res = await axios.get(`${getApiBaseUrl()}/search-orang`, { params: { nik: newNik } });
                if (res.data.success && res.data.data) {
                    existingOrang.value = res.data.data;
                    // Auto-fill form with existing data
                    identifyForm.nama = res.data.data.nama || '';
                    identifyForm.jenis_kelamin = res.data.data.jenis_kelamin || 'LAKI-LAKI';
                }
            } catch (err) {
                console.error(err);
            } finally {
                isSearchingNik.value = false;
            }
        }, 500);
    }
});

// Submit identify
const submitIdentify = async () => {
    if (isSubmitting.value) return;
    
    identifyErrors.value = {};
    isSubmitting.value = true;
    
    try {
        const res = await axios.post(`${getApiBaseUrl()}/${props.tersangka.id}/identify`, identifyForm);
        
        if (res.data.success) {
            toast.success(res.data.message);
            showIdentifyModal.value = false;
            // Refresh the page to show updated data
            router.reload();
        }
    } catch (err) {
        if (err.response?.status === 422) {
            identifyErrors.value = err.response.data.errors || {};
            toast.error('Mohon perbaiki data yang salah');
        } else {
            toast.error(err.response?.data?.message || 'Gagal mengidentifikasi tersangka');
        }
    } finally {
        isSubmitting.value = false;
    }
};

// Reset form
const resetIdentifyForm = () => {
    identifyForm.nik = '';
    identifyForm.nama = '';
    identifyForm.jenis_kelamin = 'LAKI-LAKI';
    identifyForm.tempat_lahir = '';
    identifyForm.tanggal_lahir = '';
    identifyForm.pekerjaan = '';
    identifyForm.telepon = '';
    existingOrang.value = null;
    identifyErrors.value = {};
};

// Open identify modal
const openIdentifyModal = () => {
    resetIdentifyForm();
    showIdentifyModal.value = true;
};

// Unidentify
const confirmUnidentify = ref(false);
const unidentify = async () => {
    try {
        const res = await axios.delete(`${getApiBaseUrl()}/${props.tersangka.id}/unidentify`);
        if (res.data.success) {
            toast.success(res.data.message);
            confirmUnidentify.value = false;
            router.reload();
        }
    } catch (err) {
        toast.error(err.response?.data?.message || 'Gagal menghapus identifikasi');
    }
};

// ========== IDENTITY MANAGEMENT ==========
const jenisOptions = {
    telepon: 'Nomor Telepon',
    rekening: 'Nomor Rekening',
    sosmed: 'Akun Media Sosial',
    email: 'Alamat Email',
    ewallet: 'E-Wallet',
    kripto: 'Alamat Kripto',
    marketplace: 'Akun Marketplace',
    website: 'Website/Domain',
    lainnya: 'Lainnya',
};

// Identity modal state
const showIdentityModal = ref(false);
const identityModalMode = ref('add'); // 'add' or 'edit'
const isSubmittingIdentity = ref(false);
const identityForm = reactive({
    id: null,
    jenis: 'telepon',
    nilai: '',
    platform: '',
    nama_akun: '',
    catatan: '',
});
const identityErrors = ref({});

// Delete identity confirmation
const confirmDeleteIdentity = ref(null);

// Reset identity form
const resetIdentityForm = () => {
    identityForm.id = null;
    identityForm.jenis = 'telepon';
    identityForm.nilai = '';
    identityForm.platform = '';
    identityForm.nama_akun = '';
    identityForm.catatan = '';
    identityErrors.value = {};
};

// Open add identity modal
const openAddIdentityModal = () => {
    resetIdentityForm();
    identityModalMode.value = 'add';
    showIdentityModal.value = true;
};

// Open edit identity modal
const openEditIdentityModal = (identity) => {
    identityForm.id = identity.id;
    identityForm.jenis = identity.jenis;
    identityForm.nilai = identity.nilai || '';
    identityForm.platform = identity.platform || '';
    identityForm.nama_akun = identity.nama_akun || '';
    identityForm.catatan = identity.catatan || '';
    identityErrors.value = {};
    identityModalMode.value = 'edit';
    showIdentityModal.value = true;
};

// Submit identity form
const submitIdentity = async () => {
    if (isSubmittingIdentity.value) return;
    
    identityErrors.value = {};
    isSubmittingIdentity.value = true;
    
    try {
        let res;
        if (identityModalMode.value === 'add') {
            res = await axios.post(`${getApiBaseUrl()}/${props.tersangka.id}/identity`, identityForm);
        } else {
            res = await axios.put(`${getApiBaseUrl()}/${props.tersangka.id}/identity/${identityForm.id}`, identityForm);
        }
        
        if (res.data.success) {
            toast.success(res.data.message);
            showIdentityModal.value = false;
            router.reload();
        }
    } catch (err) {
        if (err.response?.status === 422) {
            identityErrors.value = err.response.data.errors || {};
            toast.error('Mohon perbaiki data yang salah');
        } else {
            toast.error(err.response?.data?.message || 'Gagal menyimpan identitas');
        }
    } finally {
        isSubmittingIdentity.value = false;
    }
};

// Delete identity
const deleteIdentity = async (identityId) => {
    try {
        const res = await axios.delete(`${getApiBaseUrl()}/${props.tersangka.id}/identity/${identityId}`);
        if (res.data.success) {
            toast.success(res.data.message);
            confirmDeleteIdentity.value = null;
            router.reload();
        }
    } catch (err) {
        toast.error(err.response?.data?.message || 'Gagal menghapus identifikasi');
    }
};

// Determine which layout to use based on user role
const LayoutComponent = computed(() => {
    switch (userRole.value) {
        case 'admin':
            return AdminLayout;
        case 'pimpinan':
            return PimpinanLayout;
        default:
            return SidebarLayout;
    }
});

// Get base URL based on role
const getBaseUrl = () => {
    switch (userRole.value) {
        case 'admin':
            return '/admin/tersangka';
        case 'admin_subdit':
            return '/subdit/tersangka';
        case 'pimpinan':
            return '/pimpinan/tersangka';
        default:
            return '/tersangka';
    }
};

// Format date
const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};

// Format date short
const formatDateShort = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

// Get identity type icon
const getIdentityIcon = (jenis) => {
    const icons = {
        telepon: '📱',
        rekening: '🏦',
        sosmed: '📲',
        email: '📧',
        ewallet: '💳',
        kripto: '🪙',
        marketplace: '🛒',
        website: '🌐',
        lainnya: '📋',
    };
    return icons[jenis] || '📋';
};

// Get identity type color class
const getIdentityClass = (jenis) => {
    const classes = {
        telepon: 'bg-blue-100 text-blue-800 border-blue-200',
        rekening: 'bg-green-100 text-green-800 border-green-200',
        sosmed: 'bg-purple-100 text-purple-800 border-purple-200',
        email: 'bg-yellow-100 text-yellow-800 border-yellow-200',
        ewallet: 'bg-orange-100 text-orange-800 border-orange-200',
        kripto: 'bg-amber-100 text-amber-800 border-amber-200',
        marketplace: 'bg-pink-100 text-pink-800 border-pink-200',
        website: 'bg-indigo-100 text-indigo-800 border-indigo-200',
        lainnya: 'bg-gray-100 text-gray-800 border-gray-200',
    };
    return classes[jenis] || 'bg-gray-100 text-gray-800 border-gray-200';
};

// Get status badge
const getStatusBadge = (status) => {
    const badges = {
        draft: { class: 'bg-gray-100 text-gray-700', label: 'Draft' },
        submitted: { class: 'bg-blue-100 text-blue-700', label: 'Submitted' },
        verified: { class: 'bg-green-100 text-green-700', label: 'Verified' },
        rejected: { class: 'bg-red-100 text-red-700', label: 'Ditolak' },
        processing: { class: 'bg-yellow-100 text-yellow-700', label: 'Proses' },
        closed: { class: 'bg-purple-100 text-purple-700', label: 'Selesai' },
    };
    return badges[status] || { class: 'bg-gray-100 text-gray-700', label: status };
};

// Calculate statistics
const stats = computed(() => {
    return {
        totalIdentities: props.tersangka?.identitas?.length || 0,
        repeatedIdentities: props.identityAnalysis?.filter(i => i.is_repeated).length || 0,
        linkedReports: props.timeline?.length || 0,
        relatedSuspects: props.relatedSuspects?.length || 0,
    };
});
</script>

<template>
    <Head :title="`Tersangka - ${tersangka?.orang?.nama || 'Detail'}`" />

    <component :is="LayoutComponent">
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Back Button -->
                <div class="mb-4">
                    <Link
                        :href="getBaseUrl()"
                        class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Kembali ke Daftar Tersangka
                    </Link>
                </div>

                <!-- Header Card -->
                <div class="bg-gradient-to-r from-red-600 to-red-700 rounded-xl shadow-lg overflow-hidden mb-6">
                    <div class="px-6 py-8">
                        <div class="flex items-start gap-6">
                            <!-- Avatar -->
                            <div :class="[
                                'w-20 h-20 rounded-full flex items-center justify-center text-3xl font-bold border-4 border-white/20',
                                tersangka.orang ? 'bg-white/20 text-white' : 'bg-yellow-400 text-yellow-800'
                            ]">
                                {{ tersangka.orang?.nama?.charAt(0).toUpperCase() || '?' }}
                            </div>

                            <div class="flex-1 text-white">
                                <div class="flex items-center gap-3 mb-2">
                                    <h1 class="text-2xl font-bold">
                                        {{ tersangka.orang?.nama || 'Tersangka Belum Teridentifikasi' }}
                                    </h1>
                                    <span v-if="tersangka.orang" class="px-2 py-1 bg-green-400 text-green-900 text-xs font-bold rounded-full">
                                        TERIDENTIFIKASI
                                    </span>
                                    <span v-else class="px-2 py-1 bg-yellow-400 text-yellow-900 text-xs font-bold rounded-full">
                                        BELUM TERIDENTIFIKASI
                                    </span>
                                </div>

                                <div v-if="tersangka.orang" class="flex flex-wrap gap-4 text-white/80 text-sm">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        {{ tersangka.orang.jenis_kelamin }}
                                    </span>
                                    <span v-if="tersangka.orang.pekerjaan" class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        {{ tersangka.orang.pekerjaan }}
                                    </span>
                                    <span v-if="tersangka.orang.nik" class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                                        </svg>
                                        NIK: {{ tersangka.orang.nik }}
                                    </span>
                                </div>

                                <p v-if="tersangka.catatan" class="mt-3 text-white/70 text-sm italic">
                                    "{{ tersangka.catatan }}"
                                </p>

                                <!-- Action Buttons -->
                                <div class="mt-4 flex gap-2">
                                    <button
                                        v-if="!tersangka.orang"
                                        @click="openIdentifyModal"
                                        class="inline-flex items-center gap-2 px-4 py-2 bg-green-500 hover:bg-green-600 text-white text-sm font-medium rounded-lg transition-colors"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                        </svg>
                                        Identifikasi Tersangka
                                    </button>
                                    <button
                                        v-if="tersangka.orang"
                                        @click="confirmUnidentify = true"
                                        class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 hover:bg-white/30 text-white text-sm font-medium rounded-lg transition-colors"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6" />
                                        </svg>
                                        Hapus Identifikasi
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Bar -->
                    <div class="bg-black/20 px-6 py-4 grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-white">{{ stats.totalIdentities }}</p>
                            <p class="text-xs text-white/70">Identitas Digital</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-white">{{ stats.repeatedIdentities }}</p>
                            <p class="text-xs text-white/70">Identitas Berulang</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-white">{{ stats.linkedReports }}</p>
                            <p class="text-xs text-white/70">Laporan Terkait</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-white">{{ stats.relatedSuspects }}</p>
                            <p class="text-xs text-white/70">Tersangka Terhubung</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left Column: Identity Analysis -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Digital Identities Section -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                                <div>
                                    <h2 class="text-lg font-bold text-navy flex items-center gap-2">
                                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                        </svg>
                                        Identitas Digital Tersangka
                                    </h2>
                                    <p class="text-sm text-gray-500 mt-1">Daftar identitas digital yang digunakan tersangka</p>
                                </div>
                                <button
                                    @click="openAddIdentityModal"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Tambah Identitas
                                </button>
                            </div>

                            <div class="p-6">
                                <div v-if="identityAnalysis && identityAnalysis.length > 0" class="space-y-3">
                                    <div
                                        v-for="identity in identityAnalysis"
                                        :key="identity.id"
                                        :class="[
                                            'p-4 rounded-lg border-2 transition-all',
                                            getIdentityClass(identity.jenis),
                                            identity.is_repeated ? 'ring-2 ring-red-400 ring-offset-2' : ''
                                        ]"
                                    >
                                        <div class="flex items-start justify-between">
                                            <div class="flex items-start gap-3">
                                                <span class="text-2xl">{{ getIdentityIcon(identity.jenis) }}</span>
                                                <div>
                                                    <p class="font-semibold text-gray-900">{{ identity.jenis_label }}</p>
                                                    <p class="text-lg font-mono font-bold mt-1">{{ identity.nilai }}</p>
                                                    <div class="flex flex-wrap gap-2 mt-2">
                                                        <span v-if="identity.platform" class="px-2 py-0.5 bg-white/50 rounded text-xs font-medium">
                                                            Platform: {{ identity.platform }}
                                                        </span>
                                                        <span v-if="identity.nama_akun" class="px-2 py-0.5 bg-white/50 rounded text-xs font-medium">
                                                            Nama: {{ identity.nama_akun }}
                                                        </span>
                                                    </div>
                                                    <p v-if="identity.catatan" class="text-sm text-gray-600 mt-2 italic">
                                                        {{ identity.catatan }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="flex flex-col items-end gap-2">
                                                <div v-if="identity.is_repeated" class="flex items-center gap-1 text-red-600 font-bold">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                    </svg>
                                                    <span class="text-sm">{{ identity.total_occurrences }}x muncul</span>
                                                </div>
                                                <p v-else class="text-xs text-gray-500">1x (unik)</p>
                                                
                                                <!-- Action buttons -->
                                                <div class="flex items-center gap-1 mt-1">
                                                    <button
                                                        @click="openEditIdentityModal(identity)"
                                                        class="p-1.5 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded transition-colors"
                                                        title="Edit identitas"
                                                    >
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                    </button>
                                                    <button
                                                        @click="confirmDeleteIdentity = identity.id"
                                                        class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded transition-colors"
                                                        title="Hapus identitas"
                                                    >
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-else class="text-center py-8 text-gray-400">
                                    <svg class="w-12 h-12 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p>Tidak ada identitas digital tercatat</p>
                                </div>
                            </div>
                        </div>

                        <!-- Personal Info (if identified) -->
                        <div v-if="tersangka.orang" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                                <h2 class="text-lg font-bold text-navy flex items-center gap-2">
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    Data Pribadi Tersangka
                                </h2>
                            </div>

                            <div class="p-6">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase">Nama Lengkap</p>
                                        <p class="font-medium text-gray-900">{{ tersangka.orang.nama }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase">NIK</p>
                                        <p class="font-mono font-medium text-gray-900">{{ tersangka.orang.nik || '-' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase">Jenis Kelamin</p>
                                        <p class="font-medium text-gray-900">{{ tersangka.orang.jenis_kelamin }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase">Pekerjaan</p>
                                        <p class="font-medium text-gray-900">{{ tersangka.orang.pekerjaan || '-' }}</p>
                                    </div>
                                </div>

                                <!-- Alamat KTP -->
                                <div v-if="tersangka.orang.alamat_ktp" class="mt-4 pt-4 border-t border-gray-100">
                                    <p class="text-xs text-gray-500 uppercase mb-2">Alamat KTP</p>
                                    <p class="text-gray-900">
                                        {{ tersangka.orang.alamat_ktp.detail_alamat }},
                                        {{ tersangka.orang.alamat_ktp.kelurahan?.nama }},
                                        {{ tersangka.orang.alamat_ktp.kecamatan?.nama }},
                                        {{ tersangka.orang.alamat_ktp.kabupaten?.nama }},
                                        {{ tersangka.orang.alamat_ktp.provinsi?.nama }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Related Suspects -->
                        <div v-if="relatedSuspects && relatedSuspects.length > 0" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-red-50">
                                <h2 class="text-lg font-bold text-red-700 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                    </svg>
                                    Tersangka Terhubung (Link Analysis)
                                </h2>
                                <p class="text-sm text-red-600 mt-1">Tersangka lain yang memiliki identitas digital yang sama</p>
                            </div>

                            <div class="p-6 space-y-4">
                                <div
                                    v-for="related in relatedSuspects"
                                    :key="related.tersangka_id"
                                    class="p-4 bg-red-50 rounded-lg border border-red-100"
                                >
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="font-medium text-gray-900">
                                                Laporan: {{ related.laporan?.nomor_stpa || `LP-${related.laporan?.id}` }}
                                            </p>
                                            <p class="text-sm text-gray-500">{{ formatDateShort(related.laporan?.tanggal_laporan) }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-xs text-gray-500">Cocok pada:</p>
                                            <span :class="getIdentityClass(related.matched_on?.jenis)" class="px-2 py-1 rounded text-xs font-medium">
                                                {{ getIdentityIcon(related.matched_on?.jenis) }} {{ related.matched_on?.nilai }}
                                            </span>
                                        </div>
                                    </div>
                                    <Link
                                        :href="`${getBaseUrl()}/${related.tersangka_id}`"
                                        class="mt-2 inline-flex items-center gap-1 text-sm text-red-600 hover:text-red-800 font-medium"
                                    >
                                        Lihat Detail →
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Timeline & Current Report -->
                    <div class="space-y-6">
                        <!-- Current Report -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                                <h2 class="text-lg font-bold text-navy flex items-center gap-2">
                                    <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Laporan Saat Ini
                                </h2>
                            </div>

                            <div class="p-6">
                                <div v-if="tersangka.laporan">
                                    <Link
                                        :href="`/laporan/${tersangka.laporan.id}`"
                                        class="text-lg font-bold text-tactical-accent hover:underline"
                                    >
                                        {{ tersangka.laporan.nomor_stpa || `LP-${tersangka.laporan.id}` }}
                                    </Link>

                                    <div class="mt-3 space-y-2">
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-500">Tanggal:</span>
                                            <span class="font-medium">{{ formatDate(tersangka.laporan.tanggal_laporan) }}</span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-500">Kategori:</span>
                                            <span class="font-medium">{{ tersangka.laporan.kategori_kejahatan?.nama || '-' }}</span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-500">Status:</span>
                                            <span
                                                :class="getStatusBadge(tersangka.laporan.status).class"
                                                class="px-2 py-0.5 rounded text-xs font-medium"
                                            >
                                                {{ getStatusBadge(tersangka.laporan.status).label }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Pelapor -->
                                    <div v-if="tersangka.laporan.pelapor" class="mt-4 pt-4 border-t border-gray-100">
                                        <p class="text-xs text-gray-500 uppercase mb-2">Pelapor</p>
                                        <p class="font-medium text-gray-900">{{ tersangka.laporan.pelapor.nama }}</p>
                                    </div>

                                    <!-- Korban -->
                                    <div v-if="tersangka.laporan.korban && tersangka.laporan.korban.length > 0" class="mt-4 pt-4 border-t border-gray-100">
                                        <p class="text-xs text-gray-500 uppercase mb-2">Korban ({{ tersangka.laporan.korban.length }})</p>
                                        <div class="space-y-1">
                                            <p v-for="korban in tersangka.laporan.korban" :key="korban.id" class="text-sm text-gray-700">
                                                • {{ korban.orang?.nama || 'Tidak diketahui' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Report Timeline -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                                <h2 class="text-lg font-bold text-navy flex items-center gap-2">
                                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Timeline Laporan Terkait
                                </h2>
                                <p class="text-sm text-gray-500 mt-1">Semua laporan yang melibatkan identitas ini</p>
                            </div>

                            <div class="p-6">
                                <div v-if="timeline && timeline.length > 0" class="relative">
                                    <!-- Timeline Line -->
                                    <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200"></div>

                                    <div class="space-y-4">
                                        <div
                                            v-for="report in timeline"
                                            :key="report.id"
                                            :class="[
                                                'relative pl-10',
                                                report.is_current ? 'opacity-100' : 'opacity-70'
                                            ]"
                                        >
                                            <!-- Timeline Dot -->
                                            <div :class="[
                                                'absolute left-2.5 w-3 h-3 rounded-full border-2 border-white',
                                                report.is_current ? 'bg-red-500 ring-2 ring-red-200' : 'bg-gray-400'
                                            ]"></div>

                                            <div :class="[
                                                'p-3 rounded-lg border',
                                                report.is_current ? 'bg-red-50 border-red-200' : 'bg-gray-50 border-gray-200'
                                            ]">
                                                <div class="flex items-center justify-between">
                                                    <Link
                                                        :href="`/laporan/${report.id}`"
                                                        class="font-medium text-tactical-accent hover:underline text-sm"
                                                    >
                                                        {{ report.nomor_stpa || `LP-${report.id}` }}
                                                    </Link>
                                                    <span v-if="report.is_current" class="text-xs text-red-600 font-bold">SAAT INI</span>
                                                </div>
                                                <p class="text-xs text-gray-500 mt-1">{{ formatDateShort(report.tanggal_laporan) }}</p>
                                                <p class="text-xs text-gray-600 mt-1">{{ report.kategori || '-' }}</p>
                                                <span
                                                    :class="getStatusBadge(report.status).class"
                                                    class="inline-block px-2 py-0.5 rounded text-xs font-medium mt-1"
                                                >
                                                    {{ getStatusBadge(report.status).label }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-else class="text-center py-6 text-gray-400">
                                    <p class="text-sm">Tidak ada laporan terkait lainnya</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Identify Modal -->
        <Teleport to="body">
            <div v-if="showIdentifyModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <!-- Backdrop -->
                    <div class="fixed inset-0 bg-black/50" @click="showIdentifyModal = false"></div>

                    <!-- Modal -->
                    <div class="relative bg-white rounded-xl shadow-xl max-w-lg w-full p-6">
                        <h3 class="text-lg font-bold text-navy mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                            Identifikasi Tersangka
                        </h3>

                        <p class="text-sm text-gray-500 mb-4">
                            Isi data minimal untuk mengidentifikasi tersangka. Field dengan tanda * wajib diisi.
                        </p>

                        <!-- Existing Orang Alert -->
                        <div v-if="existingOrang" class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <p class="text-sm text-blue-700 font-medium">
                                Data orang dengan NIK ini sudah ada dalam sistem:
                            </p>
                            <p class="text-sm text-blue-900 mt-1">
                                <strong>{{ existingOrang.nama }}</strong> ({{ existingOrang.jenis_kelamin }})
                            </p>
                            <p class="text-xs text-blue-600 mt-1">
                                Tersangka akan dihubungkan dengan data ini.
                            </p>
                        </div>

                        <form @submit.prevent="submitIdentify" class="space-y-4">
                            <!-- NIK -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    NIK <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input
                                        v-model="identifyForm.nik"
                                        type="text"
                                        maxlength="16"
                                        placeholder="Masukkan 16 digit NIK"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                        :class="{ 'border-red-500': identifyErrors.nik }"
                                    />
                                    <div v-if="isSearchingNik" class="absolute right-3 top-2.5">
                                        <svg class="animate-spin w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <p v-if="identifyErrors.nik" class="text-xs text-red-500 mt-1">{{ identifyErrors.nik[0] }}</p>
                            </div>

                            <!-- Nama -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Nama Lengkap <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="identifyForm.nama"
                                    type="text"
                                    placeholder="Nama sesuai identitas"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent uppercase"
                                    :class="{ 'border-red-500': identifyErrors.nama }"
                                    :disabled="!!existingOrang"
                                />
                                <p v-if="identifyErrors.nama" class="text-xs text-red-500 mt-1">{{ identifyErrors.nama[0] }}</p>
                            </div>

                            <!-- Jenis Kelamin -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Jenis Kelamin <span class="text-red-500">*</span>
                                </label>
                                <select
                                    v-model="identifyForm.jenis_kelamin"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                    :class="{ 'border-red-500': identifyErrors.jenis_kelamin }"
                                    :disabled="!!existingOrang"
                                >
                                    <option value="LAKI-LAKI">LAKI-LAKI</option>
                                    <option value="PEREMPUAN">PEREMPUAN</option>
                                </select>
                                <p v-if="identifyErrors.jenis_kelamin" class="text-xs text-red-500 mt-1">{{ identifyErrors.jenis_kelamin[0] }}</p>
                            </div>

                            <!-- Optional Fields (Collapsible) -->
                            <details class="group">
                                <summary class="cursor-pointer text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1">
                                    <svg class="w-4 h-4 transition-transform group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                    Data tambahan (opsional)
                                </summary>
                                <div class="mt-3 space-y-3 pl-5 border-l-2 border-gray-200">
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 mb-1">Tempat Lahir</label>
                                            <input
                                                v-model="identifyForm.tempat_lahir"
                                                type="text"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm uppercase"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 mb-1">Tanggal Lahir</label>
                                            <input
                                                v-model="identifyForm.tanggal_lahir"
                                                type="date"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                                            />
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 mb-1">Pekerjaan</label>
                                            <input
                                                v-model="identifyForm.pekerjaan"
                                                type="text"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm uppercase"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 mb-1">Telepon</label>
                                            <input
                                                v-model="identifyForm.telepon"
                                                type="text"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </details>

                            <!-- Buttons -->
                            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                                <button
                                    type="button"
                                    @click="showIdentifyModal = false"
                                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    :disabled="isSubmitting"
                                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 flex items-center gap-2"
                                >
                                    <svg v-if="isSubmitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ existingOrang ? 'Hubungkan Data' : 'Simpan Identifikasi' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Confirm Unidentify Modal -->
        <Teleport to="body">
            <div v-if="confirmUnidentify" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="confirmUnidentify = false"></div>

                    <div class="relative bg-white rounded-xl shadow-xl max-w-sm w-full p-6 text-center">
                        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Hapus Identifikasi?</h3>
                        <p class="text-sm text-gray-500 mb-6">
                            Data orang tidak akan dihapus, hanya hubungan dengan tersangka ini yang akan dihapus.
                        </p>
                        <div class="flex justify-center gap-3">
                            <button
                                @click="confirmUnidentify = false"
                                class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50"
                            >
                                Batal
                            </button>
                            <button
                                @click="unidentify"
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
                            >
                                Ya, Hapus
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Identity Add/Edit Modal -->
        <Teleport to="body">
            <div v-if="showIdentityModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <!-- Backdrop -->
                    <div class="fixed inset-0 bg-black/50" @click="showIdentityModal = false"></div>

                    <!-- Modal -->
                    <div class="relative bg-white rounded-xl shadow-xl max-w-lg w-full p-6">
                        <h3 class="text-lg font-bold text-navy mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                            {{ identityModalMode === 'add' ? 'Tambah Identitas Digital' : 'Edit Identitas Digital' }}
                        </h3>

                        <form @submit.prevent="submitIdentity" class="space-y-4">
                            <!-- Jenis Identitas -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Jenis Identitas <span class="text-red-500">*</span>
                                </label>
                                <select
                                    v-model="identityForm.jenis"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    :class="{ 'border-red-500': identityErrors.jenis }"
                                >
                                    <option v-for="(label, key) in jenisOptions" :key="key" :value="key">
                                        {{ label }}
                                    </option>
                                </select>
                                <p v-if="identityErrors.jenis" class="text-xs text-red-500 mt-1">{{ identityErrors.jenis[0] }}</p>
                            </div>

                            <!-- Nilai (Nomor/Akun) -->
                            <div>
                                <label v-if="identityForm.jenis !== 'telepon'" class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ identityForm.jenis === 'rekening' ? 'Nomor Rekening' : 
                                       identityForm.jenis === 'email' ? 'Alamat Email' :
                                       identityForm.jenis === 'kripto' ? 'Alamat Wallet' :
                                       identityForm.jenis === 'website' ? 'URL/Domain' :
                                       'Nilai/Akun' }} <span class="text-red-500">*</span>
                                </label>
                                
                                <!-- Phone Input with country code for telepon -->
                                <PhoneInput
                                    v-if="identityForm.jenis === 'telepon'"
                                    v-model="identityForm.nilai"
                                    label="Nomor Telepon"
                                    :required="true"
                                    :error="identityErrors.nilai ? identityErrors.nilai[0] : ''"
                                    placeholder="812345678"
                                />
                                
                                <!-- Regular input for other types -->
                                <input
                                    v-else
                                    v-model="identityForm.nilai"
                                    type="text"
                                    placeholder="Masukkan nilai identitas"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent uppercase"
                                    :class="{ 'border-red-500': identityErrors.nilai }"
                                />
                                <p v-if="identityErrors.nilai && identityForm.jenis !== 'telepon'" class="text-xs text-red-500 mt-1">{{ identityErrors.nilai[0] }}</p>
                            </div>

                            <!-- Platform (for sosmed, ewallet, marketplace) -->
                            <div v-if="['sosmed', 'ewallet', 'marketplace', 'rekening'].includes(identityForm.jenis)">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ identityForm.jenis === 'rekening' ? 'Nama Bank' : 'Platform' }}
                                </label>
                                <input
                                    v-model="identityForm.platform"
                                    type="text"
                                    :placeholder="identityForm.jenis === 'rekening' ? 'Contoh: BCA, BNI, MANDIRI' : 
                                                  identityForm.jenis === 'sosmed' ? 'Contoh: INSTAGRAM, FACEBOOK, TIKTOK' :
                                                  identityForm.jenis === 'ewallet' ? 'Contoh: GOPAY, OVO, DANA' :
                                                  'Contoh: TOKOPEDIA, SHOPEE'"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent uppercase"
                                />
                            </div>

                            <!-- Nama Akun (for sosmed) -->
                            <div v-if="['sosmed', 'marketplace'].includes(identityForm.jenis)">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Akun</label>
                                <input
                                    v-model="identityForm.nama_akun"
                                    type="text"
                                    placeholder="Nama yang tertera di akun"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                            </div>

                            <!-- Catatan -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                                <textarea
                                    v-model="identityForm.catatan"
                                    rows="2"
                                    placeholder="Catatan tambahan (opsional)"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                ></textarea>
                            </div>

                            <!-- Buttons -->
                            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                                <button
                                    type="button"
                                    @click="showIdentityModal = false"
                                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    :disabled="isSubmittingIdentity"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 flex items-center gap-2"
                                >
                                    <svg v-if="isSubmittingIdentity" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ identityModalMode === 'add' ? 'Tambah Identitas' : 'Simpan Perubahan' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Confirm Delete Identity Modal -->
        <Teleport to="body">
            <div v-if="confirmDeleteIdentity" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="confirmDeleteIdentity = null"></div>

                    <div class="relative bg-white rounded-xl shadow-xl max-w-sm w-full p-6 text-center">
                        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Hapus Identitas Digital?</h3>
                        <p class="text-sm text-gray-500 mb-6">
                            Identitas ini akan dihapus permanen dan tidak dapat dikembalikan.
                        </p>
                        <div class="flex justify-center gap-3">
                            <button
                                @click="confirmDeleteIdentity = null"
                                class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50"
                            >
                                Batal
                            </button>
                            <button
                                @click="deleteIdentity(confirmDeleteIdentity)"
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
                            >
                                Ya, Hapus
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </component>
</template>
