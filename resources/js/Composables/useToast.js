import { ref, reactive } from 'vue';

// Global toast state
const toasts = reactive([]);
let toastId = 0;

export function useToast() {
    const show = (message, type = 'success', duration = 4000) => {
        const id = ++toastId;
        toasts.push({ id, message, type, duration, show: true });

        // Auto remove after duration + animation time
        setTimeout(() => {
            remove(id);
        }, duration + 500);

        return id;
    };

    const success = (message, duration = 4000) => show(message, 'success', duration);
    const error = (message, duration = 5000) => show(message, 'error', duration);
    const warning = (message, duration = 5000) => show(message, 'warning', duration);
    const info = (message, duration = 4000) => show(message, 'info', duration);
    
    // Smart success - detects warning emoji and shows as warning type with longer duration
    const smartSuccess = (message, duration = 4000) => {
        if (message.includes('⚠️') || message.includes('PERINGATAN')) {
            return show(message, 'warning', 6000); // Longer duration for warnings
        }
        return show(message, 'success', duration);
    };

    const remove = (id) => {
        const index = toasts.findIndex(t => t.id === id);
        if (index > -1) {
            toasts.splice(index, 1);
        }
    };

    return {
        toasts,
        show,
        success,
        error,
        warning,
        info,
        smartSuccess,
        remove
    };
}
