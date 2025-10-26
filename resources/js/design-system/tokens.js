export const palette = {
    light: {
        surface: '#ffffff',
        surfaceSubtle: '#f5f7ff',
        border: 'rgba(79, 70, 229, 0.12)',
        text: '#1f2937',
        textSubtle: '#4b5563',
    },
    dark: {
        surface: '#0f172a',
        surfaceSubtle: '#111827',
        border: 'rgba(59, 130, 246, 0.35)',
        text: '#f8fafc',
        textSubtle: '#cbd5f5',
    },
};

export const gradients = {
    primary: 'linear-gradient(135deg, rgba(79,70,229,0.95), rgba(6,182,212,0.9))',
};

export const formatCurrency = (value, currency = 'USD', locale = 'en-US') => {
    if (value === null || value === undefined || Number.isNaN(value)) {
        return '--';
    }
    if (!currency || currency === 'count') {
        return Number(value).toLocaleString(locale, { maximumFractionDigits: 2 });
    }
    try {
        return new Intl.NumberFormat(locale, {
            style: 'currency',
            currency,
            maximumFractionDigits: 2,
        }).format(Number(value));
    } catch (error) {
        return `${Number(value).toFixed(2)} ${currency}`;
    }
};

export const designSystem = {
    palette,
    gradients,
    formatCurrency,
};

export default designSystem;
