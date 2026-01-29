<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ToastContainer from '@/Components/ToastContainer.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch, onMounted } from 'vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    kategori: Object,
    filters: Object,
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
const status = ref(props.filters?.status || '');

let searchTimeout = null;
const handleSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/admin/kategori', {
            search: search.value,
            status: status.value,
        }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
};

watch([search, status], handleSearch);

// Delete modal
const deleteModal = ref(null);

const openDeleteModal = (item) => {
    deleteModal.value = item;
};

const closeDeleteModal = () => {
    deleteModal.value = null;
};

const confirmDelete = () => {
    if (!deleteModal.value) return;
    
    router.delete(`/admin/kategori/${deleteModal.value.id}`, {
        onSuccess: () => {
            toast.success('Kategori berhasil dihapus');
            closeDeleteModal();
        },
        onError: () => {
            toast.error('Gagal menghapus kategori');
            closeDeleteModal();
        }
    });
};

const toggleStatus = (item) => {
    router.post(`/admin/kategori/${item.id}/toggle-status`, {}, {
        preserveState: true,
        onSuccess: () => {
            toast.success(`Kategori berhasil ${item.is_active ? 'dinonaktifkan' : 'diaktifkan'}`);
        }
    });
};
</script>

<template>
    <Head title="Kategori Kejahatan" />

    <AdminLayout title="Kategori Kejahatan">
        <ToastContainer />
        
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">Kategori Kejahatan</h1>
                    <p class="text-slate-600">Kelola kategori kejahatan siber</p>
                </div>
                <Link
                    href="/admin/kategori/create"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Tambah Kategori
                </Link>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Cari</label>
                        <div class="relative">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Cari nama kategori..."
                                class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                            />
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
                        <select
                            v-model="status"
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                        >
                            <option value="">Semua Status</option>
                            <option value="active">Aktif</option>
                            <option value="inactive">Tidak Aktif</option>
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
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Nama Kategori</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Deskripsi</th>
                                <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider">Jumlah Laporan</th>
                                <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <tr v-if="kategori.data.length === 0">
                                <td colspan="5" class="px-4 py-8 text-center text-slate-500">
                                    Tidak ada data kategori
                                </td>
                            </tr>
                            <tr v-for="item in kategori.data" :key="item.id" class="hover:bg-slate-50 transition">
                                <td class="px-4 py-3">
                                    <p class="font-medium text-slate-800">{{ item.nama }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-sm text-slate-600 line-clamp-2">{{ item.deskripsi || '-' }}</p>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">
                                        {{ item.laporan_count }} laporan
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button
                                        @click="toggleStatus(item)"
                                        :class="[
                                            'px-2 py-1 rounded-full text-xs font-medium transition',
                                            item.is_active 
                                                ? 'bg-green-100 text-green-800 hover:bg-green-200' 
                                                : 'bg-red-100 text-red-800 hover:bg-red-200'
                                        ]"
                                    >
                                        {{ item.is_active ? 'Aktif' : 'Tidak Aktif' }}
                                    </button>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link
                                            :href="`/admin/kategori/${item.id}/edit`"
                                            class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition"
                                            title="Edit"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </Link>
                                        <button
                                            @click="openDeleteModal(item)"
                                            class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition"
                                            title="Hapus"
                                            :disabled="item.laporan_count > 0"
                                            :class="{ 'opacity-50 cursor-not-allowed': item.laporan_count > 0 }"
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
                <div v-if="kategori.last_page > 1" class="px-4 py-3 border-t border-slate-200 bg-slate-50">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-slate-600">
                            Menampilkan {{ kategori.from }} - {{ kategori.to }} dari {{ kategori.total }} data
                        </p>
                        <div class="flex gap-1">
                            <Link
                                v-for="link in kategori.links"
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
            <div v-if="deleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-2 bg-red-100 rounded-full">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-slate-900">Hapus Kategori</h3>
                    </div>
                    
                    <p class="text-slate-600 mb-6">
                        Apakah Anda yakin ingin menghapus kategori <strong>{{ deleteModal.nama }}</strong>? 
                        Tindakan ini tidak dapat dibatalkan.
                    </p>

                    <div class="flex justify-end gap-3">
                        <button @click="closeDeleteModal" class="px-4 py-2 text-slate-700 bg-slate-100 rounded-lg hover:bg-slate-200 transition">
                            Batal
                        </button>
                        <button @click="confirmDelete" class="px-4 py-2 text-white bg-red-500 rounded-lg hover:bg-red-600 transition">
                            Hapus
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
