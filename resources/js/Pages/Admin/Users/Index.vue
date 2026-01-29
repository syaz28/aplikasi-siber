<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ToastContainer from '@/Components/ToastContainer.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch, computed, onMounted } from 'vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    users: Object,
    filters: Object,
    roleOptions: Array,
    subditOptions: Array,
    unitOptions: Array,
});

const toast = useToast();
const page = usePage();

onMounted(() => {
    if (page.props.flash?.success) {
        toast.success(page.props.flash.success);
    }
    if (page.props.flash?.error) {
        toast.error(page.props.flash.error);
    }
});

const search = ref(props.filters?.search || '');
const role = ref(props.filters?.role || '');
const subdit = ref(props.filters?.subdit || '');

const getRoleClass = (r) => {
    const classes = {
        'admin': 'bg-red-100 text-red-800',
        'petugas': 'bg-blue-100 text-blue-800',
        'admin_subdit': 'bg-yellow-100 text-yellow-800',
        'pimpinan': 'bg-purple-100 text-purple-800',
    };
    return classes[r] || 'bg-gray-100 text-gray-800';
};

const getRoleLabel = (r) => {
    const labels = {
        'admin': 'Admin',
        'petugas': 'Petugas',
        'admin_subdit': 'Admin Subdit',
        'pimpinan': 'Pimpinan',
    };
    return labels[r] || r;
};

const getStatusClass = (isActive) => {
    return isActive 
        ? 'bg-green-100 text-green-800' 
        : 'bg-gray-100 text-gray-800';
};

let searchTimeout = null;
const handleSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/admin/users', {
            search: search.value,
            role: role.value,
            subdit: subdit.value,
        }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
};

watch([search, role, subdit], handleSearch);

const deleteConfirm = ref(null);
const resetConfirm = ref(null);

const confirmDelete = (id) => {
    deleteConfirm.value = id;
};

const cancelDelete = () => {
    deleteConfirm.value = null;
};

const deleteUser = () => {
    if (!deleteConfirm.value) return;
    
    router.delete(`/admin/users/${deleteConfirm.value}`, {
        onSuccess: () => {
            toast.success('User berhasil dihapus');
            deleteConfirm.value = null;
        },
        onError: () => {
            toast.error('Gagal menghapus user');
        }
    });
};

const confirmResetPassword = (user) => {
    resetConfirm.value = user;
};

const cancelReset = () => {
    resetConfirm.value = null;
};

const resetPassword = () => {
    if (!resetConfirm.value) return;
    
    router.post(`/admin/users/${resetConfirm.value.id}/reset-password`, {}, {
        onSuccess: () => {
            toast.success('Password berhasil direset ke NRP');
            resetConfirm.value = null;
        },
        onError: () => {
            toast.error('Gagal mereset password');
        }
    });
};

const stats = computed(() => {
    return {
        total: props.users?.total || 0,
        active: (props.users?.data || []).filter(u => u.is_active).length,
    };
});
</script>

<template>
    <Head title="Kelola User" />

    <AdminLayout title="Kelola User">
        <ToastContainer />
        
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">Manajemen User</h1>
                    <p class="text-slate-600">Kelola pengguna sistem</p>
                </div>
                <Link
                    href="/admin/users/create"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition shadow-lg"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    Tambah User
                </Link>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-800">{{ stats.total }}</p>
                            <p class="text-sm text-slate-500">Total User</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-green-100 rounded-lg">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-green-600">{{ stats.active }}</p>
                            <p class="text-sm text-slate-500">User Aktif</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Cari</label>
                        <div class="relative">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Cari nama, NRP, email..."
                                class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                            />
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Role</label>
                        <select
                            v-model="role"
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                        >
                            <option value="">Semua Role</option>
                            <option v-for="opt in roleOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Subdit</label>
                        <select
                            v-model="subdit"
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                        >
                            <option value="">Semua Subdit</option>
                            <option v-for="opt in subditOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-800 text-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">User</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">NRP</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Role</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Subdit / Unit</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200">
                            <tr v-if="users.data.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                                    Tidak ada data user
                                </td>
                            </tr>
                            <tr v-for="user in users.data" :key="user.id" class="hover:bg-slate-50 transition">
                                <td class="px-4 py-3">
                                    <div>
                                        <p class="font-medium text-slate-800">{{ user.name }}</p>
                                        <p class="text-sm text-slate-500">{{ user.email || '-' }}</p>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ user.nrp || '-' }}</td>
                                <td class="px-4 py-3">
                                    <span :class="['px-2 py-1 rounded-full text-xs font-medium', getRoleClass(user.role)]">
                                        {{ getRoleLabel(user.role) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">
                                    <span v-if="user.subdit">Subdit {{ user.subdit }}</span>
                                    <span v-if="user.subdit && user.unit"> / </span>
                                    <span v-if="user.unit">Unit {{ user.unit }}</span>
                                    <span v-if="!user.subdit && !user.unit">-</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="['px-2 py-1 rounded-full text-xs font-medium', getStatusClass(user.is_active)]">
                                        {{ user.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            @click="confirmResetPassword(user)"
                                            class="p-1.5 text-yellow-600 hover:bg-yellow-50 rounded-lg transition"
                                            title="Reset Password"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                            </svg>
                                        </button>
                                        <Link
                                            :href="`/admin/users/${user.id}/edit`"
                                            class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition"
                                            title="Edit"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </Link>
                                        <button
                                            @click="confirmDelete(user.id)"
                                            class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition"
                                            title="Hapus"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="users.last_page > 1" class="px-4 py-3 border-t border-slate-200 bg-slate-50">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-slate-600">
                            Menampilkan {{ users.from }} - {{ users.to }} dari {{ users.total }} data
                        </p>
                        <div class="flex gap-1">
                            <Link
                                v-for="link in users.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                :class="[
                                    'px-3 py-1 text-sm rounded-lg transition',
                                    link.active 
                                        ? 'bg-amber-500 text-white' 
                                        : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-300',
                                    !link.url ? 'opacity-50 cursor-not-allowed' : ''
                                ]"
                                v-html="link.label"
                                :preserveState="true"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Modal -->
        <Teleport to="body">
            <div v-if="deleteConfirm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-2 bg-red-100 rounded-full">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-slate-900">Konfirmasi Hapus</h3>
                    </div>
                    <p class="text-slate-600 mb-6">Apakah Anda yakin ingin menghapus user ini?</p>
                    <div class="flex justify-end gap-3">
                        <button @click="cancelDelete" class="px-4 py-2 text-slate-700 bg-slate-100 rounded-lg hover:bg-slate-200 transition">Batal</button>
                        <button @click="deleteUser" class="px-4 py-2 text-white bg-red-600 rounded-lg hover:bg-red-700 transition">Hapus</button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Reset Password Modal -->
        <Teleport to="body">
            <div v-if="resetConfirm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-2 bg-yellow-100 rounded-full">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-slate-900">Reset Password</h3>
                    </div>
                    <p class="text-slate-600 mb-2">Reset password untuk <strong>{{ resetConfirm.name }}</strong>?</p>
                    <p class="text-sm text-slate-500 mb-6">Password akan direset ke NRP: <code class="bg-slate-100 px-2 py-0.5 rounded">{{ resetConfirm.nrp }}</code></p>
                    <div class="flex justify-end gap-3">
                        <button @click="cancelReset" class="px-4 py-2 text-slate-700 bg-slate-100 rounded-lg hover:bg-slate-200 transition">Batal</button>
                        <button @click="resetPassword" class="px-4 py-2 text-white bg-yellow-600 rounded-lg hover:bg-yellow-700 transition">Reset Password</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
