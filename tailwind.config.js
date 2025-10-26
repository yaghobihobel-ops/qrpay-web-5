import defaultTheme from 'tailwindcss/defaultTheme';

export default {
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.jsx',
        './resources/js/**/*.js',
    ],
    darkMode: ['class', '[data-theme="dark"]'],
    theme: {
        extend: {
            fontFamily: {
                sans: ['"Inter"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    DEFAULT: '#4f46e5',
                    50: '#eef2ff',
                    100: '#e0e7ff',
                    500: '#4f46e5',
                    600: '#4338ca',
                    700: '#3730a3',
                },
                accent: {
                    DEFAULT: '#06b6d4',
                    100: '#cffafe',
                    500: '#06b6d4',
                    600: '#0891b2',
                },
                success: '#16a34a',
                warning: '#f59e0b',
                danger: '#ef4444',
            },
            boxShadow: {
                'soft-xl': '0 20px 45px -15px rgba(79, 70, 229, 0.25)',
            },
        },
    },
    plugins: [],
};
