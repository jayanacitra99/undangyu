import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import path from 'node:path';

/*
| Two bundles, never on the same page (docs/05 § 2):
|
|   dashboard.*  Bootstrap 5 + AdminLTE — admin panel and client dashboard
|   public.*     Tailwind — the published invitation
|
| Vue islands are extra entries under resources/js/islands/. Each one mounts a
| component into a single container div and is pulled in per page with
| @push('scripts') @vite('resources/js/islands/<name>.js') @endpush, e.g.
|
|   input: [..., 'resources/js/islands/invitation-builder.js']
|
| Island entries stay out of dashboard.js so a page only ships the widget it
| actually renders.
*/
export default defineConfig({
    resolve: {
        alias: {
            // docs/05 § 2 mounts islands as "@/components/builder/...".
            '@': path.resolve(import.meta.dirname, 'resources/js'),
        },
    },
    plugins: [
        laravel({
            input: [
                'resources/css/dashboard.css',
                'resources/js/dashboard.js',
                'resources/css/public.css',
                'resources/js/public.js',
                'resources/js/islands/sortable-rows.js',
                'resources/js/islands/feature-flags.js',
                'resources/js/islands/persons-editor.js',
                'resources/js/islands/event-sessions-editor.js',
                'resources/js/islands/gallery-editor.js',
            ],
            refresh: true,
        }),
        vue(),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
