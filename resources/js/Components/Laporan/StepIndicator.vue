<script setup>
defineProps({
    currentStep: {
        type: Number,
        required: true
    }
});

const emit = defineEmits(['stepClick']);

const steps = [
    { id: 0, name: 'Administrasi', description: 'Nomor STPA & Petugas' },
    { id: 1, name: 'Data Pelapor', description: 'Identitas, Kontak & Alamat' },
    { id: 2, name: 'Laporan Kejadian', description: 'Detail & Data Korban' },
    { id: 3, name: 'Tersangka & Modus', description: 'Identitas Digital & Kronologi' },
];

const handleStepClick = (stepId) => {
    emit('stepClick', stepId);
};
</script>

<template>
    <div class="bg-white rounded-xl shadow-tactical border border-tactical-border p-6 mb-6">
        <div class="flex items-center justify-between">
            <template v-for="(step, index) in steps" :key="step.id">
                <!-- Step Circle -->
                <div class="flex flex-col items-center">
                    <button
                        @click="handleStepClick(step.id)"
                        class="w-12 h-12 rounded-full flex items-center justify-center font-bold text-lg transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-tactical-accent"
                        :class="{
                            'bg-tactical-accent text-white shadow-lg': currentStep === step.id,
                            'bg-tactical-success text-white': currentStep > step.id,
                            'bg-gray-200 text-gray-500 hover:bg-gray-300': currentStep < step.id
                        }"
                    >
                        <!-- Checkmark for completed -->
                        <svg v-if="currentStep > step.id" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span v-else>{{ step.id + 1 }}</span>
                    </button>
                    
                    <!-- Step Label -->
                    <div class="mt-2 text-center">
                        <div
                            class="text-sm font-semibold transition-colors"
                            :class="{
                                'text-tactical-accent': currentStep === step.id,
                                'text-tactical-success': currentStep > step.id,
                                'text-gray-500': currentStep < step.id
                            }"
                        >
                            {{ step.name }}
                        </div>
                        <div class="text-xs text-gray-400 hidden md:block">
                            {{ step.description }}
                        </div>
                    </div>
                </div>

                <!-- Connector Line -->
                <div
                    v-if="index < steps.length - 1"
                    class="flex-1 h-1 mx-4 rounded transition-colors duration-300"
                    :class="currentStep > step.id ? 'bg-tactical-success' : 'bg-gray-200'"
                />
            </template>
        </div>
    </div>
</template>
