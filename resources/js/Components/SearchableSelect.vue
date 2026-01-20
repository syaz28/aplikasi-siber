<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: ''
    },
    options: {
        type: Array,
        default: () => []
    },
    valueKey: {
        type: String,
        default: 'id'
    },
    labelKey: {
        type: String,
        default: 'nama'
    },
    displayKey: {
        type: String,
        default: null // If null, uses labelKey
    },
    placeholder: {
        type: String,
        default: '-- Pilih --'
    },
    searchPlaceholder: {
        type: String,
        default: 'Ketik untuk mencari...'
    },
    disabled: {
        type: Boolean,
        default: false
    },
    error: {
        type: String,
        default: ''
    },
    loading: {
        type: Boolean,
        default: false
    },
    variant: {
        type: String,
        default: 'default', // 'default', 'officer', 'location'
        validator: (value) => ['default', 'officer', 'location'].includes(value)
    },
    size: {
        type: String,
        default: 'md', // 'sm', 'md', 'lg'
        validator: (value) => ['sm', 'md', 'lg'].includes(value)
    }
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const searchQuery = ref('');
const highlightedIndex = ref(-1);
const containerRef = ref(null);
const searchInputRef = ref(null);
const optionsRef = ref(null);

// Get display text for an option
const getDisplayText = (option) => {
    if (!option) return '';
    if (props.displayKey) {
        return option[props.displayKey] || '';
    }
    return option[props.labelKey] || '';
};

// Get the value of an option
const getValue = (option) => {
    if (!option) return '';
    return option[props.valueKey];
};

// Currently selected option
const selectedOption = computed(() => {
    if (!props.modelValue) return null;
    return props.options.find(opt => getValue(opt) == props.modelValue);
});

// Display text for selected value
const selectedDisplayText = computed(() => {
    if (!selectedOption.value) return '';
    return getDisplayText(selectedOption.value);
});

// Filtered options based on search
const filteredOptions = computed(() => {
    if (!searchQuery.value) return props.options;
    
    const query = searchQuery.value.toLowerCase();
    return props.options.filter(option => {
        const label = (option[props.labelKey] || '').toLowerCase();
        const display = props.displayKey ? (option[props.displayKey] || '').toLowerCase() : '';
        return label.includes(query) || display.includes(query);
    });
});

// Open dropdown
const openDropdown = () => {
    if (props.disabled) return;
    isOpen.value = true;
    searchQuery.value = '';
    highlightedIndex.value = -1;
    
    // Focus search input after dropdown opens
    setTimeout(() => {
        searchInputRef.value?.focus();
    }, 50);
};

// Close dropdown
const closeDropdown = () => {
    isOpen.value = false;
    searchQuery.value = '';
    highlightedIndex.value = -1;
};

// Select an option
const selectOption = (option) => {
    emit('update:modelValue', getValue(option));
    closeDropdown();
};

// Clear selection
const clearSelection = () => {
    emit('update:modelValue', '');
    closeDropdown();
};

// Keyboard navigation
const handleKeydown = (event) => {
    if (!isOpen.value) {
        if (event.key === 'Enter' || event.key === ' ' || event.key === 'ArrowDown') {
            event.preventDefault();
            openDropdown();
        }
        return;
    }

    switch (event.key) {
        case 'ArrowDown':
            event.preventDefault();
            highlightedIndex.value = Math.min(
                highlightedIndex.value + 1,
                filteredOptions.value.length - 1
            );
            scrollToHighlighted();
            break;
        case 'ArrowUp':
            event.preventDefault();
            highlightedIndex.value = Math.max(highlightedIndex.value - 1, 0);
            scrollToHighlighted();
            break;
        case 'Enter':
            event.preventDefault();
            if (highlightedIndex.value >= 0 && filteredOptions.value[highlightedIndex.value]) {
                selectOption(filteredOptions.value[highlightedIndex.value]);
            }
            break;
        case 'Escape':
            event.preventDefault();
            closeDropdown();
            break;
        case 'Tab':
            closeDropdown();
            break;
    }
};

// Scroll to highlighted option
const scrollToHighlighted = () => {
    if (!optionsRef.value) return;
    const items = optionsRef.value.querySelectorAll('[data-option]');
    if (items[highlightedIndex.value]) {
        items[highlightedIndex.value].scrollIntoView({ block: 'nearest' });
    }
};

