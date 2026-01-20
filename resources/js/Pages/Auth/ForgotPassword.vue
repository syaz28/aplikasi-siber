<script setup>
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: String,
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post('/forgot-password');
};
</script>

<template>
    <Head title="Lupa Password - SIBER JATENG" />

    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-navy via-slate-800 to-navy">
        <div class="absolute inset-0 bg-[url('/images/grid-pattern.svg')] opacity-5"></div>

        <div class="relative w-full max-w-md mx-4">
            <div class="text-center mb-8">
                <img src="/images/siber_logo.png" alt="DITRESIBER Logo" class="h-24 mx-auto mb-4" />
                <h1 class="text-2xl font-bold text-white">DITRESSIBER POLDA JATENG</h1>
            </div>

            <div class="bg-white/10 backdrop-blur-xl rounded-2xl border border-white/20 shadow-2xl overflow-hidden">
                <div class="bg-navy px-6 py-4 border-l-4 border-tactical-warning">
                    <h2 class="text-lg font-bold text-white flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                        </svg>
                        Lupa Password
                    </h2>
                </div>

                <div class="p-6">
                    <p class="text-slate-300 text-sm mb-4">
                        Masukkan email Anda dan kami akan mengirimkan link untuk reset password.
                    </p>

                    <div v-if="status" class="mb-4 p-3 bg-tactical-success/20 border border-tactical-success rounded-lg text-sm font-medium text-tactical-success">
                        {{ status }}
                    </div>

                    <form @submit.prevent="submit" class="space-y-5">
                        <div>
                            <label for="email" class="block text-sm font-medium text-white mb-1">Email</label>
                            <input
                                id="email"
                                type="email"
                                v-model="form.email"
                                autofocus
                                required
                                class="block w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-slate-400 focus:border-tactical-accent focus:ring-tactical-accent transition-all"
                                placeholder="email@polri.go.id"
                            />
                            <InputError :message="form.errors.email" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-between">
                            <Link href="/login" class="text-sm text-tactical-accent hover:text-blue-400">
                                ← Kembali ke Login
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="py-2 px-4 bg-tactical-warning text-white font-bold rounded-lg hover:bg-yellow-600 transition-all disabled:opacity-50"
                            >
                                {{ form.processing ? 'Mengirim...' : 'Kirim Link Reset' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
