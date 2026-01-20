<script setup>
import { useToast } from '@/Composables/useToast';
import Toast from './Toast.vue';

const { toasts, remove } = useToast();
</script>

<template>
    <div class="fixed top-4 right-4 z-[100] flex flex-col gap-2">
        <TransitionGroup
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="translate-x-full opacity-0"
            enter-to-class="translate-x-0 opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="translate-x-0 opacity-100"
            leave-to-class="translate-x-full opacity-0"
            move-class="transition-all duration-300"
        >
            <div
                v-for="toast in toasts"
                :key="toast.id"
                class="max-w-sm"
            >
                <div
                    class="flex items-center gap-3 px-4 py-3 rounded-lg shadow-lg"
                    :class="{
                        'bg-tactical-success text-white': toast.type === 'success',
                        'bg-tactical-danger text-white': toast.type === 'error',
                        'bg-tactical-warning text-white': toast.type === 'warning',
                        'bg-tactical-accent text-white': toast.type === 'info'
                    }"
                >
                    <!-- Success Icon -->
                    <svg v-if="toast.type === 'success'" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <!-- Error Icon -->
                    <svg v-else-if="toast.type === 'error'" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    <!-- Warning Icon -->
                    <svg v-else-if="toast.type === 'warning'" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <!-- Info Icon -->
                    <svg v-else class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>

                    <!-- Message -->
                    <p class="flex-1 text-sm font-medium">{{ toast.message }}</p>

                    <!-- Close Button -->
                    <button
                        @click="remove(toast.id)"
                        class="flex-shrink-0 p-1 rounded-full hover:bg-white/20 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </TransitionGroup>
    </div>
</template>
