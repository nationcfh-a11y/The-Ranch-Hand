import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

// Frontend on :5173. API calls to /api are proxied to the Express server on :3001,
// so the client code can just fetch('/api/...') with no CORS/host juggling.
export default defineConfig({
  plugins: [react()],
  server: {
    port: 5173,
    // Bind on all interfaces so localhost, 127.0.0.1, and your LAN IP all work
    // (avoids the IPv6-only "localhost won't open" issue some browsers hit).
    host: true,
    // Proxy /api to the Express server. Use 127.0.0.1 (not "localhost") so the
    // proxy never gets snagged on an IPv4/IPv6 resolution mismatch.
    proxy: {
      '/api': 'http://127.0.0.1:3001',
    },
  },
});
