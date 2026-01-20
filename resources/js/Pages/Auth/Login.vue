<script setup>
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: String,
    canResetPassword: Boolean,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Login - SIBER JATENG" />

    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-navy via-slate-800 to-navy">
        <!-- Background Pattern -->
        <div class="absolute inset-0 bg-[url('/images/grid-pattern.svg')] opacity-5"></div>

        <!-- Login Card -->
        <div class="relative w-full max-w-md mx-4">
            <!-- Logo Section -->
            <div class="text-center mb-8">
                <img
                    src="/images/siber_logo.png"
                    alt="DITRESIBER Logo"
                    class="h-24 mx-auto mb-4"
                />
                <h1 class="text-2xl font-bold text-white">
                    DITRESSIBER POLDA JATENG
                </h1>
                <p class="text-slate-400 text-sm mt-1">
                    Internal Case Entry System
                </p>
            </div>

            <!-- Card -->
            <div class="bg-white/10 backdrop-blur-xl rounded-2xl border border-white/20 shadow-2xl overflow-hidden">
                <!-- Header -->
                <div class="bg-navy px-6 py-4 border-l-4 border-tactical-accent">
                    <h2 class="text-lg font-bold text-white flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        Secure Login
                    </h2>
                </div>

                <div class="p-6">
                    <div v-if="status" class="mb-4 p-3 bg-tactical-success/20 border border-tactical-success rounded-lg text-sm font-medium text-tactical-success">
                        {{ status }}
                    </div>

                    <form @submit.prevent="submit" class="space-y-5">
                        <!-- Email Field -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-white mb-1">
                                Email
                            </label>
                            <input
                                id="email"
                                type="email"
                                v-model="form.email"
                                autocomplete="username"
                                autofocus
                                class="block w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-slate-400 focus:border-tactical-accent focus:ring-tactical-accent focus:bg-white/20 transition-all"
                                placeholder="email@polri.go.id"
                            />
                            <InputError :message="form.errors.email" class="mt-2" />
                        </div>

                        <!-- Password Field -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-white mb-1">
                                Password
                            </label>
                            <input
                                id="password"
                                type="password"
                                v-model="form.password"
                                autocomplete="current-password"
                                class="block w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-slate-400 focus:border-tactical-accent focus:ring-tactical-accent focus:bg-white/20 transition-all"
                                placeholder="••••••••"
                            />
                            <InputError :message="form.errors.password" class="mt-2" />
                        </div>

                        <!-- Remember & Forgot -->
                        <div class="flex items-center justify-between">
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.remember"
                                    class="rounded border-white/30 bg-white/10 text-tactical-accent focus:ring-tactical-accent"
                                />
                                <span class="ms-2 text-sm text-slate-300">
                                    Ingat saya
                                </span>
                            </label>

                            <Link
                                v-if="canResetPassword"
                                href="/forgot-password"
                                class="text-sm text-tactical-accent hover:text-blue-400 transition-colors"
                            >
                                Lupa password?
                            </Link>
                        </div>

                        <!-- Submit Button -->
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full py-3 px-4 bg-tactical-accent text-white font-bold rounded-lg hover:bg-blue-600 focus:ring-4 focus:ring-tactical-accent/30 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                        >
                            <template v-if="form.processing">
                                <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Memverifikasi...
                            </template>
                            <template v-else>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                </svg>
                                Masuk ke Sistem
                            </template>
                        </button>

                        <!-- Register Link -->
                        <div class="text-center pt-2">
                            <span class="text-slate-400 text-sm">
                                Belum punya akun?
                            </span>
                            <Link
                                href="/register"
                                class="text-sm text-tactical-accent hover:text-blue-400 transition-colors font-medium ml-1"
                            >
                                Daftar di sini
                            </Link>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-6 text-slate-400 text-sm">
                © 2026 Direktorat Reserse Siber Polda Jawa Tengah
            </div>
        </div>
    </div>
</template>
