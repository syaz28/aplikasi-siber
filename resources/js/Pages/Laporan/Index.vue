<script setup>
import SidebarLayout from '@/Layouts/SidebarLayout.vue';
import ToastContainer from '@/Components/ToastContainer.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    laporan: Object, // Paginated data
    filters: Object,
});

const toast = useToast();

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');

// Status badge colors
const getStatusClass = (stat) => {
    const classes = {
        draft: 'bg-gray-100 text-gray-800',
        submitted: 'bg-blue-100 text-blue-800',
        verified: 'bg-green-100 text-green-800',
        investigating: 'bg-yellow-100 text-yellow-800',
        closed: 'bg-purple-100 text-purple-800',
        rejected: 'bg-red-100 text-red-800',
    };
    return classes[stat] || 'bg-gray-100 text-gray-800';
};

const getStatusLabel = (stat) => {
    const labels = {
        draft: 'Draft',
        submitted: 'Diajukan',
        verified: 'Terverifikasi',
        investigating: 'Dalam Penyelidikan',
        closed: 'Ditutup',
        rejected: 'Ditolak',
    };
    return labels[stat] || stat;
};

// Format date
const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

// Format currency
const formatRupiah = (amount) => {
    if (!amount) return 'Rp 0';
    return 'Rp ' + parseInt(amount).toLocaleString('id-ID');
};

// Search handler with debounce
let searchTimeout = null;
const handleSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/laporan', {
            search: search.value,
            status: status.value,
        }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
};

watch([search, status], handleSearch);

// Delete confirmation
const deleteConfirm = ref(null);

const confirmDelete = (id) => {
    deleteConfirm.value = id;
};

const cancelDelete = () => {
    deleteConfirm.value = null;
};

const deleteLaporan = () => {
    if (!deleteConfirm.value) return;
    
    router.delete(`/laporan/${deleteConfirm.value}`, {
        onSuccess: () => {
            toast.success('Laporan berhasil dihapus');
            deleteConfirm.value = null;
        },
        onError: () => {
            toast.error('Gagal menghapus laporan');
        }
    });
};

// Stats
const stats = computed(() => {
    const data = props.laporan?.data || [];
    return {
        total: props.laporan?.total || 0,
        draft: data.filter(l => l.status === 'draft').length,
        submitted: data.filter(l => l.status === 'submitted').length,
    };
});
</script>

