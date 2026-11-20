import {
  BarChartIcon,
  BoxIcon,
  CartIcon,
  ClockIcon,
  DollarIcon,
  LayoutGridIcon,
  TagIcon,
  TruckDeliveryIcon,
  UserCircleIcon,
  UsersIcon,
} from '@/icons/layout'
import type { NavItem } from './types'

export const navItems: NavItem[] = [
  {
    icon: LayoutGridIcon,
    name: 'Dashboard',
    path: '/',
  },
  {
    icon: CartIcon,
    name: 'Pesanan',
    path: '/orders',
    permission: 'orders.view',
  },
  {
    icon: UsersIcon,
    name: 'Pelanggan',
    path: '/customers',
    permission: 'orders.view',
    disabled: true,
  },
  {
    icon: BoxIcon,
    name: 'Proses Laundry',
    path: '/laundry-process',
    permission: 'laundry.view',
    disabled: true,
  },
  {
    icon: TruckDeliveryIcon,
    name: 'Antar-Jemput',
    path: '/pickup-delivery',
    permission: 'delivery.view',
    disabled: true,
  },
]

export const othersItems: NavItem[] = [
  {
    icon: TagIcon,
    name: 'Layanan',
    path: '/services',
    permission: 'services.view',
  },
  {
    icon: ClockIcon,
    name: 'Waktu Layanan',
    path: '/service-times',
    permission: 'service-times.view',
  },
  {
    icon: DollarIcon,
    name: 'Pembayaran',
    path: '/payments',
    permission: 'payments.view',
    disabled: true,
  },
  {
    icon: UserCircleIcon,
    name: 'Pengguna',
    path: '/users',
    permission: 'users.view',
  },
  {
    icon: BarChartIcon,
    name: 'Laporan',
    path: '/reports',
    permission: 'reports.view',
    disabled: true,
  },
]
