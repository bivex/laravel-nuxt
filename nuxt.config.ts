/**
 * Copyright (c) 2025 Bivex
 *
 * Author: Bivex
 * Available for contact via email: support@b-b.top
 * For up-to-date contact information:
 * https://github.com/bivex
 *
 * Created: 2025-12-25T11:37:28
 * Last Updated: 2025-12-25T11:39:05
 *
 * Licensed under the MIT License.
 * Commercial licensing available upon request.
 */

// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-19',
  srcDir: 'nuxt/',

  dir: {
    public: 'public/nuxt',
  },

  vite: {
    server: {
      allowedHosts: ["localhost", "127.0.0.1"],
      https: false,
      hmr: {
        protocol: 'ws',
        host: 'localhost',
        port: 3001,
      },
    },
  },

  /**
   * Manually disable nuxt telemetry.
   * @see [Nuxt Telemetry](https://github.com/nuxt/telemetry) for more information.
   */
  telemetry: true,

  $development: {
    ssr: true,
    devtools: {
      enabled: false,
    },
  },

  $production: {
    ssr: true,
  },

  app: {
    head: {
      title: 'Home',
      titleTemplate: '%s | LaravelNuxt Boilerplate',
      meta: [
        { charset: 'utf-8' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1' },
        // Disable caching in development
        { 'http-equiv': 'Cache-Control', content: 'no-cache, no-store, must-revalidate' },
        { 'http-equiv': 'Pragma', content: 'no-cache' },
        { 'http-equiv': 'Expires', content: '0' },
      ].filter(Boolean),
      link: [
        { rel: 'icon', type: 'image/x-icon', href: '/favicon.ico' },
      ],
    },
  },

  routeRules: {
    'auth/verify': { ssr: false }
  },

  css: ['~/assets/css/main.css'],

  /**
   * @see https://v3.nuxtjs.org/api/configuration/nuxt.config#modules
   */
  modules: [
    '@nuxt/ui',
    '@nuxt/image',
    '@pinia/nuxt',
    'dayjs-nuxt',
    'nuxt-security',
  ],

  image: {
    domains: [
      import.meta.env.APP_URL || 'http://127.0.0.1:8000'
    ],
    alias: {
      api: import.meta.env.APP_URL || 'http://127.0.0.1:8000'
    }
  },

  security: {
    headers: {
      crossOriginEmbedderPolicy: 'unsafe-none',
      crossOriginOpenerPolicy: 'same-origin-allow-popups',
      // Prevent HTTPS redirects in development
      contentSecurityPolicy: {
        "default-src": ["'self'"],
        "img-src": ["'self'", "data:", "blob:", "http://localhost:3000", "http://localhost:8000", import.meta.env.APP_URL || 'http://127.0.0.1:8000'],
        "connect-src": ["'self'", "ws://localhost:3001", "http://localhost:3000", "http://localhost:8000", import.meta.env.APP_URL || 'http://127.0.0.1:8000'],
        "script-src": ["'self'", "'unsafe-inline'", "'unsafe-eval'", "http://localhost:3000"],
        "style-src": ["'self'", "'unsafe-inline'", "http://localhost:3000"],
        "font-src": ["'self'", "http://localhost:3000"],
        "upgrade-insecure-requests": null, // Disable upgrade to HTTPS
      },
    },
  },

  dayjs: {
    locales: ['en'],
    plugins: ['relativeTime', 'utc', 'timezone'],
    defaultLocale: 'en',
    defaultTimezone: import.meta.env.APP_TIMEZONE,
  },

  typescript: {
    strict: false,
  },

  /**
   * @see https://v3.nuxtjs.org/guide/features/runtime-config#exposing-runtime-config
   */
  runtimeConfig: {
    apiLocal: import.meta.env.API_LOCAL_URL,
    public: {
      authGuard: import.meta.env.AUTH_GUARD,
      apiBase: import.meta.env.APP_URL,
      apiPrefix: '/api/v1',
      storageBase: import.meta.env.APP_URL + '/storage/',
      providers: {
        google: {
          name: "Google",
          icon: "",
          color: "neutral",
          variant: "soft",
        },
      },
    },
  },
})