// Click outside handler
const handleClickOutside = (event) => {
    if (containerRef.value && !containerRef.value.contains(event.target)) {
        closeDropdown();
    }
};

// Watch for search changes to reset highlight
watch(searchQuery, () => {
    highlightedIndex.value = filteredOptions.value.length > 0 ? 0 : -1;
});

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div ref="containerRef" class="relative">
        <!-- Trigger Button -->
        <button
            type="button"
            @click="isOpen ? closeDropdown() : openDropdown()"
            @keydown="handleKeydown"
            :disabled="disabled"
            class="relative w-full cursor-pointer rounded-lg border bg-white py-2.5 pl-3 pr-10 text-left shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-tactical-accent focus:border-tactical-accent disabled:bg-gray-100 disabled:cursor-not-allowed"
            :class="[
                error ? 'border-tactical-danger' : 'border-gray-300',
                isOpen ? 'ring-2 ring-tactical-accent border-tactical-accent' : ''
            ]"
        >
            <!-- Selected Value or Placeholder -->
            <span class="block truncate" :class="selectedOption ? 'text-gray-900' : 'text-gray-400'">
                {{ selectedOption ? selectedDisplayText : placeholder }}
            </span>

            <!-- Loading or Arrow Icon -->
            <span class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                <svg v-if="loading" class="animate-spin h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <svg v-else class="h-5 w-5 text-gray-400 transition-transform" :class="{ 'rotate-180': isOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </span>
        </button>

        <!-- Dropdown Panel -->
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="transform scale-95 opacity-0 -translate-y-1"
            enter-to-class="transform scale-100 opacity-100 translate-y-0"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="transform scale-100 opacity-100 translate-y-0"
            leave-to-class="transform scale-95 opacity-0 -translate-y-1"
        >
            <div
                v-show="isOpen"
                class="absolute z-50 mt-2 w-full rounded-xl bg-white shadow-xl ring-1 ring-gray-200 overflow-hidden"
            >
                <!-- Search Input -->
                <div class="p-3 border-b border-gray-200 bg-gray-50">
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            ref="searchInputRef"
                            v-model="searchQuery"
                            type="text"
                            :placeholder="searchPlaceholder"
                            @keydown="handleKeydown"
                            class="w-full pl-10 pr-4 py-2.5 text-sm border-0 bg-white rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-tactical-accent"
                        />
                    </div>
                </div>

                <!-- Options List -->
                <ul
                    ref="optionsRef"
                    class="max-h-72 overflow-y-auto py-1 scroll-smooth"
                >
                    <!-- Clear Option -->
                    <li
                        v-if="selectedOption"
                        @click="clearSelection"
                        class="relative cursor-pointer select-none py-2 px-3 text-gray-500 hover:bg-gray-100 text-sm border-b border-gray-100"
                    >
                        <span class="flex items-center gap-2">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Hapus pilihan
                        </span>
                    </li>

                    <!-- Options -->
                    <li
                        v-for="(option, index) in filteredOptions"
                        :key="getValue(option)"
                        data-option
                        @click="selectOption(option)"
                        class="relative cursor-pointer select-none py-3 px-4 transition-all duration-150 border-b border-gray-50 last:border-0"
                        :class="[
                            getValue(option) == modelValue 
                                ? 'bg-tactical-accent text-white font-medium' 
                                : highlightedIndex === index 
                                    ? 'bg-blue-50 text-navy' 
                                    : 'text-gray-700 hover:bg-gray-50'
                        ]"
                    >
                        <div class="flex items-center justify-between">
                            <span class="block truncate">
                                {{ getDisplayText(option) }}
                            </span>
                            
                            <!-- Check mark for selected -->
                            <svg
                                v-if="getValue(option) == modelValue"
                                class="h-5 w-5 flex-shrink-0 ml-2"
                                fill="none" 
                                stroke="currentColor" 
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </li>

                    <!-- No Results -->
                    <li v-if="filteredOptions.length === 0" class="py-6 px-4 text-center">
                        <svg class="mx-auto h-10 w-10 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-gray-500 text-sm font-medium">Tidak ditemukan</p>
                        <p class="text-gray-400 text-xs mt-1">Coba kata kunci lain</p>
                    </li>
                </ul>
            </div>
        </Transition>

        <!-- Error Message -->
        <p v-if="error" class="mt-1 text-sm text-tactical-danger">{{ error }}</p>
    </div>
</template>
