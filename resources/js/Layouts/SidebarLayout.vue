<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

defineProps({
    title: {
        type: String,
        default: ''
    }
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const sidebarOpen = ref(false);

// Navigation items
const navigation = computed(() => {
    const items = [
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

    // Add "Manajemen Kasus" menu for admin_subdit role
    if (user.value?.role === 'admin_subdit') {
        items.push({
            name: 'Manajemen Kasus',
            href: '/min-ops',
            icon: 'briefcase',
            routeName: 'min-ops.index'
        });
    }

    return items;
});

// Check if current route matches using URL path
const isActiveRoute = (routeName) => {
    // Get current path from window.location
    const currentPath = window.location.pathname;
    
    // Map route names to paths
    const routePathMap = {
        'dashboard': '/dashboard',
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
                <Link href="/dashboard" class="flex items-center gap-3">
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
            <div v-if="user" class="absolute bottom-0 left-0 right-0 p-4 border-t border-navy-700">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-tactical-accent flex items-center justify-center text-white font-bold">
                        {{ user.name?.charAt(0).toUpperCase() || 'U' }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-white truncate">{{ user.name }}</div>
                        <div class="text-xs text-gray-500 truncate">{{ user.email }}</div>
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
</template>
