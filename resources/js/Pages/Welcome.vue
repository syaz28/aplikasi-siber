<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});
</script>

<template>
    <Head title="SIBER JATENG - Sistem Laporan Kejahatan Siber" />

    <div class="min-h-screen bg-gradient-to-br from-navy via-slate-800 to-navy">
        <div class="absolute inset-0 bg-[url('/images/grid-pattern.svg')] opacity-5"></div>

        <div class="relative min-h-screen flex flex-col items-center justify-center p-4">
            <!-- Logo & Title -->
            <div class="text-center mb-8">
                <img
                    src="/images/siber_logo.png"
                    alt="DITRESIBER Logo"
                    class="h-32 mx-auto mb-6"
                />
                <h1 class="text-4xl font-bold text-white mb-2">SIBER JATENG</h1>
                <p class="text-xl text-slate-400">
                    Sistem Informasi Laporan Kejahatan Siber
                </p>
                <p class="text-slate-500 mt-1">
                    Direktorat Reserse Siber Polda Jawa Tengah
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-4">
                <Link
                    v-if="canLogin"
                    href="/login"
                    class="px-8 py-3 bg-tactical-accent text-white font-bold rounded-lg hover:bg-blue-600 transition-all shadow-lg hover:shadow-xl"
                >
                    Masuk
                </Link>
                <Link
                    v-if="canRegister"
                    href="/register"
                    class="px-8 py-3 bg-white/10 backdrop-blur border border-white/20 text-white font-bold rounded-lg hover:bg-white/20 transition-all"
                >
                    Daftar
                </Link>
            </div>

            <!-- Footer -->
            <div class="absolute bottom-4 text-center text-slate-500 text-sm">
                © 2026 Direktorat Reserse Siber Polda Jawa Tengah
            </div>
        </div>
    </div>
</template>
