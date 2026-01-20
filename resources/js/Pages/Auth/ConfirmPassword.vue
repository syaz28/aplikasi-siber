<script setup>
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post('/confirm-password', {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Konfirmasi Password - SIBER JATENG" />

    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-navy via-slate-800 to-navy">
        <div class="absolute inset-0 bg-[url('/images/grid-pattern.svg')] opacity-5"></div>

        <div class="relative w-full max-w-md mx-4">
            <div class="text-center mb-8">
                <img src="/images/siber_logo.png" alt="DITRESIBER Logo" class="h-24 mx-auto mb-4" />
                <h1 class="text-2xl font-bold text-white">DITRESSIBER POLDA JATENG</h1>
            </div>

            <div class="bg-white/10 backdrop-blur-xl rounded-2xl border border-white/20 shadow-2xl overflow-hidden">
                <div class="bg-navy px-6 py-4 border-l-4 border-tactical-warning">
                    <h2 class="text-lg font-bold text-white">Konfirmasi Password</h2>
                </div>

                <div class="p-6">
                    <p class="text-slate-300 text-sm mb-4">
                        Ini adalah area yang aman. Mohon konfirmasi password Anda sebelum melanjutkan.
                    </p>

                    <form @submit.prevent="submit" class="space-y-5">
                        <div>
                            <label for="password" class="block text-sm font-medium text-white mb-1">Password</label>
                            <input
                                id="password"
                                type="password"
                                v-model="form.password"
                                required
                                autofocus
                                class="block w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-slate-400 focus:border-tactical-accent focus:ring-tactical-accent transition-all"
                            />
                            <InputError :message="form.errors.password" class="mt-2" />
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full py-3 px-4 bg-tactical-accent text-white font-bold rounded-lg hover:bg-blue-600 transition-all disabled:opacity-50"
                        >
                            {{ form.processing ? 'Mengkonfirmasi...' : 'Konfirmasi' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
