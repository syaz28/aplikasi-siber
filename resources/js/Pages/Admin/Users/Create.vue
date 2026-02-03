<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ToastContainer from '@/Components/ToastContainer.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    roleOptions: Array,
    subditOptions: Array,
});

const toast = useToast();
const showPassword = ref(false);

const form = useForm({
    username: '',
    password: '',
    role: '',
    subdit: '',
    is_active: true,
});

const showSubdit = computed(() => {
    return form.role === 'admin_subdit';
});

watch(() => form.role, (newRole) => {
    if (newRole !== 'admin_subdit') {
        form.subdit = '';
    }
});

const submit = () => {
    form.post('/admin/users', {
        onSuccess: () => {
            toast.success('User berhasil ditambahkan');
        },
        onError: () => {
            toast.error('Gagal menambahkan user');
        }
    });
};
</script>

<template>
    <Head title="Tambah User" />

    <AdminLayout title="Tambah User">
        <ToastContainer />
        
        <div class="max-w-2xl mx-auto">
            <div class="mb-6">
                <Link
                    href="/admin/users"
                    class="inline-flex items-center gap-2 text-slate-600 hover:text-slate-900 transition mb-4"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Kembali
                </Link>
                <h1 class="text-2xl font-bold text-slate-800">Tambah User Baru</h1>
                <p class="text-slate-600">Isi form berikut untuk menambahkan user baru</p>
            </div> 

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            Username <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.username"
                            type="text"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                            :class="{ 'border-red-500': form.errors.username }"
                            placeholder="Masukkan username"
                        />
                        <p class="mt-1 text-xs text-slate-500">Username untuk login ke sistem</p>
                        <p v-if="form.errors.username" class="mt-1 text-sm text-red-500">{{ form.errors.username }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            Password <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                class="w-full px-4 py-2 pr-12 border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                                :class="{ 'border-red-500': form.errors.password }"
                                placeholder="Masukkan password"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                            >
                                <svg v-if="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Minimal 6 karakter</p>
                        <p v-if="form.errors.password" class="mt-1 text-sm text-red-500">{{ form.errors.password }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            Role <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="form.role"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                            :class="{ 'border-red-500': form.errors.role }"
                        >
                            <option value="">Pilih Role</option>
                            <option v-for="opt in roleOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                        <p class="mt-1 text-xs text-slate-500">
                            <template v-if="form.role === 'admin'">Admin mengelola user dan assign laporan ke subdit</template>
                            <template v-else-if="form.role === 'petugas'">Petugas mengisi form pelaporan (shared account)</template>
                            <template v-else-if="form.role === 'admin_subdit'">Admin Subdit mengelola dan mendisposisi kasus ke unit</template>
                            <template v-else-if="form.role === 'pimpinan'">Pimpinan melihat dashboard eksekutif</template>
                        </p>
                        <p v-if="form.errors.role" class="mt-1 text-sm text-red-500">{{ form.errors.role }}</p>
                    </div>

                    <div v-if="showSubdit">
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            Subdit <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="form.subdit"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                            :class="{ 'border-red-500': form.errors.subdit }"
                        >
                            <option value="">Pilih Subdit</option>
                            <option v-for="opt in subditOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                        <p v-if="form.errors.subdit" class="mt-1 text-sm text-red-500">{{ form.errors.subdit }}</p>
                    </div>

                    <div>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input v-model="form.is_active" type="checkbox" class="w-5 h-5 text-amber-500 border-slate-300 rounded focus:ring-amber-500" />
                            <span class="text-sm font-medium text-slate-700">User Aktif</span>
                        </label>
                        <p class="mt-1 text-xs text-slate-500 ml-8">User tidak aktif tidak dapat login ke sistem</p>
                    </div>

                    <div class="flex items-center gap-4 pt-4 border-t border-slate-200">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex-1 py-2.5 px-4 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition disabled:opacity-50 disabled:cursor-not-allowed font-medium"
                        >
                            <span v-if="form.processing">Menyimpan...</span>
                            <span v-else>Tambah User</span>
                        </button>
                        <Link
                            href="/admin/users"
                            class="flex-1 py-2.5 px-4 bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 transition font-medium text-center"
                        >
                            Batal
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
