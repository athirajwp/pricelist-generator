import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

const isVercel = process.env.VERCEL === '1' || process.env.VERCEL === 'true';

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  base: isVercel ? '/' : '/build/',
  build: {
    outDir: isVercel ? 'dist' : '../backend/public/build',
    emptyOutDir: true,
  },
  server: {
    allowedHosts: ['turbofan-depth-bruising.ngrok-free.dev'],
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:9000',
        changeOrigin: true,
      },
      '/uploads': {
        target: 'http://127.0.0.1:9000',
        changeOrigin: true,
      },
      '/images': {
        target: 'http://127.0.0.1:9000',
        changeOrigin: true,
      }
    }
  }
})
