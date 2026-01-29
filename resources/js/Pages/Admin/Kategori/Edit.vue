<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ToastContainer from '@/Components/ToastContainer.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    kategori: Object,
});

const toast = useToast();

const form = useForm({
    nama: props.kategori.nama,
    deskripsi: props.kategori.deskripsi || '',
    is_active: props.kategori.is_active,
});

const submit = () => {
    form.put(`/admin/kategori/${props.kategori.id}`, {
        onSuccess: () => {
            toast.success('Kategori berhasil diperbarui');
        },
        onError: () => {
            toast.error('Gagal memperbarui kategori');
        }
    });
};
</script>

<template>
    <Head title="Edit Kategori" />

    <AdminLayout title="Edit Kategori">
        <ToastContainer />
        
        <div class="max-w-2xl mx-auto">
            <div class="mb-6">
                <Link
                    href="/admin/kategori"
                    class="inline-flex items-center gap-2 text-slate-600 hover:text-slate-900 transition mb-4"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Kembali
                </Link>
                <h1 class="text-2xl font-bold text-slate-800">Edit Kategori</h1>
                <p class="text-slate-600">Perbarui data kategori kejahatan</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            Nama Kategori <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.nama"
                            type="text"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                            :class="{ 'border-red-500': form.errors.nama }"
                            placeholder="Masukkan nama kategori"
                        />
                        <p v-if="form.errors.nama" class="mt-1 text-sm text-red-500">{{ form.errors.nama }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Deskripsi</label>
                        <textarea
                            v-model="form.deskripsi"
                            rows="4"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                            :class="{ 'border-red-500': form.errors.deskripsi }"
                            placeholder="Masukkan deskripsi kategori (opsional)"
                        ></textarea>
                        <p v-if="form.errors.deskripsi" class="mt-1 text-sm text-red-500">{{ form.errors.deskripsi }}</p>
                    </div>

                    <div>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input v-model="form.is_active" type="checkbox" class="w-5 h-5 text-amber-500 border-slate-300 rounded focus:ring-amber-500" />
                            <span class="text-sm font-medium text-slate-700">Kategori Aktif</span>
                        </label>
                        <p class="mt-1 text-xs text-slate-500 ml-8">Kategori tidak aktif tidak akan muncul pada form pelaporan</p>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                        <Link href="/admin/kategori" class="px-6 py-2 text-slate-700 bg-slate-100 rounded-lg hover:bg-slate-200 transition">
                            Batal
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                        >
                            <svg v-if="form.processing" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
