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
      path: '/orders',
      name: 'Orders',
      component: () => import('../views/Orders/Index.vue'),
      meta: {
        title: 'Pesanan',
        permission: 'orders.view',
      },
    },
    {
      path: '/orders/:id',
      name: 'OrderDetail',
      component: () => import('../views/Orders/Show.vue'),
      meta: {
        title: 'Detail Pesanan',
        permission: 'orders.view',
      },
    },
    {
      path: '/payments',
      name: 'Payments',
      component: () => import('../views/Payments/Index.vue'),
      meta: {
        title: 'Riwayat Pembayaran',
        permission: 'payments.view',
      },
    },
    {
      path: '/pickup-delivery',
      name: 'PickupDelivery',
      component: () => import('../views/Courier/Index.vue'),
      meta: {
        title: 'Antar-Jemput',
        permission: 'delivery.view',
      },
    },
    {
      path: '/pickup-delivery/:id',
      name: 'PickupDeliveryDetail',
      component: () => import('../views/Courier/Show.vue'),
      meta: {
        title: 'Detail Antar-Jemput',
        permission: 'delivery.view',
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
      path: '/reports',
      name: 'Reports',
      component: () => import('../views/Reports/Index.vue'),
      meta: {
        title: 'Laporan & Histori',
        permission: 'reports.view',
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
      path: '/signin',
      name: 'Signin',
      component: () => import('../views/Auth/Signin.vue'),
      meta: {
        title: 'Masuk',
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
