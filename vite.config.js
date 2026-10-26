import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { fontsource } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                fontsource('Be Vietnam Pro', {
                    weights: [400, 500, 600, 700],
                    subsets: ['latin', 'latin-ext', 'vietnamese'],
                    // Chỉ preload chữ thường và đậm (nội dung, tiêu đề); 500/600 tải khi cần
                    preload: [{ weight: 400 }, { weight: 700 }],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
