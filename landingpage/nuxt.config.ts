// https://nuxt.com/docs/api/configuration/nuxt-config

const SITE_URL = 'https://pemudalaundry.difarft.com'
const SITE_NAME = 'Pemuda Laundry | Laundry Bojonegoro'
const SITE_DESCRIPTION =
  'Pemuda Laundry melayani laundry kiloan, express, setrika, bedcover, dan boneka di Bojonegoro.'

export default defineNuxtConfig({
  compatibilityDate: '2026-11-12',

  devtools: { enabled: true },

  css: ['~/assets/css/main.css'],

  runtimeConfig: {
    public: {
      appName: 'Pemuda Laundry',
      appUrl: SITE_URL,
      apiBase: '',
    },
  },

  app: {
    head: {
      htmlAttrs: { lang: 'id' },
      title: SITE_NAME,
      link: [
        { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
        { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' },
        {
          rel: 'stylesheet',
          href: 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap',
        },
        { rel: 'icon', type: 'image/png', href: '/favicon.png' },
        { rel: 'canonical', href: SITE_URL },
      ],
      meta: [
        { name: 'description', content: SITE_DESCRIPTION },
        { name: 'robots', content: 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1' },
        { property: 'og:title', content: 'Pemuda Laundry | Laundry Bersih, Cepat, Wangi' },
        { property: 'og:description', content: 'Layanan laundry kiloan, express, setrika, bedcover, dan boneka di Bojonegoro.' },
        { property: 'og:type', content: 'website' },
        { property: 'og:url', content: SITE_URL },
        { property: 'og:image', content: `${SITE_URL}/og-image.png` },
        { property: 'og:image:alt', content: 'Pemuda Laundry Bojonegoro' },
        { name: 'twitter:card', content: 'summary_large_image' },
        { name: 'twitter:title', content: SITE_NAME },
        { name: 'twitter:description', content: 'Laundry bersih, cepat, dan wangi di Bojonegoro.' },
        { name: 'twitter:image', content: `${SITE_URL}/og-image.png` },
      ],
      script: [
        {
          type: 'application/ld+json',
          innerHTML: JSON.stringify({
            '@context': 'https://schema.org',
            '@type': 'Laundry',
            name: 'Pemuda Laundry',
            url: `${SITE_URL}/`,
            description: SITE_DESCRIPTION,
            image: `${SITE_URL}/og-image.png`,
            address: {
              '@type': 'PostalAddress',
              streetAddress: 'Jl. Pemuda Timur No. 8-2',
              addressLocality: 'Bojonegoro',
              addressRegion: 'Jawa Timur',
              addressCountry: 'ID',
            },
          }),
        },
      ],
    },
  },
})
