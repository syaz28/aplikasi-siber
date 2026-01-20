<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

defineProps({
    type: {
        type: String,
        default: 'text',
    },
    placeholder: String,
    disabled: Boolean,
    autofocus: Boolean,
});

const input = ref(null);

onMounted(() => {
    if (input.value?.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        ref="input"
        v-model="model"
        :type="type"
        :placeholder="placeholder"
        :disabled="disabled"
        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-tactical-accent focus:ring-tactical-accent disabled:bg-gray-100 disabled:cursor-not-allowed"
    />
</template>