<template>
    <Head title="Arsip Laporan" />

    <SidebarLayout title="Arsip Laporan">
        <ToastContainer />
        
        <div class="max-w-7xl mx-auto">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white rounded-xl shadow-tactical border border-tactical-border p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-tactical-accent/10 rounded-lg">
                            <svg class="w-6 h-6 text-tactical-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-navy">{{ stats.total }}</p>
                            <p class="text-sm text-gray-500">Total Laporan</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-tactical border border-tactical-border p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-gray-100 rounded-lg">
                            <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-600">{{ stats.draft }}</p>
                            <p class="text-sm text-gray-500">Draft</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-tactical border border-tactical-border p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-tactical-success/10 rounded-lg">
                            <svg class="w-6 h-6 text-tactical-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-tactical-success">{{ stats.submitted }}</p>
                            <p class="text-sm text-gray-500">Diajukan</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Header & Actions -->
            <div class="bg-white rounded-xl shadow-tactical border border-tactical-border p-4 mb-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <!-- Search -->
                    <div class="flex-1 max-w-md">
                        <div class="relative">
                            <input
                                type="text"
                                v-model="search"
                                placeholder="Cari laporan (nama, NIK, nomor STPA)..."
                                class="w-full pl-10 pr-4 py-2.5 rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent"
                            />
                            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>

                    <!-- Filter & Add Button -->
                    <div class="flex items-center gap-3">
                        <select
                            v-model="status"
                            class="rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent"
                        >
                            <option value="">Semua Status</option>
                            <option value="draft">Draft</option>
                            <option value="submitted">Diajukan</option>
                            <option value="verified">Terverifikasi</option>
                            <option value="investigating">Dalam Penyelidikan</option>
                            <option value="closed">Ditutup</option>
                            <option value="rejected">Ditolak</option>
                        </select>

                        <Link
                            href="/laporan/create"
                            class="px-4 py-2.5 bg-tactical-accent text-white font-semibold rounded-lg hover:bg-blue-600 transition-colors flex items-center gap-2 min-h-[44px]"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Buat Laporan
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl shadow-tactical border border-tactical-border overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-navy text-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold">No. STPA</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">Tanggal</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">Pelapor</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">Jenis Kejahatan</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">Kerugian</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">Status</th>
                                <th class="px-4 py-3 text-center text-sm font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr
                                v-for="item in laporan?.data || []"
                                :key="item.id"
                                class="hover:bg-gray-50 transition-colors"
                            >
                                <td class="px-4 py-3 text-sm font-medium text-navy">
                                    {{ item.nomor_stpa || '-' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    {{ formatDate(item.tanggal_laporan) }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-gray-800">{{ item.pelapor?.nama || '-' }}</div>
                                    <div class="text-xs text-gray-500">{{ item.pelapor?.telepon || '' }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    {{ item.jenis_kejahatan?.nama || '-' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-800 font-medium">
                                    {{ formatRupiah(item.total_kerugian) }}
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="px-2.5 py-1 text-xs font-semibold rounded-full"
                                        :class="getStatusClass(item.status)"
                                    >
                                        {{ getStatusLabel(item.status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <!-- View -->
                                        <Link
                                            :href="`/laporan/${item.id}`"
                                            class="p-2 text-gray-500 hover:text-tactical-accent hover:bg-gray-100 rounded-lg transition-colors"
                                            title="Lihat Detail"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </Link>
                                        
                                        <!-- Edit -->
                                        <Link
                                            :href="`/laporan/${item.id}/edit`"
                                            class="p-2 text-gray-500 hover:text-tactical-warning hover:bg-yellow-50 rounded-lg transition-colors"
                                            title="Edit"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </Link>
                                        
                                        <!-- PDF -->
                                        <a
                                            :href="`/laporan/${item.id}/pdf`"
                                            target="_blank"
                                            class="p-2 text-gray-500 hover:text-tactical-danger hover:bg-red-50 rounded-lg transition-colors"
                                            title="Download PDF"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </a>
                                        
                                        <!-- Delete -->
                                        <button
                                            @click="confirmDelete(item.id)"
                                            class="p-2 text-gray-500 hover:text-tactical-danger hover:bg-red-50 rounded-lg transition-colors"
                                            title="Hapus"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!laporan?.data?.length">
                                <td colspan="7" class="px-4 py-12 text-center">
                                    <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <p class="text-gray-500">Belum ada laporan</p>
                                    <Link href="/laporan/create" class="mt-2 inline-block text-tactical-accent hover:text-blue-600">
                                        Buat Laporan Pertama →
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="laporan?.links?.length > 3" class="px-4 py-3 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="text-sm text-gray-500">
                        Menampilkan {{ laporan.from }} - {{ laporan.to }} dari {{ laporan.total }} laporan
                    </div>
                    <div class="flex gap-1 flex-wrap justify-center">
                        <template v-for="link in laporan.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                class="px-3 py-1.5 text-sm rounded border transition-colors min-h-[36px] flex items-center"
                                :class="link.active ? 'bg-tactical-accent text-white border-tactical-accent' : 'border-gray-300 hover:bg-gray-50'"
                                v-html="link.label"
                            />
                            <span v-else class="px-3 py-1.5 text-sm text-gray-400" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <Teleport to="body">
            <div v-if="deleteConfirm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                <div class="bg-white rounded-xl shadow-2xl p-6 max-w-md mx-4">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-2 bg-tactical-danger/20 rounded-full">
                            <svg class="w-6 h-6 text-tactical-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-navy">Hapus Laporan?</h3>
                    </div>
                    <p class="text-gray-600 mb-6">
                        Apakah Anda yakin ingin menghapus laporan ini? Tindakan ini tidak dapat dibatalkan.
                    </p>
                    <div class="flex gap-3">
                        <button
                            @click="cancelDelete"
                            class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 font-semibold hover:bg-gray-50 transition-colors"
                        >
                            Batal
                        </button>
                        <button
                            @click="deleteLaporan"
                            class="flex-1 px-4 py-2.5 bg-tactical-danger text-white rounded-lg font-semibold hover:bg-red-700 transition-colors"
                        >
                            Ya, Hapus
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </SidebarLayout>
</template>
