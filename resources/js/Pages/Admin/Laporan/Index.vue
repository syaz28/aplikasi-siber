<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ToastContainer from '@/Components/ToastContainer.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch, onMounted } from 'vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    laporan: Object,
    filters: Object,
    subditOptions: Array,
    statusOptions: Array,
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
const assigned = ref(props.filters?.assigned || '');
const subdit = ref(props.filters?.subdit || '');
const status = ref(props.filters?.status || '');

let searchTimeout = null;
const handleSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/admin/laporan', {
            search: search.value,
            assigned: assigned.value,
            subdit: subdit.value,
            status: status.value,
        }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
};

watch([search, assigned, subdit, status], handleSearch);

// Assign modal
const assignModal = ref(null);
const selectedSubdit = ref('');
const assigningId = ref(null);

const openAssignModal = (lap) => {
    assignModal.value = lap;
    selectedSubdit.value = lap.assigned_subdit || '';
};

const closeAssignModal = () => {
    assignModal.value = null;
    selectedSubdit.value = '';
};

const assignSubdit = () => {
    if (!assignModal.value || !selectedSubdit.value) return;
    
    router.post(`/admin/laporan/${assignModal.value.id}/assign`, {
        subdit: selectedSubdit.value,
    }, {
        onSuccess: () => {
            toast.success('Laporan berhasil ditugaskan');
            closeAssignModal();
        },
        onError: () => {
            toast.error('Gagal menugaskan laporan');
        }
    });
};

// Inline assign subdit (directly from table)
const assignSubditInline = (lapId, subditValue) => {
    if (!subditValue) return;
    assigningId.value = lapId;
    
    router.post(`/admin/laporan/${lapId}/assign`, {
        subdit: subditValue,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Laporan berhasil ditugaskan ke Subdit ' + subditValue);
            assigningId.value = null;
        },
        onError: () => {
            toast.error('Gagal menugaskan laporan');
            assigningId.value = null;
        }
    });
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

const getShortStpa = (nomorStpa) => {
    if (!nomorStpa) return '-';
    const parts = nomorStpa.split('/');
    if (parts.length >= 2) {
        return parts[1];
    }
    return nomorStpa;
};

const formatRupiah = (amount) => {
    if (!amount) return 'Rp 0';
    return 'Rp ' + parseInt(amount).toLocaleString('id-ID');
};

const getTotalKerugian = (korban) => {
    if (!korban || !Array.isArray(korban)) return 0;
    return korban.reduce((sum, k) => sum + (parseInt(k.kerugian_nominal) || 0), 0);
};

