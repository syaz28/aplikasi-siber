<script setup>
import { ref, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';

defineProps({
    title: {
        type: String,
        default: ''
    }
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const activePawas = computed(() => ({
    id: page.props.auth?.active_pawas_id,
    name: page.props.auth?.active_pawas_name,
    pangkat: page.props.auth?.active_pawas_pangkat,
    nrp: page.props.auth?.active_pawas_nrp,
}));
const sidebarOpen = ref(false);
const showSwitchModal = ref(false);
const isSwitching = ref(false);

const openSwitchModal = () => {
    showSwitchModal.value = true;
};

const closeSwitchModal = () => {
    showSwitchModal.value = false;
};

const confirmSwitchPawas = () => {
    isSwitching.value = true;
    router.post('/pawas/clear', {}, {
        onFinish: () => {
            isSwitching.value = false;
            showSwitchModal.value = false;
        }
    });
};

// Navigation items
const navigation = computed(() => {
    // admin_subdit sees Dashboard and Manajemen Kasus
    if (user.value?.role === 'admin_subdit') {
        return [
            {
                name: 'Dashboard',
                href: '/subdit/dashboard',
                icon: 'dashboard',
                routeName: 'subdit.dashboard'
            },
            {
                name: 'Manajemen Kasus',
                href: '/min-ops',
                icon: 'briefcase',
                routeName: 'min-ops.index'
            }
        ];
    }

    // petugas sees Dashboard, Entry, Arsip
    return [
        {
            name: 'Dashboard',
            href: '/dashboard',
            icon: 'dashboard',
            routeName: 'dashboard'
        },
        {
            name: 'Entry Laporan',
            href: '/laporan/create',
            icon: 'document-add',
            routeName: 'laporan.create'
        },
        {
            name: 'Arsip Laporan',
            href: '/laporan',
            icon: 'archive',
            routeName: 'laporan.index'
        },
    ];
});

// Check if current route matches using URL path
const isActiveRoute = (routeName) => {
    // Get current path from window.location
    const currentPath = window.location.pathname;
    
    // Map route names to paths
    const routePathMap = {
        'dashboard': '/dashboard',
        'subdit.dashboard': '/subdit/dashboard',
        'laporan.create': '/laporan/create',
        'laporan.index': '/laporan',
        'min-ops.index': '/min-ops',
    };
    
    const targetPath = routePathMap[routeName];
    if (!targetPath) return false;
    
    // Special handling for laporan.create - exact match only
    if (routeName === 'laporan.create') {
        return currentPath === '/laporan/create';
    }
    
    // Special handling for laporan.index - match /laporan but NOT /laporan/create
    if (routeName === 'laporan.index') {
        return currentPath === '/laporan' || (currentPath.startsWith('/laporan/') && !currentPath.startsWith('/laporan/create'));
    }
    
    // For min-ops - starts with /min-ops
    if (routeName === 'min-ops.index') {
        return currentPath.startsWith('/min-ops');
    }
    
    // For subdit dashboard - starts with /subdit
    if (routeName === 'subdit.dashboard') {
        return currentPath.startsWith('/subdit/dashboard');
    }
    
    // For dashboard - exact match
    return currentPath === targetPath;
};

const currentDate = computed(() => {
    return new Date().toLocaleDateString('id-ID', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
});
</script>

<template>
    <div class="min-h-screen bg-tactical-bg">
        <!-- Mobile sidebar overlay -->
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-40 bg-black/50 lg:hidden"
            @click="sidebarOpen = false"
        />

        <!-- Sidebar -->
        <aside
            class="fixed top-0 left-0 z-50 h-full w-64 bg-navy transform transition-transform duration-300 lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <!-- Logo Section -->
            <div class="flex items-center justify-center h-20 border-b border-navy-700">
                <Link :href="user?.role === 'admin_subdit' ? '/subdit/dashboard' : '/dashboard'" class="flex items-center gap-3">
                    <img
                        src="/images/siber_logo.png"
                        alt="Ditresiber Logo"
                        class="h-12 w-auto"
                        @error="$event.target.style.display = 'none'"
                    />
                    <div class="text-white">
                        <div class="font-bold text-sm">DITRESIBER</div>
                        <div class="text-xs text-gray-400">POLDA JATENG</div>
                    </div>
                </Link>
            </div>

            <!-- Navigation -->
            <nav class="mt-6 px-4">
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4 px-3">
                    Menu Utama
                </div>
                <ul class="space-y-2">
                    <li v-for="item in navigation" :key="item.name">
                        <Link
                            :href="item.href"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200"
                            :class="isActiveRoute(item.routeName)
                                ? 'bg-tactical-accent text-white border-l-4 border-white'
                                : 'text-gray-400 hover:bg-navy-800 hover:text-white'"
                        >
                            <!-- Document Add Icon -->
                            <svg v-if="item.icon === 'document-add'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                            <!-- Archive Icon -->
                            <svg v-else-if="item.icon === 'archive'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"
                                />
                            </svg>
                            <!-- Dashboard Icon -->
                            <svg v-else-if="item.icon === 'dashboard'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"
                                />
                            </svg>
                            <!-- Users Icon -->
                            <svg v-else-if="item.icon === 'users'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                                />
                            </svg>
                            <!-- Briefcase Icon (Manajemen Kasus) -->
                            <svg v-else-if="item.icon === 'briefcase'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                />
                            </svg>
                            <span class="font-medium">{{ item.name }}</span>
                        </Link>
                    </li>
                </ul>
            </nav>

            <!-- User Section at Bottom -->
            <div v-if="user" class="absolute bottom-0 left-0 right-0 border-t border-navy-700">
                <!-- Active Pawas Info (for petugas role) -->
                <div v-if="user.role === 'petugas' && activePawas.id" class="p-4 bg-navy-800 border-b border-navy-700">
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                        Identitas Aktif
                    </div>
                    <div class="bg-tactical-accent/20 border border-tactical-accent/30 rounded-lg p-3">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full bg-tactical-accent flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                                {{ activePawas.name?.charAt(0).toUpperCase() || 'P' }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-semibold text-white truncate">
                                    {{ activePawas.name }}
                                </div>
                                <div class="text-xs text-tactical-accent mt-0.5">
                                    {{ activePawas.pangkat }} • {{ activePawas.nrp }}
                                </div>
                            </div>
                        </div>
                        <button
                            @click="openSwitchModal"
                            class="w-full mt-3 px-3 py-1.5 bg-white/10 hover:bg-white/20 text-white text-xs rounded transition-colors flex items-center justify-center gap-2"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                            Ganti Identitas
                        </button>
                    </div>
                </div>

                <!-- User Account Info -->
                <div class="p-4">
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                        Akun Login
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-navy-700 flex items-center justify-center text-white font-bold">
                            {{ user.username?.charAt(0).toUpperCase() || 'U' }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-white truncate">{{ user.username }}</div>
                            <div class="text-xs text-gray-500 capitalize">{{ user.role?.replace('_', ' ') }}</div>
                        </div>
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            class="p-2 text-gray-500 hover:text-white transition-colors"
                            title="Logout"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                                />
                            </svg>
                        </Link>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="lg:pl-64">
            <!-- Top Header -->
            <header class="sticky top-0 z-30 bg-white border-b border-tactical-border shadow-sm">
                <div class="flex items-center justify-between h-16 px-4 lg:px-8">
                    <!-- Mobile menu button -->
                    <button
                        @click="sidebarOpen = true"
                        class="lg:hidden p-2 text-gray-600 hover:text-navy"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <!-- Page Title -->
                    <h1 class="text-lg font-bold text-navy lg:text-xl">
                        {{ title }}
                    </h1>

                    <!-- Right side actions -->
                    <div class="flex items-center gap-4">
                        <!-- Current date -->
                        <div class="hidden md:block text-sm text-gray-500">
                            {{ currentDate }}
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-4 lg:p-8">
                <slot />
            </main>
        </div>
    </div>

    <!-- Switch Pawas Confirmation Modal -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showSwitchModal" class="fixed inset-0 z-[100] overflow-y-auto">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="closeSwitchModal"></div>

                <!-- Modal -->
                <div class="flex min-h-full items-center justify-center p-4">
                    <Transition
                        enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <div v-if="showSwitchModal" class="relative w-full max-w-md transform overflow-hidden rounded-2xl bg-gradient-to-br from-navy via-slate-800 to-navy border border-white/10 shadow-2xl">
                            <!-- Header with Icon -->
                            <div class="relative px-6 pt-8 pb-4 text-center">
                                <!-- Icon Container -->
                                <div class="mx-auto w-16 h-16 rounded-full bg-tactical-accent/20 border-2 border-tactical-accent flex items-center justify-center mb-4">
                                    <svg class="w-8 h-8 text-tactical-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                    </svg>
                                </div>
                                
                                <h3 class="text-xl font-bold text-white mb-2">
                                    Ganti Identitas Pawas?
                                </h3>
                                <p class="text-slate-300 text-sm">
                                    Anda akan dialihkan ke halaman pemilihan identitas petugas piket.
                                </p>
                            </div>

                            <!-- Current Identity Display -->
                            <div class="px-6 pb-4" v-if="activePawas.name">
                                <div class="bg-white/5 border border-white/10 rounded-xl p-4">
                                    <div class="text-xs text-slate-400 uppercase tracking-wider mb-2">Identitas Saat Ini</div>
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-tactical-accent flex items-center justify-center text-white font-bold text-lg flex-shrink-0">
                                            {{ activePawas.name?.charAt(0).toUpperCase() }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-semibold text-white truncate">
                                                {{ activePawas.name }}
                                            </div>
                                            <div class="text-sm text-tactical-accent">
                                                {{ activePawas.pangkat }} • {{ activePawas.nrp }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="px-6 pb-6 flex gap-3">
                                <button
                                    @click="closeSwitchModal"
                                    :disabled="isSwitching"
                                    class="flex-1 px-4 py-3 bg-white/10 hover:bg-white/20 text-white rounded-xl font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Batal
                                </button>
                                <button
                                    @click="confirmSwitchPawas"
                                    :disabled="isSwitching"
                                    class="flex-1 px-4 py-3 bg-tactical-accent hover:bg-tactical-accent-dark text-white rounded-xl font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                                >
                                    <svg v-if="isSwitching" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>{{ isSwitching ? 'Memproses...' : 'Ya, Ganti' }}</span>
                                </button>
                            </div>

                            <!-- Close Button (X) -->
                            <button
                                @click="closeSwitchModal"
                                :disabled="isSwitching"
                                class="absolute top-4 right-4 p-1 text-slate-400 hover:text-white transition-colors disabled:opacity-50"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </Transition>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
