<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import SidebarLayout from '@/Layouts/SidebarLayout.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PimpinanLayout from '@/Layouts/PimpinanLayout.vue';

const props = defineProps({
    tersangka: Object, // Paginated data
    filters: Object,
    stats: Object,
    linkedStats: Object,
    linkedGroups: Array,
    jenisOptions: Object,
});

const page = usePage();
const userRole = computed(() => page.props.auth?.user?.role);

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

const search = ref(props.filters?.search || '');
const jenisFilter = ref(props.filters?.jenis || '');
const statusFilter = ref(props.filters?.status || '');
const activeTab = ref(props.filters?.tab || 'all');

// Apply filters
const applyFilters = () => {
    router.get(getBaseUrl(), {
        search: search.value,
        jenis: jenisFilter.value,
        status: statusFilter.value,
        tab: activeTab.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

// Switch tab
const switchTab = (tab) => {
    activeTab.value = tab;
    applyFilters();
};

// Search handler with debounce
let searchTimeout = null;
const handleSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 300);
};

watch(search, handleSearch);
watch(jenisFilter, applyFilters);
watch(statusFilter, applyFilters);

// Clear all filters
const clearFilters = () => {
    search.value = '';
    jenisFilter.value = '';
    statusFilter.value = '';
    // Don't reset tab when clearing filters
    applyFilters();
};

// Tabs
const tabs = [
    { id: 'all', label: 'Semua Tersangka', icon: 'users' },
    { id: 'linked', label: 'Terdeteksi Sama', icon: 'link' },
];

// Format date
const formatDate = (date) => {
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
        telepon: 'bg-blue-100 text-blue-800',
        rekening: 'bg-green-100 text-green-800',
        sosmed: 'bg-purple-100 text-purple-800',
        email: 'bg-yellow-100 text-yellow-800',
        ewallet: 'bg-orange-100 text-orange-800',
        kripto: 'bg-amber-100 text-amber-800',
        marketplace: 'bg-pink-100 text-pink-800',
        website: 'bg-indigo-100 text-indigo-800',
        lainnya: 'bg-gray-100 text-gray-800',
    };
    return classes[jenis] || 'bg-gray-100 text-gray-800';
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
</script>

<template>
    <Head title="Daftar Tersangka" />

    <component :is="LayoutComponent">
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-navy">Daftar Tersangka</h1>
                    <p class="text-gray-500 mt-1">Database tersangka kejahatan siber dengan identitas digital</p>
                </div>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-navy">{{ stats.total }}</p>
                                <p class="text-xs text-gray-500">Total Tersangka</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-navy">{{ stats.identified }}</p>
                                <p class="text-xs text-gray-500">Teridentifikasi</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-navy">{{ stats.unidentified }}</p>
                                <p class="text-xs text-gray-500">Belum Teridentifikasi</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-navy">{{ stats.total_identities }}</p>
                                <p class="text-xs text-gray-500">Total Identitas Digital</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Identity Type Breakdown -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Sebaran Jenis Identitas</h3>
                    <div class="flex flex-wrap gap-2">
                        <div
                            v-for="(count, jenis) in stats.by_identity_type"
                            :key="jenis"
                            :class="getIdentityClass(jenis)"
                            class="px-3 py-1.5 rounded-full text-sm font-medium flex items-center gap-1.5"
                        >
                            <span>{{ getIdentityIcon(jenis) }}</span>
                            <span>{{ jenisOptions[jenis] || jenis }}</span>
                            <span class="bg-white/50 px-1.5 py-0.5 rounded-full text-xs font-bold">{{ count }}</span>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6">
                    <div class="flex border-b border-gray-200">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            @click="switchTab(tab.id)"
                            :class="[
                                'flex items-center gap-2 px-6 py-4 text-sm font-medium border-b-2 transition-colors -mb-px',
                                activeTab === tab.id
                                    ? 'border-tactical-accent text-tactical-accent bg-blue-50/50'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            ]"
                        >
                            <!-- Users Icon -->
                            <svg v-if="tab.icon === 'users'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <!-- Link Icon -->
                            <svg v-if="tab.icon === 'link'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                            </svg>
                            <span>{{ tab.label }}</span>
                            <!-- Badge for linked tab -->
                            <span 
                                v-if="tab.id === 'linked' && linkedStats.total_groups > 0"
                                class="ml-1 px-2 py-0.5 text-xs font-bold rounded-full bg-orange-100 text-orange-700"
                            >
                                {{ linkedStats.total_groups }}
                            </span>
                        </button>
                    </div>

                    <!-- Tab Description -->
                    <div v-if="activeTab === 'linked'" class="px-6 py-4 bg-orange-50 border-b border-orange-100">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-orange-800">Tersangka Terdeteksi Sama</h4>
                                <p class="text-xs text-orange-700 mt-0.5">
                                    Tersangka yang memiliki identitas digital yang sama (rekening, telepon, akun sosmed, dll) di laporan berbeda. 
                                    Total <strong>{{ linkedStats.total_linked_tersangka }}</strong> tersangka dengan <strong>{{ linkedStats.total_shared_identities }}</strong> identitas yang digunakan berulang.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
                    <div class="flex flex-wrap gap-4 items-end">
                        <!-- Search -->
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Cari</label>
                            <div class="relative">
                                <input
                                    v-model="search"
                                    type="text"
                                    placeholder="Cari nama, NIK, rekening, telepon, akun..."
                                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-tactical-accent focus:border-transparent"
                                />
                                <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>

                        <!-- Jenis Filter -->
                        <div class="w-48">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Jenis Identitas</label>
                            <select
                                v-model="jenisFilter"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-tactical-accent focus:border-transparent"
                            >
                                <option value="">Semua Jenis</option>
                                <option v-for="(label, key) in jenisOptions" :key="key" :value="key">
                                    {{ getIdentityIcon(key) }} {{ label }}
                                </option>
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div class="w-44">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Status Identifikasi</label>
                            <select
                                v-model="statusFilter"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-tactical-accent focus:border-transparent"
                            >
                                <option value="">Semua Status</option>
                                <option value="identified">Teridentifikasi</option>
                                <option value="unidentified">Belum Teridentifikasi</option>
                            </select>
                        </div>

                        <!-- Clear Button -->
                        <button
                            v-if="search || jenisFilter || statusFilter"
                            @click="clearFilters"
                            class="px-4 py-2 text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition-colors"
                        >
                            Reset
                        </button>
                    </div>
                </div>

                <!-- Linked Groups View (for linked tab) -->
                <div v-if="activeTab === 'linked' && linkedGroups && linkedGroups.length > 0" class="space-y-4 mb-6">
                    <div 
                        v-for="(group, gIndex) in linkedGroups" 
                        :key="`group-${gIndex}`"
                        class="bg-white rounded-xl shadow-sm border border-orange-200 overflow-hidden"
                    >
                        <!-- Group Header -->
                        <div class="px-4 py-3 bg-gradient-to-r from-orange-50 to-orange-100 border-b border-orange-200">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div :class="[getIdentityClass(group.identity.jenis), 'w-10 h-10 rounded-lg flex items-center justify-center text-xl']">
                                        {{ getIdentityIcon(group.identity.jenis) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">
                                            {{ jenisOptions[group.identity.jenis] || group.identity.jenis }}
                                        </p>
                                        <p class="text-base font-mono text-orange-700 font-bold">
                                            {{ group.identity.nilai }}
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-orange-500 text-white text-sm font-bold rounded-full">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                        </svg>
                                        {{ group.identity.count }} tersangka terkait
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Group Tersangka List -->
                        <div class="divide-y divide-gray-100">
                            <div 
                                v-for="t in group.tersangka" 
                                :key="t.id"
                                class="px-4 py-3 flex items-center justify-between hover:bg-gray-50 transition-colors"
                            >
                                <div class="flex items-center gap-3">
                                    <div :class="[
                                        'w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-bold',
                                        t.is_identified ? 'bg-red-500' : 'bg-gray-400'
                                    ]">
                                        {{ t.nama.charAt(0).toUpperCase() }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ t.nama }}</p>
                                        <div class="flex items-center gap-2 text-xs text-gray-500">
                                            <span v-if="t.nik">NIK: {{ t.nik }}</span>
                                            <span v-if="!t.is_identified" class="text-yellow-600 font-medium">⚠ Belum teridentifikasi</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4">
                                    <div v-if="t.nomor_stpa" class="text-right">
                                        <Link
                                            :href="`/laporan/${t.laporan_id}`"
                                            class="text-xs font-medium text-tactical-accent hover:underline"
                                        >
                                            {{ t.nomor_stpa }}
                                        </Link>
                                        <p class="text-xs text-gray-400">{{ formatDate(t.tanggal_laporan) }}</p>
                                    </div>
                                    <Link
                                        :href="`${getBaseUrl()}/${t.id}`"
                                        class="px-3 py-1.5 bg-tactical-accent text-white text-xs font-medium rounded-lg hover:bg-blue-600 transition-colors"
                                    >
                                        Detail
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty state for linked tab -->
                <div v-if="activeTab === 'linked' && (!linkedGroups || linkedGroups.length === 0)" class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center mb-6">
                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-gray-500 font-medium">Tidak ada tersangka dengan identitas yang sama</p>
                    <p class="text-gray-400 text-sm mt-1">Semua identitas digital tersangka bersifat unik</p>
                </div>

                <!-- Results Table (for all tab) -->
                <div v-if="activeTab === 'all'" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tersangka</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Identitas Digital</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Laporan Terkait</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="item in tersangka.data" :key="item.id" class="hover:bg-gray-50 transition-colors">
                                    <!-- Tersangka Info -->
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div :class="[
                                                'w-10 h-10 rounded-full flex items-center justify-center text-white font-bold',
                                                item.orang ? 'bg-red-500' : 'bg-gray-400'
                                            ]">
                                                {{ item.orang?.nama?.charAt(0).toUpperCase() || '?' }}
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-900">
                                                    {{ item.orang?.nama || 'Belum Teridentifikasi' }}
                                                </p>
                                                <p v-if="item.orang" class="text-xs text-gray-500">
                                                    {{ item.orang.jenis_kelamin }} • {{ item.orang.pekerjaan || '-' }}
                                                </p>
                                                <p v-else class="text-xs text-yellow-600 font-medium">
                                                    ⚠ Identitas belum diketahui
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Identitas Digital -->
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap gap-1.5 max-w-xs">
                                            <template v-if="item.identitas && item.identitas.length > 0">
                                                <span
                                                    v-for="(id, idx) in item.identitas.slice(0, 3)"
                                                    :key="id.id"
                                                    :class="getIdentityClass(id.jenis)"
                                                    class="px-2 py-0.5 rounded text-xs font-medium truncate max-w-[140px]"
                                                    :title="`${id.platform ? id.platform + ': ' : ''}${id.nilai}`"
                                                >
                                                    {{ getIdentityIcon(id.jenis) }} {{ id.platform || id.jenis }}: {{ id.nilai }}
                                                </span>
                                                <span
                                                    v-if="item.identitas.length > 3"
                                                    class="px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600"
                                                >
                                                    +{{ item.identitas.length - 3 }} lainnya
                                                </span>
                                            </template>
                                            <span v-else class="text-xs text-gray-400 italic">
                                                Tidak ada identitas digital
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Laporan Terkait -->
                                    <td class="px-6 py-4">
                                        <div v-if="item.laporan">
                                            <Link
                                                :href="`/laporan/${item.laporan.id}`"
                                                class="text-sm font-medium text-tactical-accent hover:underline"
                                            >
                                                {{ item.laporan.nomor_stpa || `LP-${item.laporan.id}` }}
                                            </Link>
                                            <p class="text-xs text-gray-500 mt-0.5">
                                                {{ item.laporan.kategori_kejahatan?.nama || '-' }}
                                            </p>
                                            <span
                                                :class="getStatusBadge(item.laporan.status).class"
                                                class="inline-block px-2 py-0.5 rounded text-xs font-medium mt-1"
                                            >
                                                {{ getStatusBadge(item.laporan.status).label }}
                                            </span>
                                        </div>
                                        <span v-else class="text-xs text-gray-400">-</span>
                                    </td>

                                    <!-- Tanggal -->
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatDate(item.created_at) }}
                                    </td>

                                    <!-- Aksi -->
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <Link
                                            :href="`${getBaseUrl()}/${item.id}`"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-tactical-accent text-white text-xs font-medium rounded-lg hover:bg-blue-600 transition-colors"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            Detail
                                        </Link>
                                    </td>
                                </tr>

                                <!-- Empty State -->
                                <tr v-if="!tersangka.data || tersangka.data.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center">
                                        <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        <p class="text-gray-500 font-medium">Tidak ada data tersangka</p>
                                        <p class="text-gray-400 text-sm mt-1">Coba ubah filter atau kata kunci pencarian</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="tersangka.data && tersangka.data.length > 0" class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                        <p class="text-sm text-gray-600">
                            Menampilkan {{ tersangka.from }} - {{ tersangka.to }} dari {{ tersangka.total }} tersangka
                        </p>
                        <div class="flex gap-2">
                            <Link
                                v-for="link in tersangka.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                :class="[
                                    'px-3 py-1.5 text-sm rounded-lg transition-colors',
                                    link.active
                                        ? 'bg-tactical-accent text-white'
                                        : link.url
                                            ? 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                            : 'bg-gray-50 text-gray-400 cursor-not-allowed'
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </component>
</template>
