<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
// Use window.axios which has CSRF token configured in bootstrap.js
const axios = window.axios;

/**
 * PhoneInput - Searchable Component with Flag Images
 * Features:
 * - Real flag images from flagcdn.com
 * - Smart search (name, code, alpha-2)
 * - Auto-focus search input
 * - Click-outside to close
 * - Simple absolute positioning
 */

const props = defineProps({
    modelValue: {
        type: String,
        default: ''
    },
    label: {
        type: String,
        default: ''
    },
    required: {
        type: Boolean,
        default: false
    },
    error: {
        type: String,
        default: ''
    },
    placeholder: {
        type: String,
        default: '812345678'
    }
});

const emit = defineEmits(['update:modelValue']);

// State
const selectedCode = ref('+62');
const inputNumber = ref('');
const phoneCodes = ref([]);
const isLoading = ref(false);
const isOpen = ref(false);
const searchQuery = ref('');
const searchInputRef = ref(null);
const containerRef = ref(null);

// Load phone codes on mount
onMounted(async () => {
    await loadPhoneCodes();
    parseInitialValue();
    document.addEventListener('click', handleClickOutside);
});

// Cleanup
onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

// Load phone codes from API
const loadPhoneCodes = async () => {
    isLoading.value = true;
    try {
        const res = await axios.get('/api/master/phone-codes');
        if (res.data.success) {
            phoneCodes.value = res.data.data;
        }
    } catch (err) {
        console.error('Error loading phone codes:', err);
        // Fallback to Indonesia only
        phoneCodes.value = [{ phone_code: '+62', alpha_2: 'id', name: 'Indonesia' }];
    } finally {
        isLoading.value = false;
    }
};

// Parse initial modelValue to extract code and number
const parseInitialValue = () => {
    if (!props.modelValue) return;
    
    const value = props.modelValue;
    const sortedCodes = [...phoneCodes.value].sort((a, b) => 
        b.phone_code.length - a.phone_code.length
    );
    
    const match = sortedCodes.find(c => value.startsWith(c.phone_code));
    if (match) {
        selectedCode.value = match.phone_code;
        inputNumber.value = value.slice(match.phone_code.length);
    } else {
        inputNumber.value = value;
    }
};

// Get current selected country info
const selectedCountry = computed(() => {
    return phoneCodes.value.find(c => c.phone_code === selectedCode.value) || {
        phone_code: '+62',
        alpha_2: 'id',
        name: 'Indonesia'
    };
});

// Filtered phone codes based on search
const filteredCodes = computed(() => {
    if (!searchQuery.value) return phoneCodes.value;
    const q = searchQuery.value.toLowerCase();
    return phoneCodes.value.filter(c => 
        c.name.toLowerCase().includes(q) ||
        c.alpha_2.toLowerCase().includes(q) ||
        c.phone_code.includes(q)
    );
});

// Get flag image URL from CDN
const getFlagUrl = (alpha2) => {
    if (!alpha2) return '';
    return `https://flagcdn.com/w40/${alpha2.toLowerCase()}.png`;
};

// Open dropdown and auto-focus search
const openDropdown = () => {
    isOpen.value = true;
    searchQuery.value = '';
    nextTick(() => searchInputRef.value?.focus());
};

// Close dropdown
const closeDropdown = () => {
    isOpen.value = false;
    searchQuery.value = '';
};

// Click outside handler
const handleClickOutside = (event) => {
    if (containerRef.value && !containerRef.value.contains(event.target)) {
        closeDropdown();
    }
};

// Select a country code
const selectCode = (country) => {
    selectedCode.value = country.phone_code;
    closeDropdown();
};

// Emit combined value when either changes
const emitCombined = () => {
    let number = inputNumber.value;
    
    // Sanitize: remove leading 0
    if (number.startsWith('0')) {
        number = number.slice(1);
        inputNumber.value = number;
    }
    
    const combined = number ? `${selectedCode.value}${number}` : '';
    emit('update:modelValue', combined);
};

watch(selectedCode, emitCombined);
watch(inputNumber, emitCombined);

watch(() => props.modelValue, (newVal) => {
    const currentCombined = inputNumber.value ? `${selectedCode.value}${inputNumber.value}` : '';
    if (newVal !== currentCombined) {
        parseInitialValue();
    }
});

const handleNumberInput = (e) => {
    const value = e.target.value.replace(/[^0-9]/g, '');
    inputNumber.value = value;
};
</script>

