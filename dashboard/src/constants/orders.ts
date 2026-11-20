import type { RoleBadgeColor } from './roles'
import type { OrderStatus } from '@/types/order'

export interface OrderStatusOption {
  value: OrderStatus
  label: string
  color: RoleBadgeColor
}

export const ORDER_STATUS_OPTIONS: OrderStatusOption[] = [
  { value: 'menunggu', label: 'Menunggu', color: 'gray' },
  { value: 'diproses', label: 'Diproses', color: 'info' },
  { value: 'siap_diambil', label: 'Siap diambil', color: 'warning' },
  { value: 'dalam_pengantaran', label: 'Dalam pengantaran', color: 'info' },
  { value: 'selesai', label: 'Selesai', color: 'success' },
  { value: 'dibatalkan', label: 'Dibatalkan', color: 'error' },
]

const statusMap = new Map(ORDER_STATUS_OPTIONS.map((opt) => [opt.value, opt]))

export const orderStatusLabel = (status: string): string =>
  statusMap.get(status as OrderStatus)?.label ?? status

export const orderStatusColor = (status: string): RoleBadgeColor =>
  statusMap.get(status as OrderStatus)?.color ?? 'gray'
