import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.jsx'],
            refresh: false,  // ← DISABLE Laravel's blade refresh
        }),
        react({
            fastRefresh: false,  // ← DISABLE React Fast Refresh
        }),
        tailwindcss(),
    ],
    resolve: {
        alias: { '@': '/resources/js' },
    },
    server: {
        host: 'localhost',
        port: 5173,
        strictPort: true,
        hmr: false,  // ← DISABLE Hot Module Replacement entirely
    },
});