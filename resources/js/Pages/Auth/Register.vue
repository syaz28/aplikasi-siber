<script setup>
import InputError from '@/Components/InputError.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    pangkatOptions: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    nama: '',
    nrp: '',
    pangkat: null,
    jabatan: '',
    telepon: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Register - SIBER JATENG" />

    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-navy via-slate-800 to-navy py-8">
        <!-- Background Pattern -->
        <div class="absolute inset-0 bg-[url('/images/grid-pattern.svg')] opacity-5"></div>

        <!-- Register Card -->
        <div class="relative w-full max-w-lg mx-4">
            <!-- Logo Section -->
            <div class="text-center mb-6">
                <img
                    src="/images/siber_logo.png"
                    alt="DITRESIBER Logo"
                    class="h-20 mx-auto mb-3"
                />
                <h1 class="text-2xl font-bold text-white">
                    DITRESSIBER POLDA JATENG
                </h1>
                <p class="text-slate-400 text-sm mt-1">
                    Registrasi Akun Anggota
                </p>
            </div>

            <!-- Card -->
            <div class="bg-white/10 backdrop-blur-xl rounded-2xl border border-white/20 shadow-2xl overflow-hidden">
                <!-- Header -->
                <div class="bg-navy px-6 py-4 border-l-4 border-tactical-accent">
                    <h2 class="text-lg font-bold text-white flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                        Daftar Akun Baru
                    </h2>
                    <p class="text-slate-400 text-xs mt-1">
                        Khusus untuk Anggota Ditressiber Polda Jateng
                    </p>
                </div>

                <div class="p-6">
                    <form @submit.prevent="submit" class="space-y-4">
                        <!-- Nama Lengkap Field -->
                        <div>
                            <label for="nama" class="block text-sm font-medium text-white mb-1">
                                Nama Lengkap <span class="text-red-400">*</span>
                            </label>
                            <input
                                id="nama"
                                type="text"
                                v-model="form.nama"
                                autocomplete="name"
                                autofocus
                                required
                                class="block w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-slate-400 focus:border-tactical-accent focus:ring-tactical-accent focus:bg-white/20 transition-all"
                                placeholder="Nama lengkap sesuai dokumen"
                            />
                            <InputError :message="form.errors.nama" class="mt-2" />
                        </div>

                        <!-- NRP Field -->
                        <div>
                            <label for="nrp" class="block text-sm font-medium text-white mb-1">
                                NRP (Nomor Registrasi Pokok) <span class="text-red-400">*</span>
                            </label>
                            <input
                                id="nrp"
                                type="text"
                                inputmode="numeric"
                                pattern="[0-9]*"
                                v-model="form.nrp"
                                required
                                class="block w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-slate-400 focus:border-tactical-accent focus:ring-tactical-accent focus:bg-white/20 transition-all"
                                placeholder="12345678"
                            />
                            <InputError :message="form.errors.nrp" class="mt-2" />
                        </div>

                        <!-- Pangkat & Jabatan Row -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Pangkat Field -->
                            <div>
                                <label class="block text-sm font-medium text-white mb-1">
                                    Pangkat <span class="text-red-400">*</span>
                                </label>
                                <SearchableSelect
                                    v-model="form.pangkat"
                                    :options="props.pangkatOptions"
                                    value-key="value"
                                    label-key="label"
                                    placeholder="Pilih Pangkat"
                                    class="auth-select"
                                />
                                <InputError :message="form.errors.pangkat" class="mt-2" />
                            </div>

                            <!-- Jabatan Field -->
                            <div>
                                <label class="block text-sm font-medium text-white mb-1">
                                    Jabatan
                                </label>
                                <input
                                    type="text"
                                    v-model="form.jabatan"
                                    class="block w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-slate-400 focus:border-tactical-accent focus:ring-tactical-accent focus:bg-white/20 transition-all"
                                    placeholder="Contoh: Penyidik"
                                />
                                <InputError :message="form.errors.jabatan" class="mt-2" />
                            </div>
                        </div>

                        <!-- Telepon Field -->
                        <div>
                            <label class="block text-sm font-medium text-white mb-1">
                                No. Telepon
                            </label>
                            <input
                                type="text"
                                inputmode="numeric"
                                v-model="form.telepon"
                                class="block w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-slate-400 focus:border-tactical-accent focus:ring-tactical-accent focus:bg-white/20 transition-all"
                                placeholder="08xxxxxxxxxx"
                            />
                            <InputError :message="form.errors.telepon" class="mt-2" />
                        </div>

                        <!-- Password Field -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-white mb-1">
                                Password <span class="text-red-400">*</span>
                            </label>
                            <input
                                id="password"
                                type="password"
                                v-model="form.password"
                                autocomplete="new-password"
                                required
                                class="block w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-slate-400 focus:border-tactical-accent focus:ring-tactical-accent focus:bg-white/20 transition-all"
                                placeholder="Minimal 8 karakter"
                            />
                            <InputError :message="form.errors.password" class="mt-2" />
                        </div>

                        <!-- Confirm Password Field -->
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-white mb-1">
                                Konfirmasi Password <span class="text-red-400">*</span>
                            </label>
                            <input
                                id="password_confirmation"
                                type="password"
                                v-model="form.password_confirmation"
                                autocomplete="new-password"
                                required
                                class="block w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-slate-400 focus:border-tactical-accent focus:ring-tactical-accent focus:bg-white/20 transition-all"
                                placeholder="Ulangi password"
                            />
                            <InputError :message="form.errors.password_confirmation" class="mt-2" />
                        </div>

                        <!-- Submit Button -->
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full py-3 px-4 bg-tactical-accent text-white font-bold rounded-lg hover:bg-blue-600 focus:ring-4 focus:ring-tactical-accent/30 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 mt-6"
                        >
                            <template v-if="form.processing">
                                <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Mendaftarkan...
                            </template>
                            <template v-else>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Daftar Akun
                            </template>
                        </button>

                        <!-- Login Link -->
                        <div class="text-center pt-2">
                            <span class="text-slate-400 text-sm">
                                Sudah punya akun?
                            </span>
                            <Link
                                href="/login"
                                class="text-sm text-tactical-accent hover:text-blue-400 transition-colors font-medium ml-1"
                            >
                                Masuk di sini
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

<style scoped>
/* Override SearchableSelect styles for auth pages */
:deep(.auth-select) {
    .select-trigger {
        @apply bg-white/10 border-white/20 text-white;
    }
    .select-trigger:focus {
        @apply border-tactical-accent ring-tactical-accent bg-white/20;
    }
    .select-dropdown {
        @apply bg-slate-800 border-slate-600;
    }
    .select-option {
        @apply text-white hover:bg-slate-700;
    }
    .select-option.selected {
        @apply bg-tactical-accent;
    }
}
</style>
