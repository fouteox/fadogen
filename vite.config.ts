import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite-plus';

export default defineConfig({
    fmt: {
        printWidth: 150,
        tabWidth: 4,
        useTabs: false,
        semi: true,
        singleQuote: true,
        ignorePatterns: ['**/*', '!resources/**', 'resources/js/actions/**', 'resources/js/routes/**', 'resources/js/wayfinder/**'],
        overrides: [{ files: ['**/*.yml'], options: { tabWidth: 2 } }],
        sortTailwindcss: {
            functions: ['clsx', 'cn'],
            stylesheet: 'resources/css/app.css',
        },
        sortImports: {
            groups: ['builtin', 'external', 'internal', 'parent', 'sibling', 'index'],
            newlinesBetween: false,
        },
    },
    lint: {
        plugins: ['react', 'typescript'],
        options: {
            typeAware: true,
            typeCheck: true,
        },
        rules: {
            'react/react-in-jsx-scope': 'off',
        },
        ignorePatterns: ['**/*', '!resources/**', 'resources/js/actions/**', 'resources/js/routes/**', 'resources/js/wayfinder/**'],
    },
    plugins: [
        laravel({
            input: 'resources/js/app.tsx',
            refresh: true,
        }),
        inertia(),
        react(),
        tailwindcss(),
        wayfinder(),
    ],
    ssr: {
        noExternal: true,
    },
});
