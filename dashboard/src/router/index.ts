import { createRouter, createWebHistory } from 'vue-router'
import { pinia } from '@/stores'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  scrollBehavior(to, from, savedPosition) {
    return savedPosition || { left: 0, top: 0 }
  },
  routes: [
    {
      path: '/',
      name: 'Dashboard',
      component: () => import('../views/Dashboard/Laundry.vue'),
      meta: {
        title: 'Dashboard',
        permission: 'dashboard.view',
      },
    },
    {
      path: '/users',
      name: 'Users',
      component: () => import('../views/Users/Index.vue'),
      meta: {
        title: 'Pengguna',
        permission: 'users.view',
      },
    },
    {
      path: '/services',
      name: 'Services',
      component: () => import('../views/Services/Index.vue'),
      meta: {
        title: 'Layanan',
        permission: 'services.view',
      },
    },
    {
      path: '/service-times',
      name: 'ServiceTimes',
      component: () => import('../views/ServiceTimes/Index.vue'),
      meta: {
        title: 'Waktu Layanan',
        permission: 'service-times.view',
      },
    },
    {
      path: '/payment-methods',
      name: 'PaymentMethods',
      component: () => import('../views/PaymentMethods/Index.vue'),
      meta: {
        title: 'Metode Pembayaran',
        permission: 'payment-methods.view',
      },
    },
    {
      path: '/account',
      name: 'Account',
      component: () => import('../views/Account.vue'),
      meta: {
        title: 'Akun Saya',
      },
    },
    {
      path: '/demo/ecommerce',
      name: 'Ecommerce',
      component: () => import('../views/Dashboard/Ecommerce.vue'),
      meta: {
        title: 'eCommerce Dashboard',
      },
    },
    {
      path: '/calendar',
      name: 'Calendar',
      component: () => import('../views/Calendar.vue'),
      meta: {
        title: 'Calendar',
      },
    },
    {
      path: '/profile',
      name: 'Profile',
      component: () => import('../views/Profile.vue'),
      meta: {
        title: 'Profile',
      },
    },
    {
      path: '/form-elements',
      name: 'Form Elements',
      component: () => import('../views/Forms/FormElements.vue'),
      meta: {
        title: 'Form Elements',
      },
    },
    {
      path: '/basic-tables',
      name: 'Basic Tables',
      component: () => import('../views/Tables/BasicTables.vue'),
      meta: {
        title: 'Basic Tables',
      },
    },
    {
      path: '/line-chart',
      name: 'Line Chart',
      component: () => import('../views/Charts/LineChart.vue'),
    },
    {
      path: '/bar-chart',
      name: 'Bar Chart',
      component: () => import('../views/Charts/BarChart.vue'),
    },
    {
      path: '/alerts',
      name: 'Alerts',
      component: () => import('../views/UiElements/Alerts.vue'),
      meta: {
        title: 'Alerts',
      },
    },
    {
      path: '/avatars',
      name: 'Avatars',
      component: () => import('../views/UiElements/Avatars.vue'),
      meta: {
        title: 'Avatars',
      },
    },
    {
      path: '/badge',
      name: 'Badge',
      component: () => import('../views/UiElements/Badge.vue'),
      meta: {
        title: 'Badge',
      },
    },

    {
      path: '/buttons',
      name: 'Buttons',
      component: () => import('../views/UiElements/Buttons.vue'),
      meta: {
        title: 'Buttons',
      },
    },

    {
      path: '/images',
      name: 'Images',
      component: () => import('../views/UiElements/Images.vue'),
      meta: {
        title: 'Images',
      },
    },
    {
      path: '/videos',
      name: 'Videos',
      component: () => import('../views/UiElements/Videos.vue'),
      meta: {
        title: 'Videos',
      },
    },
    {
      path: '/blank',
      name: 'Blank',
      component: () => import('../views/Pages/BlankPage.vue'),
      meta: {
        title: 'Blank',
      },
    },

    {
      path: '/signin',
      name: 'Signin',
      component: () => import('../views/Auth/Signin.vue'),
      meta: {
        title: 'Masuk',
        public: true,
      },
    },
    {
      path: '/signup',
      name: 'Signup',
      component: () => import('../views/Auth/Signup.vue'),
      meta: {
        title: 'Daftar',
        public: true,
      },
    },
    {
      path: '/forbidden',
      name: 'Forbidden',
      component: () => import('../views/Errors/Forbidden.vue'),
      meta: {
        title: 'Akses Ditolak',
      },
    },
    {
      path: '/error-404',
      name: '404 Error',
      component: () => import('../views/Errors/FourZeroFour.vue'),
      meta: {
        title: '404 Error',
        public: true,
      },
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'NotFound',
      component: () => import('../views/Errors/FourZeroFour.vue'),
      meta: {
        title: '404 Error',
        public: true,
      },
    },
  ],
})

router.beforeEach(async (to) => {
  document.title = to.meta.title ? `${to.meta.title} | Pemuda Laundry` : 'Pemuda Laundry'

  const auth = useAuthStore(pinia)

  if (to.meta.public) {
    // Sudah login lalu membuka halaman masuk: alihkan ke dashboard.
    if (to.name === 'Signin' && auth.token) {
      await auth.ensureLoaded()

      if (auth.isAuthenticated) {
        return { name: 'Dashboard' }
      }
    }

    return true
  }

  if (!auth.token) {
    return { name: 'Signin', query: { redirect: to.fullPath } }
  }

  await auth.ensureLoaded()

  if (!auth.isAuthenticated) {
    return { name: 'Signin', query: { redirect: to.fullPath } }
  }

  if (to.meta.permission && !auth.hasPermission(to.meta.permission)) {
    return { name: 'Forbidden' }
  }

  return true
})

export default router
