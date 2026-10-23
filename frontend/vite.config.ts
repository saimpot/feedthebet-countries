import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue(), tailwindcss()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    host: true,
    port: 5173,
    strictPort: true,
    // Requests arrive via nginx (localhost:8080), so accept any Host header in dev.
    allowedHosts: true,
    // The browser loads the page from nginx on :8080, so HMR must connect there.
    hmr: { clientPort: 8080 },
    watch: {
      // Bind mounts on Windows/macOS do not emit fs events reliably.
      usePolling: true,
    },
  },
})
