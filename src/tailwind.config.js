import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import flowbite from 'flowbite/plugin';
import colors from 'tailwindcss/colors';
import fs from 'node:fs';
import path from 'node:path';

const themeConfigPath = path.resolve('config/world-cup-themes.php');
const themeConfigSource = fs.existsSync(themeConfigPath)
    ? fs.readFileSync(themeConfigPath, 'utf8')
    : '';

const themeClassMatches = themeConfigSource.matchAll(/'([a-zA-Z]+Class)'\s*=>\s*'([^']*)'/g);
const themeClassSafelist = Array.from(themeClassMatches)
    .map(([, , classValue]) => classValue.trim())
    .filter(Boolean)
    .flatMap((classValue) => classValue.split(/\s+/))
    .filter(Boolean);

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    safelist: Array.from(new Set(themeClassSafelist)),
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './config/**/*.php',
        './node_modules/flowbite/**/*.js',
        './node_modules/flowbite-datepicker/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                body: [
                    'Inter',
                    'ui-sans-serif',
                    'system-ui',
                    '-apple-system',
                    'Segoe UI',
                    'Roboto',
                    'Helvetica Neue',
                    'Arial',
                    'Noto Sans',
                    'sans-serif',
                    'Apple Color Emoji',
                    'Segoe UI Emoji',
                    'Segoe UI Symbol',
                    'Noto Color Emoji',
                ],
                sans: [
                    'Inter',
                    'ui-sans-serif',
                    'system-ui',
                    '-apple-system',
                    'Segoe UI',
                    'Roboto',
                    'Helvetica Neue',
                    'Arial',
                    'Noto Sans',
                    'sans-serif',
                    'Apple Color Emoji',
                    'Segoe UI Emoji',
                    'Segoe UI Symbol',
                    'Noto Color Emoji',
                ],
            },
            colors: {
                primary: colors.blue,
                brand: colors.blue[600],
                heading: colors.slate[900],
                body: colors.slate[600],
                fg: {
                    disabled: colors.slate[400],
                    brand: colors.blue[700],
                },
                neutral: {
                    primary: {
                        medium: colors.white,
                    },
                    secondary: {
                        medium: colors.slate[50],
                    },
                    tertiary: {
                        medium: colors.slate[100],
                    },
                },
                default: {
                    DEFAULT: colors.slate[300],
                    medium: colors.slate[300],
                },
            },
            borderRadius: {
                base: '0.5rem',
            },
            boxShadow: {
                xs: '0 1px 2px 0 rgb(15 23 42 / 0.05)',
            },
        },
    },

    plugins: [forms, flowbite],
};
