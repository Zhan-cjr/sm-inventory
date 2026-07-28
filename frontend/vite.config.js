import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import { VitePWA } from 'vite-plugin-pwa'

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    react(),
    VitePWA({
      registerType: 'autoUpdate',
      includeAssets: ['favicon.ico', 'apple-touch-icon.png', 'masked-icon.svg'],
      manifest: {
        name: 'Toserba Selamat POS',
        short_name: 'Selamat POS',
        description: 'Aplikasi Kasir Toserba Selamat',
        theme_color: '#0f172a',
        background_color: '#0f172a',
        display: 'standalone',
        icons: [
          {
            src: 'pwa-192x192.png',
            sizes: '192x192',
            type: 'image/png'
          },
          {
            src: 'pwa-512x512.png',
            sizes: '512x512',
            type: 'image/png'
          },
          {
            src: 'pwa-512x512.png',
            sizes: '512x512',
            type: 'image/png',
            purpose: 'any maskable'
          }
        ]
      },
      workbox: {
        globPatterns: ['**/*.{js,css,html,ico,png,svg}'],
        navigateFallback: 'index.html',
        navigateFallbackAllowlist: [/^(?!\/__).*/],
        runtimeCaching: [
          {
            urlPattern: /^https?:\/\/.*\/api\/v1\/products/i,
            handler: 'NetworkFirst',
            options: {
              cacheName: 'pos-products-cache',
              expiration: {
                maxEntries: 10,
                maxAgeSeconds: 60 * 60 * 24 * 7 // 1 week
              },
              cacheableResponse: {
                statuses: [0, 200]
              }
            }
          }
        ]
      },
      devOptions: {
        enabled: true,
        type: 'module',
      }
    })
  ],
  build: {
    chunkSizeWarningLimit: 1000,
    rollupOptions: {
      output: {
        manualChunks(id) {
          if (id.includes('node_modules')) {
            if (id.includes('react') || id.includes('react-dom') || id.includes('react-router-dom')) {
              return 'vendor';
            }
            if (id.includes('lucide-react') || id.includes('recharts')) {
              return 'ui';
            }
            if (id.includes('html5-qrcode') || id.includes('laravel-echo') || id.includes('pusher-js')) {
              return 'utils';
            }
            return 'modules';
          }
        }
      }
    }
  },
  server: {
    port: 4173,
    allowedHosts: true,
    hmr: {
      clientPort: 443
    },
    proxy: {
      '/api': {
        target: process.env.VITE_BACKEND_URL || 'http://backend:8080',
        changeOrigin: true,
        secure: false,
      },
      '/app': {
        target: process.env.VITE_REVERB_SERVER_URL || 'ws://reverb:8085',
        ws: true,
        changeOrigin: true,
        secure: false,
      },
      '/storage': {
        target: process.env.VITE_BACKEND_URL || 'http://backend:8080',
        changeOrigin: true,
        secure: false,
      }
    }
  },
  preview: {
    port: 4173,
    allowedHosts: true,
    hmr: {
      clientPort: 443
    },
    proxy: {
      '/api': {
        target: process.env.VITE_BACKEND_URL || 'http://backend:8080',
        changeOrigin: true,
        secure: false,
      },
      '/app': {
        target: process.env.VITE_REVERB_SERVER_URL || 'ws://reverb:8085',
        ws: true,
        changeOrigin: true,
        secure: false,
      },
      '/storage': {
        target: process.env.VITE_BACKEND_URL || 'http://backend:8080',
        changeOrigin: true,
        secure: false,
      }
    }
  }
})
