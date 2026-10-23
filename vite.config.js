import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

// Fonts are downloaded at build time and served from /build, so visitors never hit a font CDN.
// Two families. Newsreader (display) is a calm, bookish serif: it gives headings the
// trust of print without feeling clinical. Atkinson Hyperlegible Next (body) was drawn for the
// Braille Institute so that easily-confused characters (I l 1, O 0, rn m) stay distinct for
// low-vision readers. On a hospital site, where older patients read phone numbers, dates and
// reference codes, that legibility matters more than personality.
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Newsreader', {
                    alias: 'display',
                    variable: '--ff-display',
                    weights: [500, 600],
                    styles: ['normal', 'italic'],
                    subsets: ['latin'],
                    preload: [{ weight: 500 }],
                    fallbacks: ['Georgia', 'Cambria', 'Times New Roman', 'serif'],
                }),
                bunny('Atkinson Hyperlegible Next', {
                    alias: 'sans',
                    variable: '--ff-sans',
                    weights: [400, 600, 700],
                    subsets: ['latin'],
                    preload: [{ weight: 400 }, { weight: 700 }],
                    fallbacks: ['ui-sans-serif', 'system-ui', 'Segoe UI', 'sans-serif'],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: { watch: { ignored: ['**/storage/framework/views/**'] } },
});
