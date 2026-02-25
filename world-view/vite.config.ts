import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    react(),
    tailwindcss(),
  ],
  base: '/MCAG_Militare-Civile-Archivio-Gestionale/public/world-view/dist/',
  build: {
    outDir: '../public/world-view/dist'
  }
})
