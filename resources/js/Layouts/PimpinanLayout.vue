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
const isSidebarOpen = ref(false);

// Pimpinan navigation items
const navigation = [
    {
        name: 'Executive Dashboard',
        href: '/pimpinan/dashboard',
        icon: 'chart-bar',
    },
    {
        name: 'Threat Radar',
        href: '/pimpinan/peta-platform',
        icon: 'map-pin',
    },
    {
        name: 'Case Pipeline',
        href: '/pimpinan/case-pipeline',
        icon: 'clipboard-list',
    },
    {
        name: 'Daftar Orang',
        href: '/pimpinan/orang',
        icon: 'users',
    },
    {
        name: 'Daftar Tersangka',
        href: '/pimpinan/tersangka',
        icon: 'exclamation',
    },
];

// Check if current path matches
const isActivePath = (path) => {
    const currentPath = window.location.pathname;
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
    <div class="min-h-screen bg-slate-950">
        <!-- Sidebar backdrop overlay -->
        <Transition
            enter-active-class="transition-opacity duration-300"
            leave-active-class="transition-opacity duration-300"
            enter-from-class="opacity-0"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isSidebarOpen"
                class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm"
                @click="isSidebarOpen = false"
            />
        </Transition>

        <!-- Sidebar -->
        <aside
            class="fixed top-0 left-0 z-50 h-full w-64 bg-gradient-to-b from-purple-900 to-indigo-900 transform transition-transform duration-300 shadow-2xl"
            :class="isSidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <!-- Logo Section -->
            <div class="flex items-center justify-center h-20 border-b border-purple-700/50">
                <Link href="/pimpinan/dashboard" class="flex items-center gap-3">
                    <img
                        src="/images/siber_logo.png"
                        alt="Ditresiber Logo"
                        class="h-12 w-auto"
                        @error="$event.target.style.display = 'none'"
                    />
                    <div class="text-white">
                        <div class="font-bold text-sm">PIMPINAN</div>
                        <div class="text-xs text-purple-300">DITRESIBER JATENG</div>
                    </div>
                </Link>
            </div>

            <!-- Navigation -->
            <nav class="mt-6 px-4">
                <div class="text-xs font-semibold text-purple-400 uppercase tracking-wider mb-4 px-3">
                    Menu Pimpinan
                </div>
                <div class="space-y-2">
                    <Link
                        v-for="item in navigation"
                        :key="item.name"
                        :href="item.href"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200',
                            isActivePath(item.href)
                                ? 'bg-white/20 text-white border-l-4 border-white'
                                : 'text-purple-200 hover:bg-white/10 hover:text-white'
                        ]"
                    >
                        <!-- Chart Bar Icon -->
                        <svg v-if="item.icon === 'chart-bar'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                            />
                        </svg>
                        <!-- Map Pin Icon (Peta & Platform) -->
                        <svg v-else-if="item.icon === 'map-pin'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                            />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                            />
                        </svg>
                        <!-- Dashboard Icon -->
                        <svg v-else-if="item.icon === 'dashboard'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"
                            />
                        </svg>
                        <!-- Clipboard List Icon (Case Pipeline) -->
                        <svg v-else-if="item.icon === 'clipboard-list'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"
                            />
                        </svg>
                        <!-- Users Icon -->
                        <svg v-else-if="item.icon === 'users'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                            />
                        </svg>
                        <!-- Exclamation Icon (Daftar Tersangka) -->
                        <svg v-else-if="item.icon === 'exclamation'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                            />
                        </svg>
                        <span class="font-medium">{{ item.name }}</span>
                    </Link>
                </div>
            </nav>

            <!-- User Profile at Bottom -->
            <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-purple-700/50">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center text-white font-bold">
                        {{ user?.name?.charAt(0)?.toUpperCase() || 'P' }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white truncate">{{ user?.name || 'Pimpinan' }}</p>
                        <p class="text-xs text-purple-300 truncate">{{ user?.nrp || 'Pimpinan' }}</p>
                    </div>
                </div>
                <button
                    @click="logout"
                    class="w-full flex items-center justify-center gap-2 px-4 py-2 text-sm text-purple-200 hover:text-white hover:bg-white/10 rounded-lg transition"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Logout
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="w-full">
            <!-- Top Header — Glassmorphism -->
            <header class="sticky top-0 z-30 bg-slate-900/80 backdrop-blur-md border-b border-slate-800">
                <div class="flex items-center justify-between h-16 px-4 lg:px-8">
                    <!-- Left: Hamburger + Title -->
                    <div class="flex items-center gap-3">
                        <button
                            @click="isSidebarOpen = true"
                            class="p-2 text-slate-300 hover:text-cyan-400 hover:bg-slate-800/60 rounded-lg transition-colors"
                            title="Buka menu navigasi"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                        <div class="leading-tight">
                            <h1 class="text-2xl font-extrabold tracking-tight bg-gradient-to-r from-cyan-400 to-blue-500 bg-clip-text text-transparent">
                                {{ title }}
                            </h1>
                            <p class="text-[10px] text-slate-400 tracking-widest uppercase font-mono mt-0.5">Pusat Komando Analitik Siber Jateng</p>
                        </div>
                    </div>

                    <!-- Right side -->
                    <div class="flex items-center gap-4">
                        <span class="text-sm text-slate-400 hidden md:block">{{ currentDate }}</span>
                        <span class="px-3 py-1 text-xs font-semibold bg-cyan-500/15 text-cyan-300 border border-cyan-500/25 rounded-full">
                            Pimpinan
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
