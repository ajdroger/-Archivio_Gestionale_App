import { defineConfig } from 'vite';
import path from 'path';
import purgecss from '@fullhuman/postcss-purgecss';

// PurgeCSS configuration for production builds
const purgeCssConfig = purgecss({
    content: [
        './templates/**/*.mustache',
        './public/**/*.html',
        './resources/js/**/*.js'
    ],
    defaultExtractor: content => content.match(/[\w-/:]+(?<!:)/g) || [],
    safelist: {
        standard: [
            // Bootstrap dynamic classes
            /^modal/,
            /^tooltip/,
            /^popover/,
            /^dropdown/,
            /^collapse/,
            /^show/,
            /^fade/,
            /^active/,
            /^disabled/,
            // DataTables classes
            /^dataTables/,
            /^dt-/,
            // Chart.js
            /^chart/,
            // Custom classes that may be added dynamically
            /^alert/,
            /^badge/,
            /^btn-/
        ],
        deep: [/^table/, /^pagination/]
    }
});

export default defineConfig({
    root: 'resources',
    build: {
        outDir: '../public/dist',
        emptyOutDir: true,
        manifest: true,
        rollupOptions: {
            input: {
                app: path.resolve(__dirname, 'resources/scss/app.scss'),
                main: path.resolve(__dirname, 'resources/js/main.js')
            },
            output: {
                assetFileNames: 'assets/[name].[ext]'
            }
        },
        minify: 'terser', // Explicit minification
        terserOptions: {
            compress: {
                drop_console: false, // DEBUG: Keep console logs
                drop_debugger: true
            }
        }
    },
    css: {
        postcss: {
            plugins: process.env.NODE_ENV === 'production' ? [purgeCssConfig] : []
        }
    },
    server: {
        strictPort: true,
        port: 5173,
        origin: 'http://localhost:5173'
    }
});