<template>
    <div class="w-full">
        <!-- Label -->
        <label v-if="label" class="block text-sm font-medium text-gray-700 mb-1">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>
        
        <!-- Input Container -->
        <div ref="containerRef" class="relative flex rounded-md shadow-sm">
            
            <!-- Country Code Trigger Button -->
            <button 
                type="button"
                @click="openDropdown"
                :disabled="isLoading"
                class="flex items-center gap-2 px-3 py-2 border border-r-0 border-gray-300 bg-gray-50 text-gray-700 hover:bg-gray-100 rounded-l-md focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition-colors min-w-[110px] disabled:opacity-50"
                :class="{ 'border-red-500': error }"
            >
                <!-- Flag Image -->
                <img 
                    v-if="selectedCountry.alpha_2" 
                    :src="getFlagUrl(selectedCountry.alpha_2)" 
                    :alt="selectedCountry.name"
                    class="w-6 h-4 object-cover rounded-sm shadow-sm" 
                    loading="lazy"
                />
                
                <!-- Phone Code -->
                <span class="font-bold text-sm">{{ selectedCode }}</span>
                
                <!-- Dropdown Arrow -->
                <svg 
                    class="w-4 h-4 text-gray-400 ml-auto transition-transform"
                    :class="{ 'rotate-180': isOpen }"
                    fill="none" 
                    viewBox="0 0 24 24" 
                    stroke="currentColor"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            
            <!-- Phone Number Input -->
            <input
                type="text"
                :value="inputNumber"
                @input="handleNumberInput"
                :placeholder="placeholder"
                class="flex-1 block w-full rounded-none rounded-r-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                :class="{ 'border-red-500': error }"
                inputmode="numeric"
            />
            
            <!-- Searchable Dropdown Menu (Absolute Positioning) -->
            <Transition
                enter-active-class="transition duration-150 ease-out"
                enter-from-class="transform scale-95 opacity-0"
                enter-to-class="transform scale-100 opacity-100"
                leave-active-class="transition duration-100 ease-in"
                leave-from-class="transform scale-100 opacity-100"
                leave-to-class="transform scale-95 opacity-0"
            >
                <div 
                    v-if="isOpen" 
                    class="absolute top-full left-0 mt-1 w-80 bg-white border border-gray-300 rounded-lg shadow-xl z-50 flex flex-col max-h-80"
                >
                    <!-- Sticky Search Bar -->
                    <div class="p-2 bg-gray-50 border-b border-gray-200 sticky top-0 z-10 rounded-t-lg">
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </span>
                            <input 
                                ref="searchInputRef"
                                v-model="searchQuery"
                                type="text"
                                class="w-full pl-9 pr-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Cari negara (ketik 62, us, japan)..."
                            />
                        </div>
                    </div>
                    
                    <!-- Scrollable Country List -->
                    <ul class="flex-1 overflow-y-auto py-1 scroll-smooth">
                        <li v-for="country in filteredCodes" :key="country.alpha_2">
                            <button 
                                type="button"
                                @click="selectCode(country)"
                                class="w-full px-4 py-2 text-left flex items-center hover:bg-indigo-50 transition-colors border-b border-gray-50 last:border-0"
                                :class="{ 'bg-indigo-100': country.phone_code === selectedCode }"
                            >
                                <!-- Flag Image -->
                                <img 
                                    :src="getFlagUrl(country.alpha_2)" 
                                    :alt="country.name"
                                    class="w-6 h-4 object-cover rounded-sm shadow-sm mr-3 flex-shrink-0" 
                                    loading="lazy"
                                />
                                
                                <!-- Country Name -->
                                <span class="flex-1 text-sm text-gray-700 truncate mr-2">
                                    {{ country.name }}
                                </span>
                                
                                <!-- Phone Code -->
                                <span class="text-sm font-mono font-bold text-gray-500">
                                    {{ country.phone_code }}
                                </span>
                            </button>
                        </li>
                        
                        <!-- No Results State -->
                        <li v-if="filteredCodes.length === 0" class="px-4 py-6 text-center text-sm text-gray-500">
                            Tidak ditemukan.
                        </li>
                    </ul>
                </div>
            </Transition>
        </div>
        
        <!-- Error Message -->
        <p v-if="error" class="text-sm text-red-600 mt-1">{{ error }}</p>
    </div>
</template>

<style scoped>
/* Smooth scrolling for country list */
.scroll-smooth {
    scroll-behavior: smooth;
}

/* Clean input appearance */
input:focus {
    outline: none;
}
</style>
