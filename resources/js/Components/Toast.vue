<script setup>
import { ref, watch, onMounted } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    type: {
        type: String,
        default: 'success', // success, error, warning, info
        validator: (v) => ['success', 'error', 'warning', 'info'].includes(v)
    },
    message: {
        type: String,
        default: ''
    },
    duration: {
        type: Number,
        default: 4000 // ms
    },
    position: {
        type: String,
        default: 'top-right'
    }
});

const emit = defineEmits(['close']);

const isVisible = ref(false);
let timeoutId = null;

const icons = {
    success: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>`,
    error: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>`,
    warning: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>`,
    info: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`
};

const typeClasses = {
    success: 'bg-tactical-success text-white',
    error: 'bg-tactical-danger text-white',
    warning: 'bg-tactical-warning text-white',
    info: 'bg-tactical-accent text-white'
};

const close = () => {
    isVisible.value = false;
    setTimeout(() => emit('close'), 300);
};

const startTimer = () => {
    if (props.duration > 0) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(close, props.duration);
    }
};

watch(() => props.show, (newVal) => {
    if (newVal) {
        isVisible.value = true;
        startTimer();
    } else {
        isVisible.value = false;
    }
});

onMounted(() => {
    if (props.show) {
        isVisible.value = true;
        startTimer();
    }
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="translate-x-full opacity-0"
            enter-to-class="translate-x-0 opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="translate-x-0 opacity-100"
            leave-to-class="translate-x-full opacity-0"
        >
            <div
                v-if="isVisible"
                class="fixed top-4 right-4 z-[100] max-w-sm"
            >
                <div
                    class="flex items-center gap-3 px-4 py-3 rounded-lg shadow-lg"
                    :class="typeClasses[type]"
                >
                    <!-- Icon -->
                    <div class="flex-shrink-0" v-html="icons[type]"></div>

                    <!-- Message -->
                    <p class="flex-1 text-sm font-medium">{{ message }}</p>

                    <!-- Close Button -->
                    <button
                        @click="close"
                        class="flex-shrink-0 p-1 rounded-full hover:bg-white/20 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
