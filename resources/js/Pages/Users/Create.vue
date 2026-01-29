<script setup>
import SidebarLayout from '@/Layouts/SidebarLayout.vue';
import ToastContainer from '@/Components/ToastContainer.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    roleOptions: Array,
    subditOptions: Array,
    unitOptions: Array,
});

const toast = useToast();

const form = useForm({
    name: '',
    nrp: '',
    email: '',
    role: '',
    subdit: '',
    unit: '',
    is_active: true,
});

// Determine which fields to show based on role
const showSubdit = computed(() => {
    return ['admin_subdit', 'petugas'].includes(form.role);
});

const showUnit = computed(() => {
    return form.role === 'petugas';
});

// Clear subdit/unit when role changes
watch(() => form.role, (newRole) => {
    if (newRole === 'admin' || newRole === 'pimpinan') {
        form.subdit = '';
        form.unit = '';
    } else if (newRole === 'admin_subdit') {
        form.unit = '';
    }
});

const submit = () => {
    form.post('/users', {
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

    <SidebarLayout title="Tambah User">
        <ToastContainer />
        
        <div class="max-w-2xl mx-auto">
            <!-- Header -->
            <div class="mb-6">
                <Link
                    href="/users"
                    class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 transition mb-4"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Kembali
                </Link>
                <h1 class="text-2xl font-bold text-navy">Tambah User Baru</h1>
                <p class="text-gray-600">Isi form berikut untuk menambahkan user baru</p>
            </div>

            <!-- Form -->
            <div class="bg-white rounded-xl shadow-tactical border border-tactical-border p-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Nama Lengkap <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-tactical-accent focus:border-tactical-accent"
                            :class="{ 'border-red-500': form.errors.name }"
                            placeholder="Masukkan nama lengkap"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-sm text-red-500">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <!-- NRP -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            NRP <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.nrp"
                            type="text"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-tactical-accent focus:border-tactical-accent"
                            :class="{ 'border-red-500': form.errors.nrp }"
                            placeholder="Masukkan NRP"
                        />
                        <p class="mt-1 text-xs text-gray-500">
                            NRP akan digunakan sebagai password default
                        </p>
                        <p v-if="form.errors.nrp" class="mt-1 text-sm text-red-500">
                            {{ form.errors.nrp }}
                        </p>
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Email
                        </label>
                        <input
                            v-model="form.email"
                            type="email"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-tactical-accent focus:border-tactical-accent"
                            :class="{ 'border-red-500': form.errors.email }"
                            placeholder="Masukkan email (opsional)"
                        />
                        <p v-if="form.errors.email" class="mt-1 text-sm text-red-500">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <!-- Role -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Role <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="form.role"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-tactical-accent focus:border-tactical-accent"
                            :class="{ 'border-red-500': form.errors.role }"
                        >
                            <option value="">Pilih Role</option>
                            <option v-for="opt in roleOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">
                            <template v-if="form.role === 'admin'">
                                Admin memiliki akses penuh ke semua fitur sistem
                            </template>
                            <template v-else-if="form.role === 'petugas'">
                                Petugas dapat mengelola laporan di unit yang ditugaskan
                            </template>
                            <template v-else-if="form.role === 'admin_subdit'">
                                Admin Subdit mengelola laporan di subdit yang ditugaskan
                            </template>
                            <template v-else-if="form.role === 'pimpinan'">
                                Pimpinan dapat melihat dashboard dan laporan
                            </template>
                        </p>
                        <p v-if="form.errors.role" class="mt-1 text-sm text-red-500">
                            {{ form.errors.role }}
                        </p>
                    </div>

                    <!-- Subdit (conditional) -->
                    <div v-if="showSubdit">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Subdit <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="form.subdit"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-tactical-accent focus:border-tactical-accent"
                            :class="{ 'border-red-500': form.errors.subdit }"
                        >
                            <option value="">Pilih Subdit</option>
                            <option v-for="opt in subditOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                        <p v-if="form.errors.subdit" class="mt-1 text-sm text-red-500">
                            {{ form.errors.subdit }}
                        </p>
                    </div>

                    <!-- Unit (conditional) -->
                    <div v-if="showUnit">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Unit <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="form.unit"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-tactical-accent focus:border-tactical-accent"
                            :class="{ 'border-red-500': form.errors.unit }"
                        >
                            <option value="">Pilih Unit</option>
                            <option v-for="opt in unitOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                        <p v-if="form.errors.unit" class="mt-1 text-sm text-red-500">
                            {{ form.errors.unit }}
                        </p>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                class="w-5 h-5 text-tactical-accent border-gray-300 rounded focus:ring-tactical-accent"
                            />
                            <span class="text-sm font-medium text-gray-700">
                                User Aktif
                            </span>
                        </label>
                        <p class="mt-1 text-xs text-gray-500 ml-8">
                            User tidak aktif tidak dapat login ke sistem
                        </p>
                    </div>

                    <!-- Info Box -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex gap-3">
                            <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div class="text-sm text-blue-700">
                                <p class="font-medium">Informasi</p>
                                <p>Password default akan diset ke NRP yang diinputkan. User dapat mengubah password setelah login pertama kali.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                        <Link
                            href="/users"
                            class="px-6 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition"
                        >
                            Batal
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-2 bg-tactical-accent text-white rounded-lg hover:bg-tactical-accent/90 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                        >
                            <svg v-if="form.processing" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ form.processing ? 'Menyimpan...' : 'Simpan' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </SidebarLayout>
</template>
