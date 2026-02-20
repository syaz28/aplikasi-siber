import { ref, watch, onMounted } from 'vue';

const STORAGE_PREFIX = 'siber_jateng_';
const DRAFT_KEY = STORAGE_PREFIX + 'laporan_draft';
const SETTINGS_KEY = STORAGE_PREFIX + 'settings';

/**
 * Composable for form auto-save and settings storage
 */
export function useFormStorage() {
    // ========================================
    // SETTINGS (Petugas default, etc)
    // ========================================
    
    const getSettings = () => {
        try {
            const saved = localStorage.getItem(SETTINGS_KEY);
            return saved ? JSON.parse(saved) : {};
        } catch {
            return {};
        }
    };

    const saveSettings = (settings) => {
        try {
            const current = getSettings();
            localStorage.setItem(SETTINGS_KEY, JSON.stringify({ ...current, ...settings }));
        } catch (e) {
            console.error('Failed to save settings:', e);
        }
    };

    // Default Petugas
    const getDefaultPetugas = () => {
        return getSettings().default_petugas_id || null;
    };

    const saveDefaultPetugas = (petugasId) => {
        saveSettings({ default_petugas_id: petugasId });
    };

    // ========================================
    // DRAFT AUTO-SAVE
    // ========================================
    
    /**
     * Save draft with form data and optional extra state
     * @param {object} formData - Main form data
     * @param {object} extraState - Optional extra state (e.g., textExtractState)
     */
    const saveDraft = (formData, extraState = null) => {
        try {
            const draft = {
                data: formData,
                extraState: extraState,
                savedAt: new Date().toISOString()
            };
            localStorage.setItem(DRAFT_KEY, JSON.stringify(draft));
        } catch (e) {
            console.error('Failed to save draft:', e);
        }
    };

    const loadDraft = () => {
        try {
            const saved = localStorage.getItem(DRAFT_KEY);
            if (!saved) return null;
            
            const draft = JSON.parse(saved);
            // Check if draft is older than 24 hours
            const savedAt = new Date(draft.savedAt);
            const now = new Date();
            const hoursDiff = (now - savedAt) / (1000 * 60 * 60);
            
            if (hoursDiff > 24) {
                clearDraft();
                return null;
            }
            
            return draft;
        } catch {
            return null;
        }
    };

    const clearDraft = () => {
        try {
            localStorage.removeItem(DRAFT_KEY);
        } catch (e) {
            console.error('Failed to clear draft:', e);
        }
    };

    const hasDraft = () => {
        return loadDraft() !== null;
    };

    // ========================================
    // AUTO-SAVE SETUP
    // ========================================
    
    let autoSaveInterval = null;

    /**
     * Start auto-save with optional extra state
     * @param {object} formData - Main form data
     * @param {number} intervalMs - Interval in milliseconds
     * @param {function} getExtraState - Optional function to get extra state
     */
    const startAutoSave = (formData, intervalMs = 30000, getExtraState = null) => {
        stopAutoSave();
        autoSaveInterval = setInterval(() => {
            const extraState = getExtraState ? getExtraState() : null;
            saveDraft(formData, extraState);
        }, intervalMs);
    };

    const stopAutoSave = () => {
        if (autoSaveInterval) {
            clearInterval(autoSaveInterval);
            autoSaveInterval = null;
        }
    };

    return {
        // Settings
        getSettings,
        saveSettings,
        getDefaultPetugas,
        saveDefaultPetugas,
        
        // Draft
        saveDraft,
        loadDraft,
        clearDraft,
        hasDraft,
        
        // Auto-save
        startAutoSave,
        stopAutoSave
    };
}

/**
 * Format utilities
 */
export const formatUtils = {
    // Format NIK with spaces: 1234 5678 9012 3456
    formatNik: (nik) => {
        if (!nik) return '';
        const clean = nik.replace(/\D/g, '').slice(0, 16);
        return clean.replace(/(\d{4})(?=\d)/g, '$1 ').trim();
    },

    // Parse NIK (remove spaces)
    parseNik: (formatted) => {
        return formatted ? formatted.replace(/\s/g, '') : '';
    },

    // Format phone: 0812-3456-7890
    formatPhone: (phone) => {
        if (!phone) return '';
        const clean = phone.replace(/\D/g, '');
        if (clean.length <= 4) return clean;
        if (clean.length <= 8) return clean.replace(/(\d{4})(\d+)/, '$1-$2');
        return clean.replace(/(\d{4})(\d{4})(\d+)/, '$1-$2-$3');
    },

    // Parse phone (remove dashes)
    parsePhone: (formatted) => {
        return formatted ? formatted.replace(/-/g, '') : '';
    },

    // Format currency: Rp 50.000.000
    formatCurrency: (amount) => {
        if (!amount && amount !== 0) return '';
        const num = parseInt(String(amount).replace(/\D/g, '')) || 0;
        return 'Rp ' + num.toLocaleString('id-ID');
    },

    // Parse currency (get number only)
    parseCurrency: (formatted) => {
        return parseInt(String(formatted).replace(/\D/g, '')) || 0;
    },

    // Number to words (Terbilang)
    terbilang: (num) => {
        const angka = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];
        
        if (num === 0) return 'Nol';
        if (num < 12) return angka[num];
        if (num < 20) return formatUtils.terbilang(num - 10) + ' Belas';
        if (num < 100) return formatUtils.terbilang(Math.floor(num / 10)) + ' Puluh' + (num % 10 ? ' ' + formatUtils.terbilang(num % 10) : '');
        if (num < 200) return 'Seratus' + (num % 100 ? ' ' + formatUtils.terbilang(num % 100) : '');
        if (num < 1000) return formatUtils.terbilang(Math.floor(num / 100)) + ' Ratus' + (num % 100 ? ' ' + formatUtils.terbilang(num % 100) : '');
        if (num < 2000) return 'Seribu' + (num % 1000 ? ' ' + formatUtils.terbilang(num % 1000) : '');
        if (num < 1000000) return formatUtils.terbilang(Math.floor(num / 1000)) + ' Ribu' + (num % 1000 ? ' ' + formatUtils.terbilang(num % 1000) : '');
        if (num < 1000000000) return formatUtils.terbilang(Math.floor(num / 1000000)) + ' Juta' + (num % 1000000 ? ' ' + formatUtils.terbilang(num % 1000000) : '');
        if (num < 1000000000000) return formatUtils.terbilang(Math.floor(num / 1000000000)) + ' Miliar' + (num % 1000000000 ? ' ' + formatUtils.terbilang(num % 1000000000) : '');
        return formatUtils.terbilang(Math.floor(num / 1000000000000)) + ' Triliun' + (num % 1000000000000 ? ' ' + formatUtils.terbilang(num % 1000000000000) : '');
    }
};
