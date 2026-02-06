<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import SidebarLayout from '@/Layouts/SidebarLayout.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PimpinanLayout from '@/Layouts/PimpinanLayout.vue';

const props = defineProps({
    orang: Object,
});

const page = usePage();
const userRole = computed(() => page.props.auth?.user?.role);

// Determine which layout to use based on user role
const LayoutComponent = computed(() => {
    switch (userRole.value) {
        case 'admin':
            return AdminLayout;
        case 'pimpinan':
            return PimpinanLayout;
        default:
            return SidebarLayout;
    }
});

// Get back URL based on role
const getBackUrl = () => {
    switch (userRole.value) {
        case 'admin':
            return '/admin/orang';
        case 'admin_subdit':
            return '/subdit/orang';
        case 'pimpinan':
            return '/pimpinan/orang';
        default:
            return '/orang';
    }
};

// Format date
const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};

// Format currency
const formatRupiah = (amount) => {
    if (!amount) return 'Rp 0';
    return 'Rp ' + parseInt(amount).toLocaleString('id-ID');
};

// Get full address
const getFullAddress = (alamat) => {
    if (!alamat) return '-';
    const parts = [
        alamat.alamat_lengkap,
        alamat.kelurahan?.nama,
        alamat.kecamatan?.nama,
        alamat.kabupaten?.nama,
        alamat.provinsi?.nama,
    ].filter(Boolean);
    return parts.join(', ') || '-';
};

