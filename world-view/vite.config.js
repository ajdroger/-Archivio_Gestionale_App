import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'
import cesium from 'vite-plugin-cesium'

// https://vite.dev/config/
export default defineConfig({
    plugins: [
        react(),
        tailwindcss(),
        cesium()
    ],
    base: './',
    build: {
        outDir: '../public/world-view/dist'
    },
    server: {
        proxy: {
            '/api/opensky': {
                target: 'https://opensky-network.org/api',
                changeOrigin: true,
                rewrite: (path) => path.replace(/^\/api\/opensky/, '')
            },
            '/api/usgs': {
                target: 'https://earthquake.usgs.gov/earthquakes/feed/v1.0/summary',
                changeOrigin: true,
                rewrite: (path) => path.replace(/^\/api\/usgs/, '')
            }
        }
    },
    test: {
        environment: 'jsdom',
        globals: true,
        setupFiles: './src/setupTests.js',
    }
})
