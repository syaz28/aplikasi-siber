<script setup>
import { ref, computed, watch } from 'vue';
import { formatUtils } from '@/Composables/useFormStorage';

const props = defineProps({
    modelValue: {
        type: [String, Number], // Accept both String and Number
        default: ''
    },
    type: {
        type: String,
        default: 'text', // text, nik, phone, currency, name, email, number
        validator: (v) => ['text', 'nik', 'phone', 'currency', 'name', 'email', 'number'].includes(v)
    },
    label: String,
    placeholder: String,
    required: {
        type: Boolean,
        default: false
    },
    error: String,
    disabled: Boolean,
    maxlength: [String, Number],
    helpText: String,
    showValidIcon: {
        type: Boolean,
        default: true
    }
});

const emit = defineEmits(['update:modelValue']);

const inputRef = ref(null);
const displayValue = ref('');
const isValid = ref(null); // null = empty, true = valid, false = invalid
const validationMessage = ref('');

// Validation patterns
const patterns = {
    nik: /^\d{16}$/, // exactly 16 digits
    phone: /^(08|62)\d{8,13}$/, // Indonesian phone format
    email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/, // basic email
    name: /^[a-zA-Z\s'.,-]+$/, // letters, spaces, common punctuation
    number: /^\d+$/, // only digits
};

// Allowed characters during typing
const allowedChars = {
    nik: /^\d$/,
    phone: /^\d$/,
    currency: /^\d$/,
    number: /^\d$/,
    name: /^[a-zA-Z\s'.,-]$/,
    email: /^[a-zA-Z0-9@._-]$/,
    text: /^.$/,
};

// Help text by type
const typeHelpText = {
    nik: 'Masukkan 16 digit angka NIK',
    phone: 'Contoh: 08123456789',
    email: 'Contoh: email@domain.com',
    name: 'Hanya huruf dan spasi',
    currency: 'Masukkan nominal dalam angka',
    number: 'Hanya angka',
};

// Format function based on type
const formatValue = (value) => {
    if (!value) return '';
    switch (props.type) {
        case 'nik':
            return formatUtils.formatNik(value);
        case 'phone':
            return formatUtils.formatPhone(value);
        case 'currency':
            return parseInt(value).toLocaleString('id-ID');
        default:
            return value;
    }
};

// Parse function based on type
const parseValue = (formatted) => {
    if (!formatted) return '';
    switch (props.type) {
        case 'nik':
            return formatUtils.parseNik(formatted);
        case 'phone':
            return formatUtils.parsePhone(formatted);
        case 'currency':
            return formatUtils.parseCurrency(formatted);
        case 'number':
            return formatted.replace(/\D/g, '');
        default:
            return formatted;
    }
};

// Validate value
const validateValue = (value) => {
    if (!value) {
        isValid.value = null;
        validationMessage.value = '';
        return;
    }

    switch (props.type) {
        case 'nik':
            if (value.length < 16) {
                isValid.value = false;
                validationMessage.value = `${value.length}/16 digit`;
            } else if (value.length === 16) {
                isValid.value = true;
                validationMessage.value = '✓ NIK valid';
            }
            break;
        case 'phone':
            if (value.length < 10) {
                isValid.value = false;
                validationMessage.value = 'Minimal 10 digit';
            } else if (patterns.phone.test(value)) {
                isValid.value = true;
                validationMessage.value = '✓ No. Telepon valid';
            } else {
                isValid.value = false;
                validationMessage.value = 'Format: 08xx atau 62xx';
            }
            break;
        case 'email':
            if (patterns.email.test(value)) {
                isValid.value = true;
                validationMessage.value = '✓ Email valid';
            } else {
                isValid.value = false;
                validationMessage.value = 'Format email tidak valid';
            }
            break;
        case 'name':
            if (patterns.name.test(value) && value.length >= 2) {
                isValid.value = true;
                validationMessage.value = '';
            } else if (value.length < 2) {
                isValid.value = false;
                validationMessage.value = 'Minimal 2 karakter';
            } else {
                isValid.value = false;
                validationMessage.value = 'Hanya huruf dan spasi';
            }
            break;
        case 'currency':
        case 'number':
            isValid.value = value.length > 0;
            validationMessage.value = '';
            break;
        default:
            isValid.value = value.length > 0;
            validationMessage.value = '';
    }
};

// Block invalid keypress
const handleKeypress = (event) => {
    const char = event.key;
    
    // Allow control keys
    if (event.ctrlKey || event.metaKey || char.length > 1) {
        return;
    }
    
    const pattern = allowedChars[props.type];
    if (pattern && !pattern.test(char)) {
        event.preventDefault();
        // Visual feedback - shake animation
        inputRef.value?.classList.add('shake');
        setTimeout(() => {
            inputRef.value?.classList.remove('shake');
        }, 300);
    }
};

// Handle paste - filter invalid characters
const handlePaste = (event) => {
    event.preventDefault();
    
    let pastedText = (event.clipboardData || window.clipboardData).getData('text');
    
    // Filter based on type
    switch (props.type) {
        case 'nik':
            pastedText = pastedText.replace(/\D/g, '').slice(0, 16);
            break;
        case 'phone':
        case 'currency':
        case 'number':
            pastedText = pastedText.replace(/\D/g, '');
            break;
        case 'name':
            pastedText = pastedText.replace(/[^a-zA-Z\s'.,-]/g, '');
            break;
        case 'email':
            pastedText = pastedText.replace(/[^a-zA-Z0-9@._-]/g, '');
            break;
    }
    
    // Insert filtered text
    const input = event.target;
    const start = input.selectionStart;
    const end = input.selectionEnd;
    const currentValue = displayValue.value;
    const newValue = currentValue.substring(0, start) + pastedText + currentValue.substring(end);
    
    handleInput({ target: { value: newValue } });
};

// Handle input
const handleInput = (event) => {
    let rawValue = event.target.value;
    
    // Clean based on type
    switch (props.type) {
        case 'nik':
            rawValue = rawValue.replace(/\D/g, '').slice(0, 16);
            break;
        case 'phone':
            rawValue = rawValue.replace(/\D/g, '');
            break;
        case 'currency':
        case 'number':
            rawValue = rawValue.replace(/\D/g, '');
            break;
        case 'name':
            rawValue = rawValue.replace(/[^a-zA-Z\s'.,-]/g, '');
            break;
        case 'email':
            rawValue = rawValue.replace(/[^a-zA-Z0-9@._-]/g, '').toLowerCase();
            break;
    }
    
    const parsed = parseValue(rawValue);
    displayValue.value = formatValue(parsed) || rawValue;
    validateValue(parsed || rawValue);
    emit('update:modelValue', parsed || rawValue);
};

// Currency terbilang preview
const terbilangPreview = computed(() => {
    if (props.type !== 'currency' || !props.modelValue) return '';
    const num = parseInt(props.modelValue) || 0;
    return formatUtils.terbilang(num) + ' Rupiah';
});

// Get computed help text
const computedHelpText = computed(() => {
    return props.helpText || typeHelpText[props.type] || '';
});

// Initialize display value
watch(() => props.modelValue, (newVal) => {
    // Normalize Number to String
    const normalizedVal = typeof newVal === 'number' ? String(newVal) : newVal;
    displayValue.value = formatValue(normalizedVal) || normalizedVal;
    validateValue(normalizedVal);
}, { immediate: true });

// Max length for display (with formatting chars)
const displayMaxLength = computed(() => {
    if (props.type === 'nik') return 19; // 16 digits + 3 spaces
    return props.maxlength || undefined;
});

// Input type for mobile keyboards
const inputMode = computed(() => {
    switch (props.type) {
        case 'nik':
        case 'phone':
        case 'currency':
        case 'number':
            return 'numeric';
        case 'email':
            return 'email';
        default:
            return 'text';
    }
});

defineExpose({ focus: () => inputRef.value?.focus() });
</script>

<template>
    <div>
        <label v-if="label" class="block text-sm font-medium text-gray-700 mb-1">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>
        
        <div class="relative">
            <!-- Currency prefix -->
            <div v-if="type === 'currency'" class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="text-gray-500 font-medium">Rp</span>
            </div>
            
            <input
                ref="inputRef"
                type="text"
                :inputmode="inputMode"
                :value="displayValue"
                @input="handleInput"
                @keypress="handleKeypress"
                @paste="handlePaste"
                :placeholder="placeholder"
                :disabled="disabled"
                :maxlength="displayMaxLength"
                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-tactical-accent focus:ring-tactical-accent disabled:bg-gray-100 disabled:cursor-not-allowed transition-all pr-10"
                :class="[
                    error ? 'border-tactical-danger focus:border-tactical-danger focus:ring-tactical-danger' : '',
                    type === 'currency' ? 'pl-12' : '',
                    isValid === true && showValidIcon ? 'border-green-400 focus:border-green-500 focus:ring-green-500' : '',
                    isValid === false && showValidIcon ? 'border-amber-400' : ''
                ]"
            />
            
            <!-- Validation icon -->
            <div v-if="showValidIcon && modelValue" class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                <!-- Valid -->
                <svg v-if="isValid === true" class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <!-- Invalid -->
                <svg v-else-if="isValid === false" class="h-5 w-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
        </div>
        
        <!-- Terbilang for currency -->
        <p v-if="type === 'currency' && modelValue" class="mt-1 text-sm text-tactical-accent font-medium">
            {{ terbilangPreview }}
        </p>
        
        <!-- Validation message -->
        <div v-if="validationMessage && !error" class="mt-1 flex items-center gap-1">
            <span 
                class="text-xs"
                :class="isValid ? 'text-green-600' : 'text-amber-600'"
            >
                {{ validationMessage }}
            </span>
        </div>
        
        <!-- Help text -->
        <p v-else-if="computedHelpText && !error && !modelValue" class="mt-1 text-xs text-gray-400">
            {{ computedHelpText }}
        </p>
        
        <!-- Error message -->
        <p v-if="error" class="mt-1 text-sm text-tactical-danger flex items-center gap-1">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ error }}
        </p>
    </div>
</template>

<style scoped>
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-4px); }
    75% { transform: translateX(4px); }
}

.shake {
    animation: shake 0.3s ease-in-out;
    border-color: #ef4444 !important;
}
</style>
