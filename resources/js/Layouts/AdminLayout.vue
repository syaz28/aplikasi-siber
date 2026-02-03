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
const sidebarOpen = ref(false);

// Admin navigation items
const navigation = [
    {
        name: 'Dashboard',
        href: '/admin',
        icon: 'dashboard',
    },
    {
        name: 'Kelola User',
        href: '/admin/users',
        icon: 'user-cog',
    },
    {
        name: 'Data Personel',
        href: '/admin/personels',
        icon: 'users',
    },
    {
        name: 'Kategori Kejahatan',
        href: '/admin/kategori',
        icon: 'tag',
    },
    {
        name: 'Laporan Masuk',
        href: '/admin/laporan',
        icon: 'inbox',
    },
];

// Check if current path matches
const isActivePath = (path) => {
    const currentPath = window.location.pathname;
    if (path === '/admin') {
        return currentPath === '/admin';
    }
    return currentPath.startsWith(path);
};

const currentDate = computed(() => {
    return new Date().toLocaleDateString('id-ID', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
});

const logout = () => {
    router.post('/logout');
};
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
                <Link href="/admin" class="flex items-center gap-3">
                    <img
                        src="/images/siber_logo.png"
                        alt="Ditresiber Logo"
                        class="h-12 w-auto"
                        @error="$event.target.style.display = 'none'"
                    />
                    <div class="text-white">
                        <div class="font-bold text-sm">ADMIN PANEL</div>
                        <div class="text-xs text-gray-400">DITRESIBER JATENG</div>
                    </div>
                </Link>
            </div>

            <!-- Navigation -->
            <nav class="mt-6 px-4">
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4 px-3">
                    Menu Admin
                </div>
                <div class="space-y-2">
                    <Link
                        v-for="item in navigation"
                        :key="item.name"
                        :href="item.href"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200',
                            isActivePath(item.href)
                                ? 'bg-tactical-accent text-white border-l-4 border-white'
                                : 'text-gray-400 hover:bg-navy-800 hover:text-white'
                        ]"
                    >
                        <!-- Dashboard Icon -->
                        <svg v-if="item.icon === 'dashboard'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"
                            />
                        </svg>
                        <!-- User Cog Icon -->
                        <svg v-else-if="item.icon === 'user-cog'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        <!-- Users Icon -->
                        <svg v-else-if="item.icon === 'users'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                            />
                        </svg>
                        <!-- Tag Icon -->
                        <svg v-else-if="item.icon === 'tag'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"
                            />
                        </svg>
                        <!-- Inbox Icon -->
                        <svg v-else-if="item.icon === 'inbox'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
                            />
                        </svg>
                        <span class="font-medium">{{ item.name }}</span>
                    </Link>
                </div>
            </nav>

            <!-- User Profile at Bottom -->
            <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-navy-700">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-tactical-accent rounded-full flex items-center justify-center text-white font-bold">
                        {{ user?.username?.charAt(0)?.toUpperCase() || 'A' }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white truncate">{{ user?.username || 'Admin' }}</p>
                        <p class="text-xs text-gray-400 truncate">Administrator</p>
                    </div>
                </div>
                <button
                    @click="logout"
                    class="w-full flex items-center justify-center gap-2 px-4 py-2 text-sm text-gray-400 hover:text-white hover:bg-navy-800 rounded-lg transition"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Logout
                </button>
            </div>
        </aside>

        <!-- Main Content -->
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

                    <!-- Right side -->
                    <div class="flex items-center gap-4">
                        <span class="text-sm text-gray-500 hidden md:block">{{ currentDate }}</span>
                        <span class="px-3 py-1 text-xs font-semibold bg-tactical-accent text-white rounded-full">
                            Admin
                        </span>
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
