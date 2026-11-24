import type { RoleBadgeColor } from './roles'
import type { OrderStatus } from '@/types/order'

export interface OrderStatusOption {
  value: OrderStatus
  label: string
  color: RoleBadgeColor
}

export const ORDER_STATUS_OPTIONS: OrderStatusOption[] = [
  { value: 'menunggu', label: 'Menunggu giliran', color: 'gray' },
  { value: 'dicuci', label: 'Di cuci', color: 'info' },
  { value: 'dikeringkan', label: 'Di keringkan', color: 'info' },
  { value: 'dilipat', label: 'Di lipat', color: 'warning' },
  { value: 'disetrika', label: 'Di setrika', color: 'warning' },
  { value: 'diproses', label: 'Diproses', color: 'info' },
  { value: 'siap_diambil', label: 'Siap diambil', color: 'warning' },
  { value: 'selesai', label: 'Selesai', color: 'success' },
  { value: 'dibatalkan', label: 'Dibatalkan', color: 'error' },
]

/**
 * Urutan tahapan laundry yang dipakai setelah scan QR. Status pengantaran
 * sengaja tidak dimasukkan karena dikelola dari modul antar-jemput.
 */
export const LAUNDRY_STATUS_FLOW: OrderStatus[] = [
  'menunggu',
  'dicuci',
  'dikeringkan',
  'dilipat',
  'disetrika',
  'selesai',
]

export const nextLaundryStatus = (status: OrderStatus): OrderStatus | null => {
  const index = LAUNDRY_STATUS_FLOW.indexOf(status)
  if (index === -1 || index === LAUNDRY_STATUS_FLOW.length - 1) return null
  return LAUNDRY_STATUS_FLOW[index + 1] ?? null
}

const statusMap = new Map(ORDER_STATUS_OPTIONS.map((opt) => [opt.value, opt]))

export const orderStatusLabel = (status: string): string =>
  statusMap.get(status as OrderStatus)?.label ?? status

export const orderStatusColor = (status: string): RoleBadgeColor =>
  statusMap.get(status as OrderStatus)?.color ?? 'gray'
