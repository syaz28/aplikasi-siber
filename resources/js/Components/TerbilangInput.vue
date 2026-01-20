<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: [Number, String],
        default: 0
    },
    label: String,
    error: String,
    required: {
        type: Boolean,
        default: false
    },
    placeholder: {
        type: String,
        default: 'Masukkan nominal'
    }
});

const emit = defineEmits(['update:modelValue']);

// Format as Rupiah
const formatRupiah = (value) => {
    if (!value && value !== 0) return '';
    const num = parseInt(value.toString().replace(/\D/g, '')) || 0;
    return num.toLocaleString('id-ID');
};

// Parse from formatted string
const parseRupiah = (value) => {
    return parseInt(value.toString().replace(/\D/g, '')) || 0;
};

// Terbilang conversion
const terbilang = (num) => {
    const angka = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];
    
    if (num < 12) return angka[num];
    if (num < 20) return terbilang(num - 10) + ' Belas';
    if (num < 100) return terbilang(Math.floor(num / 10)) + ' Puluh' + (num % 10 ? ' ' + terbilang(num % 10) : '');
    if (num < 200) return 'Seratus' + (num % 100 ? ' ' + terbilang(num % 100) : '');
    if (num < 1000) return terbilang(Math.floor(num / 100)) + ' Ratus' + (num % 100 ? ' ' + terbilang(num % 100) : '');
    if (num < 2000) return 'Seribu' + (num % 1000 ? ' ' + terbilang(num % 1000) : '');
    if (num < 1000000) return terbilang(Math.floor(num / 1000)) + ' Ribu' + (num % 1000 ? ' ' + terbilang(num % 1000) : '');
    if (num < 1000000000) return terbilang(Math.floor(num / 1000000)) + ' Juta' + (num % 1000000 ? ' ' + terbilang(num % 1000000) : '');
    if (num < 1000000000000) return terbilang(Math.floor(num / 1000000000)) + ' Miliar' + (num % 1000000000 ? ' ' + terbilang(num % 1000000000) : '');
    if (num < 1000000000000000) return terbilang(Math.floor(num / 1000000000000)) + ' Triliun' + (num % 1000000000000 ? ' ' + terbilang(num % 1000000000000) : '');
    return 'Angka terlalu besar';
};

const displayValue = ref(formatRupiah(props.modelValue));
const terbilangText = computed(() => {
    const num = parseRupiah(displayValue.value);
    if (num === 0) return 'Nol Rupiah';
    return terbilang(num) + ' Rupiah';
});

const handleInput = (event) => {
    const raw = event.target.value;
    const num = parseRupiah(raw);
    displayValue.value = formatRupiah(num);
    emit('update:modelValue', num);
};

watch(() => props.modelValue, (newVal) => {
    displayValue.value = formatRupiah(newVal);
});
</script>

<template>
    <div>
        <label v-if="label" class="block text-sm font-medium text-gray-700 mb-1">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>
        
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="text-gray-500 text-sm">Rp</span>
            </div>
            <input
                type="text"
                :value="displayValue"
                @input="handleInput"
                :placeholder="placeholder"
                class="block w-full pl-10 pr-3 py-2 rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent"
                :class="{ 'border-tactical-danger focus:border-tactical-danger focus:ring-tactical-danger': error }"
            />
        </div>
        
        <!-- Terbilang display -->
        <p class="mt-1 text-sm text-gray-500 italic">
            {{ terbilangText }}
        </p>
        
        <p v-if="error" class="mt-1 text-sm text-tactical-danger">
            {{ error }}
        </p>
    </div>
</template>
