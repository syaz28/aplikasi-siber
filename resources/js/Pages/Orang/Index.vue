<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import SidebarLayout from '@/Layouts/SidebarLayout.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PimpinanLayout from '@/Layouts/PimpinanLayout.vue';

const props = defineProps({
    orang: Object, // Paginated data
    filters: Object,
    counts: Object,
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
            return '/admin/orang';
        case 'admin_subdit':
            return '/subdit/orang';
        case 'pimpinan':
            return '/pimpinan/orang';
        default:
            return '/orang';
    }
};

const activeTab = ref(props.filters?.tab || 'semua');
const search = ref(props.filters?.search || '');

// Tab configuration (Tersangka removed - now has its own module)
const tabs = [
    { id: 'semua', label: 'Semua Orang', icon: 'users' },
    { id: 'korban', label: 'Korban', icon: 'user-minus' },
    { id: 'pelapor', label: 'Pelapor', icon: 'user-check' },
];

// Switch tab
const switchTab = (tabId) => {
    activeTab.value = tabId;
    router.get(getBaseUrl(), {
        tab: tabId,
        search: search.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

// Search handler with debounce
let searchTimeout = null;
const handleSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(getBaseUrl(), {
            tab: activeTab.value,
            search: search.value,
        }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
};

watch(search, handleSearch);

// Format date
const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

// Format currency
const formatRupiah = (amount) => {
    if (!amount) return 'Rp 0';
    return 'Rp ' + parseInt(amount).toLocaleString('id-ID');
};

// Get gender badge
const getGenderBadge = (gender) => {
    if (gender === 'LAKI-LAKI') {
        return { class: 'bg-blue-100 text-blue-800', label: 'L' };
    }
    return { class: 'bg-pink-100 text-pink-800', label: 'P' };
};

// Get role badges for a person
const getRoleBadges = (item) => {
    const badges = [];
    if (item.laporan_sebagai_pelapor_count > 0) {
        badges.push({ class: 'bg-green-100 text-green-800', label: 'Pelapor', count: item.laporan_sebagai_pelapor_count });
    }
    if (item.sebagai_korban_count > 0) {
        badges.push({ class: 'bg-yellow-100 text-yellow-800', label: 'Korban', count: item.sebagai_korban_count });
    }
    if (item.sebagai_tersangka_count > 0) {
        badges.push({ class: 'bg-red-100 text-red-800', label: 'Tersangka', count: item.sebagai_tersangka_count });
    }
    return badges;
};

// Get tab-specific extra info
const getExtraInfo = (item) => {
    if (activeTab.value === 'korban' && item.sebagai_korban_sum_kerugian_nominal) {
        return {
            label: 'Total Kerugian',
            value: formatRupiah(item.sebagai_korban_sum_kerugian_nominal),
            class: 'text-red-600 font-semibold'
        };
    }
    if (activeTab.value === 'tersangka' && item.sebagai_tersangka_count) {
        return {
            label: 'Terlibat',
            value: `${item.sebagai_tersangka_count} kasus`,
            class: 'text-orange-600 font-semibold'
        };
    }
    if (activeTab.value === 'pelapor' && item.laporan_sebagai_pelapor_count) {
        return {
            label: 'Laporan',
            value: `${item.laporan_sebagai_pelapor_count} laporan`,
            class: 'text-blue-600 font-semibold'
        };
    }
    return null;
};
</script>

<template>
    <Head title="Daftar Orang" />

    <component :is="LayoutComponent" title="Daftar Orang">
        <div class="max-w-7xl mx-auto">
            <!-- Page Header with Stats -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-navy mb-2">Daftar Orang</h1>
                <p class="text-gray-500">Database semua orang yang terlibat dalam laporan kejahatan siber</p>
            </div>

            <!-- Tab Navigation -->
            <div class="bg-white rounded-xl shadow-tactical border border-tactical-border mb-6">
                <div class="border-b border-gray-200">
                    <nav class="flex -mb-px overflow-x-auto" aria-label="Tabs">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            @click="switchTab(tab.id)"
                            :class="[
                                'group relative min-w-0 flex-1 overflow-hidden py-4 px-4 text-center text-sm font-medium hover:bg-gray-50 focus:z-10 transition-colors',
                                activeTab === tab.id
                                    ? 'text-tactical-accent border-b-2 border-tactical-accent'
                                    : 'text-gray-500 hover:text-gray-700 border-b-2 border-transparent'
                            ]"
                        >
                            <div class="flex items-center justify-center gap-2">
                                <!-- Icons -->
                                <svg v-if="tab.icon === 'users'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <svg v-else-if="tab.icon === 'exclamation'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <svg v-else-if="tab.icon === 'user-minus'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6" />
                                </svg>
                                <svg v-else-if="tab.icon === 'user-check'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                
                                <span>{{ tab.label }}</span>
                                
                                <!-- Count Badge -->
                                <span :class="[
                                    'ml-1 px-2 py-0.5 text-xs rounded-full',
                                    activeTab === tab.id
                                        ? 'bg-tactical-accent text-white'
                                        : 'bg-gray-100 text-gray-600'
                                ]">
                                    {{ counts[tab.id] || 0 }}
                                </span>
                            </div>
                        </button>
                    </nav>
                </div>

                <!-- Search & Filter Bar -->
                <div class="p-4 border-b border-gray-100">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Cari berdasarkan nama, NIK, telepon, atau email..."
                            class="block w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-tactical-accent focus:border-transparent transition-all"
                        />
                    </div>
                </div>

                <!-- Data Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Nama
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    NIK
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Telepon
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Alamat
                                </th>
                                <th v-if="activeTab === 'semua'" scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Keterlibatan
                                </th>
                                <th v-else scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Info
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="item in orang.data" :key="item.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div :class="[
                                                'h-10 w-10 rounded-full flex items-center justify-center text-white font-semibold',
                                                item.jenis_kelamin === 'LAKI-LAKI' ? 'bg-blue-500' : 'bg-pink-500'
                                            ]">
                                                {{ item.nama?.charAt(0).toUpperCase() }}
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ item.nama }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ item.jenis_kelamin }} • {{ item.pekerjaan || '-' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 font-mono">{{ item.nik || '-' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ item.telepon || '-' }}</div>
                                    <div v-if="item.email" class="text-xs text-gray-500">{{ item.email }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 max-w-xs truncate">
                                        {{ item.alamat_ktp?.kabupaten?.nama || '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <!-- Show role badges for 'semua' tab -->
                                    <div v-if="activeTab === 'semua'" class="flex flex-wrap gap-1">
                                        <span
                                            v-for="badge in getRoleBadges(item)"
                                            :key="badge.label"
                                            :class="[badge.class, 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium']"
                                        >
                                            {{ badge.label }} ({{ badge.count }})
                                        </span>
                                        <span v-if="getRoleBadges(item).length === 0" class="text-gray-400 text-sm">-</span>
                                    </div>
                                    <!-- Show extra info for other tabs -->
                                    <div v-else>
                                        <template v-if="getExtraInfo(item)">
                                            <div class="text-xs text-gray-500">{{ getExtraInfo(item).label }}</div>
                                            <div :class="['text-sm', getExtraInfo(item).class]">{{ getExtraInfo(item).value }}</div>
                                        </template>
                                        <span v-else class="text-gray-400 text-sm">-</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <Link
                                        :href="`${getBaseUrl()}/${item.id}`"
                                        class="text-tactical-accent hover:text-tactical-accent-dark transition-colors"
                                    >
                                        Detail
                                    </Link>
                                </td>
                            </tr>
                            
                            <!-- Empty State -->
                            <tr v-if="orang.data.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada data</h3>
                                    <p class="mt-1 text-sm text-gray-500">
                                        {{ search ? 'Tidak ditemukan orang dengan kata kunci tersebut.' : 'Belum ada data orang yang tercatat.' }}
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="orang.data.length > 0" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <Link
                            v-if="orang.prev_page_url"
                            :href="orang.prev_page_url"
                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                        >
                            Sebelumnya
                        </Link>
                        <Link
                            v-if="orang.next_page_url"
                            :href="orang.next_page_url"
                            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                        >
                            Selanjutnya
                        </Link>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Menampilkan
                                <span class="font-medium">{{ orang.from }}</span>
                                sampai
                                <span class="font-medium">{{ orang.to }}</span>
                                dari
                                <span class="font-medium">{{ orang.total }}</span>
                                data
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <Link
                                    v-for="link in orang.links"
                                    :key="link.label"
                                    :href="link.url || '#'"
                                    :class="[
                                        'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                                        link.active
                                            ? 'z-10 bg-tactical-accent border-tactical-accent text-white'
                                            : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                        !link.url ? 'cursor-not-allowed opacity-50' : ''
                                    ]"
                                    v-html="link.label"
                                    preserve-scroll
                                />
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </component>
</template>
