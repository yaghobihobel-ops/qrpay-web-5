import { palette } from '../tokens';

export const applyTheme = (theme) => {
    const targetTheme = palette[theme] ? theme : 'light';
    document.documentElement.setAttribute('data-theme', targetTheme);
    document.body.classList.add('ds-ready');
};

export const hydrateThemeFromPreference = (preference) => {
    if (!preference || preference === 'system') {
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        applyTheme(prefersDark ? 'dark' : 'light');
        return prefersDark ? 'dark' : 'light';
    }
    applyTheme(preference);
    return preference;
};

export default {
    applyTheme,
    hydrateThemeFromPreference,
};
