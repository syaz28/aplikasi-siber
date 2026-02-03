<script setup>
import { ref, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import axios from 'axios';

// Simple debounce function
function debounce(fn, delay) {
    let timeoutId;
    return function (...args) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn.apply(this, args), delay);
    };
}

const form = useForm({
    pawas_id: null,
});

const selectedPersonel = ref(null);
const searchQuery = ref('');
const personels = ref([]);
const totalResults = ref(0);
const isLoading = ref(false);
const hasSearched = ref(false);

// Debounced search function
const searchPersonels = debounce(async (query) => {
    isLoading.value = true;
    hasSearched.value = true;
    
    try {
        const response = await axios.get(route('pawas.search'), {
            params: { q: query }
        });
        personels.value = response.data.personels;
        totalResults.value = response.data.total;
    } catch (error) {
        console.error('Search error:', error);
        personels.value = [];
        totalResults.value = 0;
    } finally {
        isLoading.value = false;
    }
}, 300);

// Watch search query changes
watch(searchQuery, (newQuery) => {
    if (newQuery.length >= 2) {
        searchPersonels(newQuery);
    } else if (newQuery.length === 0) {
        // Load initial data when empty
        searchPersonels('');
    } else {
        personels.value = [];
        totalResults.value = 0;
        hasSearched.value = false;
    }
});

// Load initial data
searchPersonels('');

const submit = () => {
    if (!form.pawas_id) {
        alert('Silakan pilih identitas Pawas terlebih dahulu');
        return;
    }
    
    form.post(route('pawas.store'), {
        onError: () => {
            alert('Terjadi kesalahan. Silakan coba lagi.');
        }
    });
};

const selectPersonel = (personel) => {
    form.pawas_id = personel.id;
    selectedPersonel.value = personel;
};

const clearSearch = () => {
    searchQuery.value = '';
    searchPersonels('');
};
</script>

