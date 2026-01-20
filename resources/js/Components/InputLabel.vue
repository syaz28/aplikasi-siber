<script setup>
defineProps({
    value: String,
    required: {
        type: Boolean,
        default: false
    }
});
</script>

<template>
    <label class="block text-sm font-medium text-gray-700">
        <slot v-if="!value" />
        <span v-else>
            {{ value }}
            <span v-if="required" class="text-red-500">*</span>
        </span>
    </label>
</template>
