import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import react from '@vitejs/plugin-react';
import inertia from '@inertiajs/vite';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.jsx'],
            refresh: true,
            fonts: [
                bunny('Outfit', {
                    weights: [500, 600, 700, 800],
                }),
                bunny('Fraunces', {
                    weights: [600, 700],
                }),
                bunny('DM Sans', {
                    weights: [400, 500, 600, 700],
                }),
            ],
        }),
        react(),
        inertia(),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
