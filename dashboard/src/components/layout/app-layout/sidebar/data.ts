import {
  BarChartIcon,
  BoxIcon,
  CartIcon,
  ClockIcon,
  DollarIcon,
  GearIcon,
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
    readOnlyName: 'Riwayat Pesanan',
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
    name: 'Metode Pembayaran',
    path: '/payment-methods',
    permission: 'payment-methods.view',
  },
  {
    icon: DollarIcon,
    name: 'Riwayat Pembayaran',
    path: '/payments',
    permission: 'payments.view',
  },
  {
    icon: UserCircleIcon,
    name: 'Pengguna',
    path: '/users',
    permission: 'users.view',
  },
  {
    icon: BarChartIcon,
    name: 'Laporan & Histori',
    path: '/reports',
    permission: 'reports.view',
  },
  {
    icon: GearIcon,
    name: 'Pengaturan',
    path: '/settings',
    permission: 'settings.manage',
  },
  {
    icon: GearIcon,
    name: 'Manajemen Akses',
    adminOnly: true,
    subItems: [
      { name: 'Role Permission', path: '/settings/role-permission' },
      { name: 'User Permission', path: '/settings/user-permission' },
    ],
  },
]
