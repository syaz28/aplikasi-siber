<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ToastContainer from '@/Components/ToastContainer.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    pangkatOptions: Array,
    subditOptions: Array,
    unitOptions: Array,
});

const toast = useToast();
const page = usePage();

onMounted(() => {
    if (page.props.flash?.error) {
        toast.error(page.props.flash.error);
    }
});

const form = useForm({
    nrp: '',
    nama_lengkap: '',
    pangkat: '',
    subdit_id: '',
    unit_id: '',
});

const submit = () => {
    form.post('/admin/personels', {
        onSuccess: () => {
            toast.success('Data personel berhasil ditambahkan');
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0];
            toast.error(firstError);
        }
    });
};
</script>

<template>
    <Head title="Tambah Personel" />
    <ToastContainer />

    <AdminLayout title="Tambah Personel">
        <div class="max-w-2xl mx-auto">
            <!-- Back Button -->
            <div class="mb-6">
                <Link
                    href="/admin/personels"
                    class="inline-flex items-center gap-2 text-gray-600 hover:text-navy transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Kembali ke Daftar Personel
                </Link>
            </div>

            <!-- Form Card -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <!-- Header -->
                <div class="bg-navy px-6 py-4 border-l-4 border-tactical-accent">
                    <h2 class="text-lg font-bold text-white flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                        Tambah Data Personel Baru
                    </h2>
                    <p class="text-slate-300 text-sm mt-1">
                        Isi form berikut untuk menambahkan data anggota personel baru
                    </p>
                </div>

                <!-- Form -->
                <form @submit.prevent="submit" class="p-6 space-y-6">
                    <!-- NRP -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            NRP <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.nrp"
                            type="text"
                            placeholder="Masukkan NRP"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-tactical-accent focus:border-tactical-accent font-mono"
                            :class="{ 'border-red-500': form.errors.nrp }"
                        />
                        <p v-if="form.errors.nrp" class="mt-1 text-sm text-red-500">
                            {{ form.errors.nrp }}
                        </p>
                    </div>

                    <!-- Nama Lengkap -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Nama Lengkap <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.nama_lengkap"
                            type="text"
                            placeholder="Masukkan nama lengkap"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-tactical-accent focus:border-tactical-accent"
                            :class="{ 'border-red-500': form.errors.nama_lengkap }"
                        />
                        <p v-if="form.errors.nama_lengkap" class="mt-1 text-sm text-red-500">
                            {{ form.errors.nama_lengkap }}
                        </p>
                    </div>

                    <!-- Pangkat -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Pangkat
                        </label>
                        <select
                            v-model="form.pangkat"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-tactical-accent focus:border-tactical-accent"
                        >
                            <option value="">Pilih Pangkat</option>
                            <option v-for="p in pangkatOptions" :key="p" :value="p">{{ p }}</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Pangkat anggota kepolisian</p>
                    </div>

                    <!-- Subdit -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Subdit
                        </label>
                        <select
                            v-model="form.subdit_id"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-tactical-accent focus:border-tactical-accent"
                        >
                            <option value="">Pilih Subdit (Opsional)</option>
                            <option v-for="s in subditOptions" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </div>

                    <!-- Unit -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Unit
                        </label>
                        <select
                            v-model="form.unit_id"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-tactical-accent focus:border-tactical-accent"
                        >
                            <option value="">Pilih Unit (Opsional)</option>
                            <option v-for="u in unitOptions" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-4 pt-4 border-t border-gray-200">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex-1 px-6 py-3 bg-tactical-accent hover:bg-tactical-accent-dark text-white rounded-lg font-semibold transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                        >
                            <svg v-if="form.processing" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Data' }}
                        </button>
                        <Link
                            href="/admin/personels"
                            class="px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-semibold transition-colors"
                        >
                            Batal
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
