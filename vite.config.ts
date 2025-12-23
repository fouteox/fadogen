import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';
import oxlintPlugin from 'vite-plugin-oxlint';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.tsx',
            ssr: 'resources/js/ssr.tsx',
            refresh: true,
        }),
        react(),
        tailwindcss(),
        oxlintPlugin(),
        wayfinder(),
    ],
    esbuild: {
        jsx: 'automatic',
    },
    ssr: {
        noExternal: true,
    },
});