<template>
    <Head title="Pilih Identitas Pawas" />

    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-navy via-slate-800 to-navy">
        <!-- Background Pattern -->
        <div class="absolute inset-0 bg-[url('/images/grid-pattern.svg')] opacity-5"></div>

        <!-- Selection Card -->
        <div class="relative w-full max-w-2xl mx-4">
            <!-- Logo Section -->
            <div class="text-center mb-8">
                <img
                    src="/images/siber_logo.png"
                    alt="DITRESIBER Logo"
                    class="h-20 mx-auto mb-4"
                />
                <h1 class="text-2xl font-bold text-white">
                    DITRESSIBER POLDA JATENG
                </h1>
                <p class="text-slate-400 text-sm mt-1">
                    Sistem Pelaporan Kejahatan Siber
                </p>
            </div>

            <!-- Card -->
            <div class="bg-white/10 backdrop-blur-xl rounded-2xl border border-white/20 shadow-2xl overflow-hidden">
                <!-- Header -->
                <div class="bg-navy px-6 py-4 border-l-4 border-tactical-accent">
                    <h2 class="text-lg font-bold text-white flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Pilih Identitas Petugas Piket (Pawas)
                    </h2>
                    <p class="text-slate-300 text-sm mt-1">
                        Pilih identitas Anda untuk memulai menggunakan sistem
                    </p>
                </div>

                <div class="p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Search Box -->
                        <div>
                            <label class="block text-sm font-medium text-white mb-2">
                                Cari Petugas
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Cari berdasarkan nama, NRP, atau pangkat..."
                                    class="w-full pl-10 pr-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-tactical-accent focus:border-transparent"
                                />
                                <button
                                    v-if="searchQuery"
                                    type="button"
                                    @click="searchQuery = ''"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center"
                                >
                                    <svg class="w-5 h-5 text-slate-400 hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Personel List -->
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <label class="block text-sm font-medium text-white">
                                    Daftar Petugas
                                </label>
                                <span class="text-xs text-slate-400">
                                    <template v-if="isLoading">
                                        <svg class="animate-spin h-4 w-4 inline mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Mencari...
                                    </template>
                                    <template v-else>
                                        Menampilkan {{ personels.length }} dari {{ totalResults }} petugas
                                    </template>
                                </span>
                            </div>
                            
                            <!-- Loading State -->
                            <div v-if="isLoading" class="space-y-2">
                                <div v-for="n in 3" :key="n" class="animate-pulse w-full px-4 py-3 rounded-lg border-2 border-white/10 bg-white/5">
                                    <div class="h-4 bg-white/20 rounded w-3/4 mb-2"></div>
                                    <div class="h-3 bg-white/10 rounded w-1/2"></div>
                                </div>
                            </div>
                            
                            <!-- Initial State -->
                            <div v-else-if="!hasSearched && personels.length === 0 && searchQuery.length < 2" class="text-center py-8">
                                <svg class="w-12 h-12 text-slate-500 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <p class="text-slate-400 text-sm">
                                    Ketik minimal 2 karakter untuk mencari petugas
                                </p>
                            </div>
                            
                            <div v-else-if="personels.length > 0" class="space-y-2 max-h-96 overflow-y-auto pr-2">
                                <button
                                    v-for="personel in personels"
                                    :key="personel.id"
                                    type="button"
                                    @click="selectPersonel(personel)"
                                    :class="[
                                        'w-full text-left px-4 py-3 rounded-lg border-2 transition-all',
                                        form.pawas_id === personel.id
                                            ? 'border-tactical-accent bg-tactical-accent/20 text-white'
                                            : 'border-white/20 bg-white/5 text-slate-300 hover:border-white/40 hover:bg-white/10'
                                    ]"
                                >
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <div class="font-semibold text-base">
                                                {{ personel.nama_lengkap }}
                                            </div>
                                            <div class="text-sm mt-1 opacity-80">
                                                {{ personel.pangkat }} • NRP: {{ personel.nrp }}
                                            </div>
                                        </div>
                                        <div v-if="form.pawas_id === personel.id" class="ml-3">
                                            <svg class="w-6 h-6 text-tactical-accent" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </div>
                                </button>
                            </div>
                            
                            <!-- Empty State -->
                            <div v-else-if="hasSearched && searchQuery.length >= 2" class="text-center py-12">
                                <svg class="w-16 h-16 text-slate-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-slate-400 text-sm">
                                    Tidak ada petugas yang sesuai dengan pencarian "{{ searchQuery }}"
                                </p>
                                <button
                                    type="button"
                                    @click="clearSearch"
                                    class="mt-3 text-tactical-accent hover:text-tactical-accent-dark text-sm font-medium"
                                >
                                    Reset Pencarian
                                </button>
                            </div>
                            
                            <!-- More results hint -->
                            <div v-if="totalResults > 5 && personels.length > 0" class="mt-3 text-center">
                                <p class="text-xs text-slate-400">
                                    {{ totalResults - 5 }} petugas lainnya. Ketik lebih spesifik untuk mempersempit hasil.
                                </p>
                            </div>
                        </div>

                        <!-- Selected Info -->
                        <div v-if="selectedPersonel" class="bg-green-500/20 border border-green-500/30 rounded-lg p-4">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                <div class="text-green-100">
                                    <span class="font-medium">Dipilih:</span>
                                    <span class="ml-2">{{ selectedPersonel.nama_lengkap }} ({{ selectedPersonel.pangkat }})</span>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button
                            type="submit"
                            :disabled="!form.pawas_id || form.processing"
                            class="w-full px-6 py-4 bg-tactical-accent hover:bg-tactical-accent-dark text-white rounded-lg font-semibold text-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-3"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                            </svg>
                            <span v-if="form.processing">Memproses...</span>
                            <span v-else>Masuk Sistem</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-6">
                <p class="text-slate-400 text-sm">
                    © {{ new Date().getFullYear() }} DITRESSIBER POLDA JATENG
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Custom scrollbar for personel list */
.max-h-96::-webkit-scrollbar {
    width: 8px;
}

.max-h-96::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 4px;
}

.max-h-96::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 4px;
}

.max-h-96::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.3);
}
</style>
