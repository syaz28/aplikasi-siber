<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ToastContainer from '@/Components/ToastContainer.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch, computed, onMounted } from 'vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    personels: Object,
    filters: Object,
    pangkatOptions: Array,
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
const pangkat = ref(props.filters?.pangkat || '');
const subdit = ref(props.filters?.subdit || '');

const getSubditName = (id) => {
    const subdits = {
        1: 'Subdit 1',
        2: 'Subdit 2',
        3: 'Subdit 3',
    };
    return subdits[id] || '-';
};

const getUnitName = (id) => {
    return id ? `Unit ${id}` : '-';
};

const getPangkatClass = (pangkat) => {
    const highRank = ['JENDERAL', 'KOMJEN', 'IRJEN', 'BRIGJEN', 'KOMBES', 'AKBP'];
    const midRank = ['KOMPOL', 'AKP', 'IPTU', 'IPDA'];
    
    if (highRank.includes(pangkat)) {
        return 'bg-red-100 text-red-800';
    } else if (midRank.includes(pangkat)) {
        return 'bg-yellow-100 text-yellow-800';
    }
    return 'bg-blue-100 text-blue-800';
};

let searchTimeout = null;
const handleSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/admin/personels', {
            search: search.value,
            pangkat: pangkat.value,
            subdit: subdit.value,
        }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
};

watch([search, pangkat, subdit], handleSearch);

const deleteConfirm = ref(null);

const confirmDelete = (id) => {
    deleteConfirm.value = id;
};

const cancelDelete = () => {
    deleteConfirm.value = null;
};

const deletePersonel = () => {
    if (!deleteConfirm.value) return;
    
    router.delete(`/admin/personels/${deleteConfirm.value}`, {
        onSuccess: () => {
            toast.success('Data personel berhasil dihapus');
            deleteConfirm.value = null;
        },
        onError: () => {
            toast.error('Gagal menghapus data personel');
        }
    });
};

const clearFilters = () => {
    search.value = '';
    pangkat.value = '';
    subdit.value = '';
    router.get('/admin/personels');
};

const hasFilters = computed(() => {
    return search.value || pangkat.value || subdit.value;
});
</script>

<template>
    <Head title="Data Personel" />
    <ToastContainer />

    <AdminLayout title="Data Personel">
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-navy">Manajemen Data Personel</h2>
                    <p class="text-gray-500 mt-1">Kelola data anggota personel kepolisian</p>
                </div>
                <Link
                    href="/admin/personels/create"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-tactical-accent hover:bg-tactical-accent-dark text-white rounded-lg font-medium transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Tambah Personel
                </Link>
            </div>

            <!-- Filters Section -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cari</label>
                        <div class="relative">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Cari nama atau NRP..."
                                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-tactical-accent focus:border-tactical-accent"
                            />
                            <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>

                    <!-- Filter Pangkat -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pangkat</label>
                        <select
                            v-model="pangkat"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-tactical-accent focus:border-tactical-accent"
                        >
                            <option value="">Semua Pangkat</option>
                            <option v-for="p in pangkatOptions" :key="p" :value="p">{{ p }}</option>
                        </select>
                    </div>

                    <!-- Filter Subdit -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Subdit</label>
                        <select
                            v-model="subdit"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-tactical-accent focus:border-tactical-accent"
                        >
                            <option value="">Semua Subdit</option>
                            <option v-for="s in subditOptions" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </div>
                </div>

                <!-- Clear Filters -->
                <div v-if="hasFilters" class="mt-3 flex justify-end">
                    <button
                        @click="clearFilters"
                        class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Reset Filter
                    </button>
                </div>
            </div>

            <!-- Data Table -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    NRP
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Nama Lengkap
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Pangkat
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Subdit / Unit
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="personel in personels.data" :key="personel.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-mono font-medium text-gray-900">{{ personel.nrp }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-navy flex items-center justify-center text-white font-semibold text-sm">
                                            {{ personel.nama_lengkap?.charAt(0).toUpperCase() }}
                                        </div>
                                        <span class="text-sm font-medium text-gray-900">{{ personel.nama_lengkap }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        v-if="personel.pangkat"
                                        :class="[getPangkatClass(personel.pangkat), 'px-2.5 py-0.5 rounded-full text-xs font-medium']"
                                    >
                                        {{ personel.pangkat }}
                                    </span>
                                    <span v-else class="text-gray-400 text-sm">-</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ getSubditName(personel.subdit_id) }}</div>
                                    <div class="text-xs text-gray-500">{{ getUnitName(personel.unit_id) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link
                                            :href="`/admin/personels/${personel.id}/edit`"
                                            class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                            title="Edit"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </Link>
                                        <button
                                            @click="confirmDelete(personel.id)"
                                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Hapus"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- Empty State -->
                            <tr v-if="personels.data.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada data personel</h3>
                                    <p class="mt-1 text-sm text-gray-500">
                                        {{ hasFilters ? 'Coba ubah filter pencarian Anda' : 'Mulai dengan menambahkan data personel baru' }}
                                    </p>
                                    <div v-if="!hasFilters" class="mt-4">
                                        <Link
                                            href="/admin/personels/create"
                                            class="inline-flex items-center gap-2 px-4 py-2 bg-tactical-accent hover:bg-tactical-accent-dark text-white rounded-lg font-medium transition-colors"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                            Tambah Personel
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="personels.data.length > 0" class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="text-sm text-gray-500">
                            Menampilkan {{ personels.from }} - {{ personels.to }} dari {{ personels.total }} personel
                        </div>
                        <nav class="flex items-center gap-1">
                            <Link
                                v-for="link in personels.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                :class="[
                                    'px-3 py-1.5 text-sm rounded-lg transition-colors',
                                    link.active
                                        ? 'bg-tactical-accent text-white'
                                        : link.url
                                            ? 'text-gray-700 hover:bg-gray-100'
                                            : 'text-gray-400 cursor-not-allowed'
                                ]"
                                v-html="link.label"
                            />
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="deleteConfirm" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="fixed inset-0 bg-black/50" @click="cancelDelete"></div>
                    <div class="flex min-h-full items-center justify-center p-4">
                        <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
                            <div class="text-center">
                                <div class="mx-auto w-14 h-14 rounded-full bg-red-100 flex items-center justify-center mb-4">
                                    <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">Hapus Data Personel?</h3>
                                <p class="text-sm text-gray-500 mb-6">
                                    Data personel yang dihapus tidak dapat dikembalikan. Yakin ingin melanjutkan?
                                </p>
                                <div class="flex gap-3">
                                    <button
                                        @click="cancelDelete"
                                        class="flex-1 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors"
                                    >
                                        Batal
                                    </button>
                                    <button
                                        @click="deletePersonel"
                                        class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors"
                                    >
                                        Ya, Hapus
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AdminLayout>
</template>