const getStatusClass = (stat) => {
    const classes = {
        'Penyelidikan': 'bg-blue-100 text-blue-800',
        'Penyidikan': 'bg-indigo-100 text-indigo-800',
        'Tahap I': 'bg-yellow-100 text-yellow-800',
        'Tahap II': 'bg-orange-100 text-orange-800',
        'SP3': 'bg-red-100 text-red-800',
        'RJ': 'bg-green-100 text-green-800',
        'Diversi': 'bg-purple-100 text-purple-800',
    };
    return classes[stat] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <Head title="Laporan Masuk" />

    <AdminLayout title="Laporan Masuk">
        <ToastContainer />
        
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-slate-800">Laporan Masuk</h1>
                <p class="text-slate-600">Kelola dan tugaskan laporan ke subdit</p>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Cari</label>
                        <div class="relative">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Cari nomor STPA, nama pelapor..."
                                class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                            />
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Status Tugas</label>
                        <select
                            v-model="assigned"
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                        >
                            <option value="">Semua</option>
                            <option value="no">Belum Ditugaskan</option>
                            <option value="yes">Sudah Ditugaskan</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Subdit</label>
                        <select
                            v-model="subdit"
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                        >
                            <option value="">Semua Subdit</option>
                            <option v-for="opt in subditOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Status Kasus</label>
                        <select
                            v-model="status"
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                        >
                            <option value="">Semua Status</option>
                            <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
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
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">No. STPA</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Pelapor</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Kategori</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Kerugian</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Subdit</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <tr v-if="laporan.data.length === 0">
                                <td colspan="7" class="px-4 py-8 text-center text-slate-500">
                                    Tidak ada data laporan
                                </td>
                            </tr>
                            <tr v-for="lap in laporan.data" :key="lap.id" class="hover:bg-slate-50 transition">
                                <td class="px-4 py-3">
                                    <span class="font-mono text-sm font-medium text-slate-800">
                                        {{ getShortStpa(lap.nomor_stpa) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-sm font-medium text-slate-800">{{ lap.pelapor?.nama }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm text-slate-600">{{ lap.kategori_kejahatan?.nama }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm font-medium text-red-600">
                                        {{ formatRupiah(getTotalKerugian(lap.korban)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="['px-2 py-1 rounded-full text-xs font-medium', getStatusClass(lap.status)]">
                                        {{ lap.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="relative">
                                        <!-- Show locked badge when already assigned -->
                                        <div v-if="lap.assigned_subdit" class="flex items-center gap-2">
                                            <span class="px-3 py-1.5 text-xs font-medium rounded-lg bg-green-50 border border-green-300 text-green-800">
                                                Subdit {{ lap.assigned_subdit }}
                                            </span>
                                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="Terkunci - Ubah di halaman detail">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                            </svg>
                                        </div>
                                        <!-- Show dropdown only when not assigned -->
                                        <template v-else>
                                            <select
                                                :value="lap.assigned_subdit || ''"
                                                @change="assignSubditInline(lap.id, $event.target.value)"
                                                :disabled="assigningId === lap.id"
                                                :class="[
                                                    'appearance-none w-full pl-3 pr-8 py-1.5 text-xs font-medium rounded-lg border cursor-pointer transition',
                                                    'bg-red-50 border-red-300 text-red-800',
                                                    assigningId === lap.id ? 'opacity-50 cursor-wait' : 'hover:border-tactical-accent'
                                                ]"
                                            >
                                                <option value="" disabled>Pilih Subdit</option>
                                                <option v-for="opt in subditOptions" :key="opt.value" :value="opt.value">
                                                    {{ opt.label }}
                                                </option>
                                            </select>
                                            <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                                <svg v-if="assigningId === lap.id" class="w-4 h-4 text-slate-500 animate-spin" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <svg v-else class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </div>
                                        </template>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Link
                                        :href="`/admin/laporan/${lap.id}`"
                                        class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition inline-flex"
                                        title="Lihat Detail"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="laporan.last_page > 1" class="px-4 py-3 border-t border-slate-200 bg-slate-50">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-slate-600">
                            Menampilkan {{ laporan.from }} - {{ laporan.to }} dari {{ laporan.total }} data
                        </p>
                        <div class="flex gap-1">
                            <Link
                                v-for="link in laporan.links"
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

        <!-- Assign Modal -->
        <Teleport to="body">
            <div v-if="assignModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-2 bg-amber-100 rounded-full">
                            <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-slate-900">Tugaskan ke Subdit</h3>
                    </div>
                    
                    <div class="mb-4">
                        <p class="text-sm text-slate-600 mb-2">Laporan:</p>
                        <p class="font-mono font-medium text-slate-800">{{ assignModal.nomor_stpa }}</p>
                        <p class="text-sm text-slate-500">{{ assignModal.pelapor?.nama }}</p>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-slate-700 mb-2">Pilih Subdit</label>
                        <div class="grid grid-cols-3 gap-3">
                            <button
                                v-for="opt in subditOptions"
                                :key="opt.value"
                                @click="selectedSubdit = opt.value"
                                :class="[
                                    'py-3 px-4 rounded-lg border-2 font-medium transition',
                                    selectedSubdit === opt.value
                                        ? 'border-amber-500 bg-amber-50 text-amber-700'
                                        : 'border-slate-200 text-slate-600 hover:border-slate-300'
                                ]"
                            >
                                {{ opt.label }}
                            </button>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button @click="closeAssignModal" class="px-4 py-2 text-slate-700 bg-slate-100 rounded-lg hover:bg-slate-200 transition">
                            Batal
                        </button>
                        <button
                            @click="assignSubdit"
                            :disabled="!selectedSubdit"
                            class="px-4 py-2 text-white bg-amber-500 rounded-lg hover:bg-amber-600 transition disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Tugaskan
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>

<style scoped>
/* Hide default select arrow in all browsers */
select.appearance-none::-ms-expand {
    display: none;
}
select.appearance-none {
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    background-image: none;
}
</style>