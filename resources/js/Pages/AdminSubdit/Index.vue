<script setup>
import SidebarLayout from '@/Layouts/SidebarLayout.vue';
import ToastContainer from '@/Components/ToastContainer.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    laporan: Object, // Paginated data
    filters: Object,
    statusOptions: Object,
    unitOptions: Array,
});

const toast = useToast();

const search = ref(props.filters?.search || '');
const filterStatus = ref(props.filters?.status || '');
const filterUnit = ref(props.filters?.unit || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');

// Status badge colors
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

const getStatusLabel = (stat) => {
    const labels = {
        'Penyelidikan': 'Penyelidikan',
        'Penyidikan': 'Penyidikan',
        'Tahap I': 'Tahap I',
        'Tahap II': 'Tahap II',
        'SP3': 'SP3',
        'RJ': 'Restorative Justice',
        'Diversi': 'Diversi',
    };
    return labels[stat] || stat;
};

// Unit badge colors
const getUnitClass = (unit) => {
    const classes = {
        1: 'bg-emerald-100 text-emerald-800 border-emerald-300',
        2: 'bg-sky-100 text-sky-800 border-sky-300',
        3: 'bg-amber-100 text-amber-800 border-amber-300',
        4: 'bg-rose-100 text-rose-800 border-rose-300',
        5: 'bg-violet-100 text-violet-800 border-violet-300',
    };
    return classes[unit] || 'bg-gray-100 text-gray-800 border-gray-300';
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

// Get total kerugian from korban array
const getTotalKerugian = (item) => {
    if (!item.korban || !Array.isArray(item.korban)) return 0;
    return item.korban.reduce((sum, k) => sum + (parseInt(k.kerugian_nominal) || 0), 0);
};

// Extract nomor urut from nomor_stpa
const getShortStpa = (nomorStpa) => {
    if (!nomorStpa) return '-';
    const parts = nomorStpa.split('/');
    if (parts.length >= 2) {
        return parts[1];
    }
    return nomorStpa;
};

// Search handler with debounce
let searchTimeout = null;
const applyFilters = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/min-ops', {
            search: search.value || undefined,
            status: filterStatus.value || undefined,
            unit: filterUnit.value || undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
        }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
};

watch([search, filterStatus, filterUnit, dateFrom, dateTo], applyFilters);

// Clear filters
const clearFilters = () => {
    search.value = '';
    filterStatus.value = '';
    filterUnit.value = '';
    dateFrom.value = '';
    dateTo.value = '';
};

// Inline editing states
const editingUnit = ref(null);
const editingStatus = ref(null);
const savingUnit = ref(null);
const savingStatus = ref(null);

// Update Unit
const updateUnit = (item, newUnit) => {
    savingUnit.value = item.id;
    router.patch(`/min-ops/kasus/${item.id}/unit`, {
        disposisi_unit: newUnit,
    }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            toast.success(`Unit diperbarui ke Unit ${newUnit}`);
            editingUnit.value = null;
            savingUnit.value = null;
        },
        onError: () => {
            toast.error('Gagal memperbarui unit');
            savingUnit.value = null;
        }
    });
};

// Update Status
const updateStatus = (item, newStatus) => {
    savingStatus.value = item.id;
    router.patch(`/min-ops/kasus/${item.id}/status`, {
        status: newStatus,
    }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            toast.success(`Status diperbarui ke ${getStatusLabel(newStatus)}`);
            editingStatus.value = null;
            savingStatus.value = null;
        },
        onError: () => {
            toast.error('Gagal memperbarui status');
            savingStatus.value = null;
        }
    });
};

// Stats
const stats = computed(() => {
    const data = props.laporan?.data || [];
    return {
        total: props.laporan?.total || 0,
        penyelidikan: data.filter(l => l.status === 'Penyelidikan').length,
        penyidikan: data.filter(l => l.status === 'Penyidikan').length,
        belumDisposisi: data.filter(l => !l.disposisi_unit).length,
    };
});
</script>

