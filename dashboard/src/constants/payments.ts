import type { RoleBadgeColor } from './roles'
import type { PaymentStatus, PaymentType } from '@/types/payment'

export interface PaymentTypeOption {
  value: PaymentType
  label: string
}

export interface PaymentStatusOption {
  value: PaymentStatus
  label: string
  color: RoleBadgeColor
}

export const PAYMENT_TYPE_OPTIONS: PaymentTypeOption[] = [
  { value: 'dp', label: 'DP (uang muka)' },
  { value: 'angsuran', label: 'Angsuran' },
  { value: 'pelunasan', label: 'Pelunasan' },
]

export const PAYMENT_STATUS_OPTIONS: PaymentStatusOption[] = [
  { value: 'berhasil', label: 'Berhasil', color: 'success' },
  { value: 'menunggu', label: 'Menunggu konfirmasi', color: 'warning' },
  { value: 'dibatalkan', label: 'Dibatalkan', color: 'error' },
]

const typeMap = new Map(PAYMENT_TYPE_OPTIONS.map((option) => [option.value, option]))
const statusMap = new Map(PAYMENT_STATUS_OPTIONS.map((option) => [option.value, option]))

export const paymentTypeLabel = (value: string): string =>
  typeMap.get(value as PaymentType)?.label ?? value

export const paymentStatusLabel = (value: string): string =>
  statusMap.get(value as PaymentStatus)?.label ?? value

export const paymentStatusColor = (value: string): RoleBadgeColor =>
  statusMap.get(value as PaymentStatus)?.color ?? 'gray'
