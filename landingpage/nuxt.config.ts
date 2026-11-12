// https://nuxt.com/docs/api/configuration/nuxt-config

export default defineNuxtConfig({
  compatibilityDate: '2026-11-12',

  devtools: { enabled: true },

  modules: [],

  runtimeConfig: {
    public: {
      appName: '',
      appUrl: '',
      apiBase: '',
    },
  },

  app: {
    head: {
      title: 'Laundry App',
      meta: [
        {
          name: 'description',
          content: 'Layanan laundry modern, praktis, dan terpercaya.',
        },
      ],
    },
  },
})
