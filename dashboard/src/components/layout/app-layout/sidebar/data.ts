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
    disabled: true,
  },
  {
    icon: UsersIcon,
    name: 'Pelanggan',
    path: '/customers',
    disabled: true,
  },
  {
    icon: BoxIcon,
    name: 'Proses Laundry',
    path: '/laundry-process',
    disabled: true,
  },
  {
    icon: TruckDeliveryIcon,
    name: 'Antar-Jemput',
    path: '/pickup-delivery',
    disabled: true,
  },
]

export const othersItems: NavItem[] = [
  {
    icon: TagIcon,
    name: 'Layanan',
    path: '/services',
    disabled: true,
  },
  {
    icon: ClockIcon,
    name: 'Waktu Layanan',
    path: '/service-times',
    disabled: true,
  },
  {
    icon: DollarIcon,
    name: 'Pembayaran',
    path: '/payments',
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
    disabled: true,
  },
]
