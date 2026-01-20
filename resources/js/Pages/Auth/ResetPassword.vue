<script setup>
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: String,
    token: String,
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/reset-password', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Reset Password - SIBER JATENG" />

    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-navy via-slate-800 to-navy">
        <div class="absolute inset-0 bg-[url('/images/grid-pattern.svg')] opacity-5"></div>

        <div class="relative w-full max-w-md mx-4">
            <div class="text-center mb-8">
                <img src="/images/siber_logo.png" alt="DITRESIBER Logo" class="h-24 mx-auto mb-4" />
                <h1 class="text-2xl font-bold text-white">DITRESSIBER POLDA JATENG</h1>
            </div>

            <div class="bg-white/10 backdrop-blur-xl rounded-2xl border border-white/20 shadow-2xl overflow-hidden">
                <div class="bg-navy px-6 py-4 border-l-4 border-tactical-accent">
                    <h2 class="text-lg font-bold text-white">Reset Password</h2>
                </div>

                <div class="p-6">
                    <form @submit.prevent="submit" class="space-y-5">
                        <div>
                            <label for="email" class="block text-sm font-medium text-white mb-1">Email</label>
                            <input
                                id="email"
                                type="email"
                                v-model="form.email"
                                required
                                class="block w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-slate-400 focus:border-tactical-accent focus:ring-tactical-accent transition-all"
                            />
                            <InputError :message="form.errors.email" class="mt-2" />
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-white mb-1">Password Baru</label>
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

                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-white mb-1">Konfirmasi Password</label>
                            <input
                                id="password_confirmation"
                                type="password"
                                v-model="form.password_confirmation"
                                required
                                class="block w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-slate-400 focus:border-tactical-accent focus:ring-tactical-accent transition-all"
                            />
                            <InputError :message="form.errors.password_confirmation" class="mt-2" />
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full py-3 px-4 bg-tactical-accent text-white font-bold rounded-lg hover:bg-blue-600 transition-all disabled:opacity-50"
                        >
                            {{ form.processing ? 'Menyimpan...' : 'Reset Password' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