<template>
    <Head title="Manajemen Kasus" />

    <SidebarLayout title="Manajemen Kasus">
        <ToastContainer />
        
        <div class="max-w-7xl mx-auto">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-xl shadow-tactical border border-tactical-border p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-tactical-accent/10 rounded-lg">
                            <svg class="w-6 h-6 text-tactical-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-navy">{{ stats.total }}</p>
                            <p class="text-sm text-gray-500">Total Kasus</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-tactical border border-tactical-border p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-blue-600">{{ stats.penyelidikan }}</p>
                            <p class="text-sm text-gray-500">Penyelidikan</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-tactical border border-tactical-border p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-indigo-100 rounded-lg">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-indigo-600">{{ stats.penyidikan }}</p>
                            <p class="text-sm text-gray-500">Penyidikan</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-tactical border border-tactical-border p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-amber-100 rounded-lg">
                            <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-amber-600">{{ stats.belumDisposisi }}</p>
                            <p class="text-sm text-gray-500">Belum Disposisi Unit</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="bg-white rounded-xl shadow-tactical border border-tactical-border p-4 mb-6">
                <div class="flex flex-col lg:flex-row lg:items-center gap-4">
                    <!-- Search -->
                    <div class="flex-1">
                        <div class="relative">
                            <input
                                type="text"
                                v-model="search"
                                placeholder="Cari nama pelapor atau nomor STPA..."
                                class="w-full pl-10 pr-4 py-2.5 rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent"
                            />
                            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>

                    <!-- Filters Row -->
                    <div class="flex flex-wrap items-center gap-3">
                        <!-- Status Filter -->
                        <select
                            v-model="filterStatus"
                            class="rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent text-sm"
                        >
                            <option value="">Semua Status</option>
                            <option v-for="(label, value) in statusOptions" :key="value" :value="value">
                                {{ label }}
                            </option>
                        </select>

                        <!-- Unit Filter -->
                        <select
                            v-model="filterUnit"
                            class="rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent text-sm"
                        >
                            <option value="">Semua Unit</option>
                            <option v-for="unit in unitOptions" :key="unit" :value="unit">
                                Unit {{ unit }}
                            </option>
                        </select>

                        <!-- Date From -->
                        <input
                            type="date"
                            v-model="dateFrom"
                            class="rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent text-sm"
                            placeholder="Dari"
                        />

                        <!-- Date To -->
                        <input
                            type="date"
                            v-model="dateTo"
                            class="rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent text-sm"
                            placeholder="Sampai"
                        />

                        <!-- Clear Filters -->
                        <button
                            v-if="search || filterStatus || filterUnit || dateFrom || dateTo"
                            @click="clearFilters"
                            class="px-3 py-2 text-sm text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition-colors"
                        >
                            Reset Filter
                        </button>
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
                                <th class="px-4 py-3 text-left text-sm font-semibold">Kategori</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">Unit</th>
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
                                    {{ getShortStpa(item.nomor_stpa) }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    {{ formatDate(item.tanggal_laporan) }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-gray-800">{{ item.pelapor?.nama || '-' }}</div>
                                    <div class="text-xs text-gray-500">{{ item.kategori_kejahatan?.nama || '' }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    {{ item.kategori_kejahatan?.nama || '-' }}
                                </td>
                                
                                <!-- Unit Column (Locked after first selection) -->
                                <td class="px-4 py-3">
                                    <!-- Show dropdown only when not yet assigned -->
                                    <div v-if="!item.disposisi_unit && editingUnit === item.id" class="flex items-center gap-1">
                                        <select
                                            :value="item.disposisi_unit"
                                            @change="updateUnit(item, $event.target.value)"
                                            :disabled="savingUnit === item.id"
                                            class="text-xs rounded border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent py-1 px-2"
                                        >
                                            <option value="">Pilih</option>
                                            <option v-for="unit in unitOptions" :key="unit" :value="unit">
                                                Unit {{ unit }}
                                            </option>
                                        </select>
                                        <button
                                            @click="editingUnit = null"
                                            class="p-1 text-gray-400 hover:text-gray-600"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                    <!-- Locked badge with lock icon when already assigned -->
                                    <div v-else-if="item.disposisi_unit" class="flex items-center gap-1">
                                        <span
                                            class="px-2 py-0.5 text-xs font-semibold rounded border"
                                            :class="getUnitClass(item.disposisi_unit)"
                                        >
                                            Unit {{ item.disposisi_unit }}
                                        </span>
                                        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="Ubah di halaman detail">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                    </div>
                                    <!-- Editable button when not yet assigned -->
                                    <button
                                        v-else
                                        @click="editingUnit = item.id"
                                        class="group flex items-center gap-1 cursor-pointer"
                                    >
                                        <span class="text-xs text-gray-400 italic">
                                            Belum disposisi
                                        </span>
                                        <svg class="w-3 h-3 text-gray-300 group-hover:text-tactical-accent transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </button>
                                </td>

                                <!-- Status Column (Locked - change via detail page) -->
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1">
                                        <span
                                            class="px-2.5 py-1 text-xs font-semibold rounded-full"
                                            :class="getStatusClass(item.status)"
                                        >
                                            {{ getStatusLabel(item.status) }}
                                        </span>
                                        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="Ubah di halaman detail">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <!-- View Detail -->
                                        <Link
                                            :href="`/min-ops/kasus/${item.id}`"
                                            class="p-2 text-gray-500 hover:text-tactical-accent hover:bg-gray-100 rounded-lg transition-colors"
                                            title="Lihat Detail"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
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
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!laporan?.data?.length">
                                <td colspan="7" class="px-4 py-12 text-center">
                                    <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <p class="text-gray-500">Belum ada kasus yang didisposisikan ke subdit Anda</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="laporan?.links?.length > 3" class="px-4 py-3 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="text-sm text-gray-500">
                        Menampilkan {{ laporan.from }} - {{ laporan.to }} dari {{ laporan.total }} kasus
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
    </SidebarLayout>
</template>
