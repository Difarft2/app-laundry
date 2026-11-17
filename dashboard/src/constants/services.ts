import type { RoleBadgeColor } from './roles'
import type { ServiceFinishing, ServiceType } from '@/types/service'

export interface ServiceOption<T extends string> {
  value: T
  label: string
  description: string
}

/**
 * Mengikuti Data Reference pada dokumentasi Service Management.
 */
export const SERVICE_TYPE_OPTIONS: (ServiceOption<ServiceType> & { color: RoleBadgeColor })[] = [
  {
    value: 'kiloan',
    label: 'Kiloan',
    description: 'Dihitung berdasarkan berat (kilogram).',
    color: 'info',
  },
  {
    value: 'satuan',
    label: 'Satuan',
    description: 'Dihitung berdasarkan jumlah item.',
    color: 'warning',
  },
]

export const SERVICE_FINISHING_OPTIONS: ServiceOption<ServiceFinishing>[] = [
  { value: 'lipat', label: 'Lipat', description: 'Pakaian dilipat rapi.' },
  { value: 'setrika', label: 'Setrika', description: 'Pakaian disetrika.' },
  { value: 'tanpa_finishing', label: 'Tanpa finishing', description: 'Tanpa proses finishing.' },
]

const typeMap = new Map(SERVICE_TYPE_OPTIONS.map((option) => [option.value, option]))
const finishingMap = new Map(SERVICE_FINISHING_OPTIONS.map((option) => [option.value, option]))

export const serviceTypeLabel = (value: string): string =>
  typeMap.get(value as ServiceType)?.label ?? value

export const serviceTypeColor = (value: string): RoleBadgeColor =>
  typeMap.get(value as ServiceType)?.color ?? 'gray'

export const serviceFinishingLabel = (value: string): string =>
  finishingMap.get(value as ServiceFinishing)?.label ?? value

export const isServiceType = (value: string): value is ServiceType =>
  typeMap.has(value as ServiceType)

export const isServiceFinishing = (value: string): value is ServiceFinishing =>
  finishingMap.has(value as ServiceFinishing)

/**
 * Susun label estimasi waktu, mis. "Reguler · 48–72 jam".
 * Tampilkan seadanya bila salah satu/kedua batas kosong.
 */
export const formatEstimasi = (
  min: number | null | undefined,
  max: number | null | undefined,
): string => {
  if (min == null && max == null) return 'Tanpa estimasi'
  if (max == null) return `≥ ${min} jam`
  if (min == null) return `≤ ${max} jam`
  if (min === max) return `${min} jam`
  return `${min}–${max} jam`
}
