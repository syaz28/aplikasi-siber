<script setup>
import { ref } from 'vue';

const model = defineModel({
    type: String,
    default: ''
});

defineProps({
    label: String,
    type: {
        type: String,
        default: 'text'
    },
    error: String,
    required: {
        type: Boolean,
        default: false
    },
    placeholder: String,
    maxlength: [String, Number]
});

const input = ref(null);
defineExpose({ focus: () => input.value?.focus() });
</script>

<template>
    <div>
        <label v-if="label" class="block text-sm font-medium text-gray-700 mb-1">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>
        
        <div class="relative">
            <input
                ref="input"
                v-model="model"
                :type="type"
                :placeholder="placeholder"
                :maxlength="maxlength"
                class="block w-full rounded-lg border-gray-300 focus:border-tactical-accent focus:ring-tactical-accent"
                :class="{ 'border-tactical-danger focus:border-tactical-danger focus:ring-tactical-danger': error }"
            />
        </div>
        
        <p v-if="error" class="mt-1 text-sm text-tactical-danger">
            {{ error }}
        </p>
        
        <p class="mt-1 text-xs text-gray-400">
            Data ini akan disimpan secara aman.
        </p>
    </div>
</template>
