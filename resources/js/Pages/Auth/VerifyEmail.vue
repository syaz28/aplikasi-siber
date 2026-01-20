<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    status: String,
});

const form = useForm({});

const submit = () => {
    form.post('/email/verification-notification');
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <Head title="Verifikasi Email - SIBER JATENG" />

    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-navy via-slate-800 to-navy">
        <div class="absolute inset-0 bg-[url('/images/grid-pattern.svg')] opacity-5"></div>

        <div class="relative w-full max-w-md mx-4">
            <div class="text-center mb-8">
                <img src="/images/siber_logo.png" alt="DITRESIBER Logo" class="h-24 mx-auto mb-4" />
                <h1 class="text-2xl font-bold text-white">DITRESSIBER POLDA JATENG</h1>
            </div>

            <div class="bg-white/10 backdrop-blur-xl rounded-2xl border border-white/20 shadow-2xl overflow-hidden">
                <div class="bg-navy px-6 py-4 border-l-4 border-tactical-warning">
                    <h2 class="text-lg font-bold text-white">Verifikasi Email</h2>
                </div>

                <div class="p-6">
                    <p class="text-slate-300 text-sm mb-4">
                        Terima kasih telah mendaftar! Sebelum memulai, mohon verifikasi email Anda dengan klik link yang telah kami kirim.
                    </p>

                    <div v-if="verificationLinkSent" class="mb-4 p-3 bg-tactical-success/20 border border-tactical-success rounded-lg text-sm text-tactical-success">
                        Link verifikasi baru telah dikirim ke email Anda.
                    </div>

                    <form @submit.prevent="submit" class="space-y-4">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full py-3 px-4 bg-tactical-accent text-white font-bold rounded-lg hover:bg-blue-600 transition-all disabled:opacity-50"
                        >
                            {{ form.processing ? 'Mengirim...' : 'Kirim Ulang Email Verifikasi' }}
                        </button>

                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            class="w-full py-2 text-center text-sm text-slate-400 hover:text-white transition-colors"
                        >
                            Logout
                        </Link>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