// Status badge classes
const getStatusClass = (status) => {
    const classes = {
        'Penyelidikan': 'bg-blue-100 text-blue-800',
        'Penyidikan': 'bg-indigo-100 text-indigo-800',
        'Tahap I': 'bg-yellow-100 text-yellow-800',
        'Tahap II': 'bg-orange-100 text-orange-800',
        'SP3': 'bg-red-100 text-red-800',
        'RJ': 'bg-green-100 text-green-800',
        'Diversi': 'bg-purple-100 text-purple-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <Head :title="`Detail: ${orang.nama}`" />

    <component :is="LayoutComponent" :title="orang.nama">
        <div class="max-w-5xl mx-auto">
            <!-- Back Button -->
            <div class="mb-6">
                <Link
                    :href="getBackUrl()"
                    class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 transition-colors"
                >
                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Kembali ke Daftar Orang
                </Link>
            </div>

            <!-- Profile Header -->
            <div class="bg-white rounded-xl shadow-tactical border border-tactical-border mb-6 overflow-hidden">
                <div class="bg-gradient-to-r from-navy to-navy-light px-6 py-8">
                    <div class="flex items-center gap-6">
                        <div :class="[
                            'h-20 w-20 rounded-full flex items-center justify-center text-3xl font-bold text-white border-4 border-white/20',
                            orang.jenis_kelamin === 'LAKI-LAKI' ? 'bg-blue-500' : 'bg-pink-500'
                        ]">
                            {{ orang.nama?.charAt(0).toUpperCase() }}
                        </div>
                        <div class="text-white">
                            <h1 class="text-2xl font-bold">{{ orang.nama }}</h1>
                            <div class="flex items-center gap-4 mt-2 text-white/80">
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    {{ orang.jenis_kelamin }}
                                </span>
                                <span v-if="orang.pekerjaan" class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    {{ orang.pekerjaan }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary Stats -->
                <div class="grid grid-cols-3 divide-x divide-gray-200">
                    <div class="px-6 py-4 text-center">
                        <div class="text-2xl font-bold text-green-600">{{ orang.laporan_sebagai_pelapor?.length || 0 }}</div>
                        <div class="text-sm text-gray-500">Sebagai Pelapor</div>
                    </div>
                    <div class="px-6 py-4 text-center">
                        <div class="text-2xl font-bold text-yellow-600">{{ orang.sebagai_korban?.length || 0 }}</div>
                        <div class="text-sm text-gray-500">Sebagai Korban</div>
                    </div>
                    <div class="px-6 py-4 text-center">
                        <div class="text-2xl font-bold text-red-600">{{ orang.sebagai_tersangka?.length || 0 }}</div>
                        <div class="text-sm text-gray-500">Sebagai Tersangka</div>
                    </div>
                </div>
            </div>

            <!-- Personal Information -->
            <div class="bg-white rounded-xl shadow-tactical border border-tactical-border mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-navy">Informasi Pribadi</h2>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">NIK</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-mono">{{ orang.nik || '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Nama Lengkap</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ orang.nama }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Tempat, Tanggal Lahir</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ orang.tempat_lahir }}, {{ formatDate(orang.tanggal_lahir) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Jenis Kelamin</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ orang.jenis_kelamin }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Pekerjaan</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ orang.pekerjaan || '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Pendidikan</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ orang.pendidikan || '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Telepon</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ orang.telepon || '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Email</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ orang.email || '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Kewarganegaraan</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ orang.kewarganegaraan || 'WNI' }}</dd>
                        </div>
                        <div v-if="orang.kewarganegaraan === 'WNA'">
                            <dt class="text-sm font-medium text-gray-500">Negara Asal</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ orang.negara_asal || '-' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Addresses -->
            <div class="bg-white rounded-xl shadow-tactical border border-tactical-border mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-navy">Alamat</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Alamat KTP</h3>
                            <p class="text-sm text-gray-900">{{ getFullAddress(orang.alamat_ktp) }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Alamat Domisili</h3>
                            <p class="text-sm text-gray-900">{{ getFullAddress(orang.alamat_domisili) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Related Reports: As Pelapor -->
            <div v-if="orang.laporan_sebagai_pelapor?.length > 0" class="bg-white rounded-xl shadow-tactical border border-tactical-border mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-navy flex items-center gap-2">
                        <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                        Laporan sebagai Pelapor ({{ orang.laporan_sebagai_pelapor.length }})
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No. STPA</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kategori</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hubungan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="lap in orang.laporan_sebagai_pelapor" :key="lap.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-mono text-gray-900">{{ lap.nomor_stpa }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(lap.tanggal_laporan) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ lap.kategori_kejahatan?.nama || '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 capitalize">{{ lap.hubungan_pelapor?.replace('_', ' ') || '-' }}</td>
                                <td class="px-6 py-4">
                                    <span :class="[getStatusClass(lap.status), 'px-2 py-1 text-xs font-medium rounded-full']">
                                        {{ lap.status }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Related Reports: As Korban -->
            <div v-if="orang.sebagai_korban?.length > 0" class="bg-white rounded-xl shadow-tactical border border-tactical-border mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-navy flex items-center gap-2">
                        <span class="w-2 h-2 bg-yellow-500 rounded-full"></span>
                        Terlibat sebagai Korban ({{ orang.sebagai_korban.length }})
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No. STPA</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kategori</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kerugian</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="korban in orang.sebagai_korban" :key="korban.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-mono text-gray-900">{{ korban.laporan?.nomor_stpa }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(korban.laporan?.tanggal_laporan) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ korban.laporan?.kategori_kejahatan?.nama || '-' }}</td>
                                <td class="px-6 py-4 text-sm font-semibold text-red-600">{{ formatRupiah(korban.kerugian_nominal) }}</td>
                                <td class="px-6 py-4">
                                    <span :class="[getStatusClass(korban.laporan?.status), 'px-2 py-1 text-xs font-medium rounded-full']">
                                        {{ korban.laporan?.status }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Related Reports: As Tersangka -->
            <div v-if="orang.sebagai_tersangka?.length > 0" class="bg-white rounded-xl shadow-tactical border border-tactical-border mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-navy flex items-center gap-2">
                        <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                        Terlibat sebagai Tersangka ({{ orang.sebagai_tersangka.length }})
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No. STPA</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kategori</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Identitas Digital</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="tersangka in orang.sebagai_tersangka" :key="tersangka.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-mono text-gray-900">{{ tersangka.laporan?.nomor_stpa }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(tersangka.laporan?.tanggal_laporan) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ tersangka.laporan?.kategori_kejahatan?.nama || '-' }}</td>
                                <td class="px-6 py-4">
                                    <div v-if="tersangka.identitas?.length > 0" class="flex flex-wrap gap-1">
                                        <span
                                            v-for="id in tersangka.identitas.slice(0, 3)"
                                            :key="id.id"
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-700"
                                        >
                                            {{ id.platform }}: {{ id.identifier }}
                                        </span>
                                        <span v-if="tersangka.identitas.length > 3" class="text-xs text-gray-500">
                                            +{{ tersangka.identitas.length - 3 }} lainnya
                                        </span>
                                    </div>
                                    <span v-else class="text-gray-400 text-sm">-</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="[getStatusClass(tersangka.laporan?.status), 'px-2 py-1 text-xs font-medium rounded-full']">
                                        {{ tersangka.laporan?.status }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </component>
</template>
