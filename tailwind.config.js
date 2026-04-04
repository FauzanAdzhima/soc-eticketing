/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './vendor/livewire/flux/**/*.blade.php',
    ],
    theme: {
        extend: {
            colors: {
                background: 'rgb(var(--background) / <alpha-value>)',
                surface: 'rgb(var(--surface) / <alpha-value>)',
                border: 'rgb(var(--border) / <alpha-value>)',
                'border-strong': 'rgb(var(--border-strong) / <alpha-value>)',
                primary: {
                    DEFAULT: 'rgb(var(--primary) / <alpha-value>)',
                    foreground: 'rgb(var(--primary-foreground) / <alpha-value>)',
                    hover: 'rgb(var(--primary-hover) / <alpha-value>)',
                    active: 'rgb(var(--primary-active) / <alpha-value>)',
                },
                secondary: {
                    DEFAULT: 'rgb(var(--secondary) / <alpha-value>)',
                    foreground: 'rgb(var(--secondary-foreground) / <alpha-value>)',
                },
                accent: {
                    DEFAULT: 'rgb(var(--accent) / <alpha-value>)',
                    foreground: 'rgb(var(--accent-foreground) / <alpha-value>)',
                },
                'alt-primary': {
                    DEFAULT: 'rgb(var(--alt-primary) / <alpha-value>)',
                    foreground: 'rgb(var(--alt-primary-foreground) / <alpha-value>)',
                },
                'alt-secondary': {
                    DEFAULT: 'rgb(var(--alt-secondary) / <alpha-value>)',
                    foreground: 'rgb(var(--alt-secondary-foreground) / <alpha-value>)',
                },
                foreground: {
                    DEFAULT: 'rgb(var(--text-primary) / <alpha-value>)',
                    secondary: 'rgb(var(--text-secondary) / <alpha-value>)',
                },
                muted: {
                    DEFAULT: 'rgb(var(--muted) / <alpha-value>)',
                    foreground: 'rgb(var(--muted-foreground) / <alpha-value>)',
                },
                success: {
                    DEFAULT: 'rgb(var(--success) / <alpha-value>)',
                    foreground: 'rgb(var(--success-foreground) / <alpha-value>)',
                },
                warning: {
                    DEFAULT: 'rgb(var(--warning) / <alpha-value>)',
                    foreground: 'rgb(var(--warning-foreground) / <alpha-value>)',
                },
                danger: {
                    DEFAULT: 'rgb(var(--danger) / <alpha-value>)',
                    foreground: 'rgb(var(--danger-foreground) / <alpha-value>)',
                },
                info: {
                    DEFAULT: 'rgb(var(--info) / <alpha-value>)',
                    foreground: 'rgb(var(--info-foreground) / <alpha-value>)',
                },
                glass: 'rgb(var(--glass-bg) / <alpha-value>)',
                scrim: 'rgb(var(--scrim) / <alpha-value>)',
            },
        },
    },
    plugins: [],
};
