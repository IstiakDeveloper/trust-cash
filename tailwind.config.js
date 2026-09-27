import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'selector',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            backdropBlur: {
                sm: '4px',
            },
            fontFamily: {
                sans: ['Inter', 'Noto Sans Bengali', 'Hind Siliguri', ...defaultTheme.fontFamily.sans],
                bangla: ['Noto Sans Bengali', 'Hind Siliguri', ...defaultTheme.fontFamily.sans],
            },

        },
    },

    plugins: [
        forms,
        require('@tailwindcss/forms'),
    ],
    variants: {
        extend: {
            display: ['print']
        }
    }
};
