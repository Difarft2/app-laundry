import type {
  BadgeTone,
  CourierTaskStatus,
  CourierTaskType,
  DashboardRole,
  LaundryStatus,
  PaymentStatus,
  MetricTone,
} from './types'

/**
 * Status labels and badge colors follow section 7 of the PRD.
 * Colors are only for visualization; actual status values must come from the API.
 */
export const laundryStatusMeta: Record<LaundryStatus, { label: string; color: BadgeTone }> = {
  menunggu: { label: 'Menunggu giliran dicuci', color: 'gray' },
  dicuci: { label: 'Dicuci', color: 'info' },
  dikeringkan: { label: 'Dikeringkan', color: 'info' },
  finishing: { label: 'Finishing', color: 'warning' },
  selesai: { label: 'Pesanan selesai', color: 'success' },
}

export const paymentStatusMeta: Record<PaymentStatus, { label: string; color: BadgeTone }> = {
  belum_dibayar: { label: 'Belum dibayar', color: 'gray' },
  belum_lunas: { label: 'Belum lunas', color: 'error' },
  lunas: { label: 'Lunas', color: 'success' },
}

export const courierStatusMeta: Record<CourierTaskStatus, { label: string; color: BadgeTone }> = {
  menunggu_kurir: { label: 'Menunggu kurir', color: 'gray' },
  ditugaskan: { label: 'Kurir ditugaskan', color: 'info' },
  menuju_lokasi: { label: 'Menuju lokasi', color: 'warning' },
  barang_diambil: { label: 'Barang diambil', color: 'info' },
  barang_diantar: { label: 'Barang diantar', color: 'info' },
  selesai: { label: 'Tugas selesai', color: 'success' },
}

export const courierTypeMeta: Record<CourierTaskType, { label: string; color: BadgeTone }> = {
  jemput: { label: 'Jemput', color: 'primary' },
  antar: { label: 'Antar', color: 'info' },
}

export const roleMeta: Record<
  DashboardRole,
  { label: string; ownerName: string; description: string }
> = {
  owner: {
    label: 'Owner',
    ownerName: 'Budi Santoso',
    description: 'Ringkasan pendapatan, pesanan, dan operasional outlet.',
  },
  admin: {
    label: 'Admin',
    ownerName: 'Siti Rahmawati',
    description: 'Pantau pesanan, pembayaran, dan tugas antar-jemput.',
  },
  karyawan: {
    label: 'Karyawan',
    ownerName: 'Dewi Anggraini',
    description: 'Antrean dan pekerjaan laundry yang perlu diselesaikan.',
  },
  kurir: {
    label: 'Kurir',
    ownerName: 'Rizky Maulana',
    description: 'Tugas penjemputan dan pengantaran yang ditugaskan kepada Anda.',
  },
}

export const metricToneClasses: Record<MetricTone, string> = {
  brand: 'bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400',
  info: 'bg-sky-50 text-sky-500 dark:bg-sky-500/15 dark:text-sky-400',
  success: 'bg-green-50 text-green-600 dark:bg-green-500/15 dark:text-green-500',
  warning: 'bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-orange-400',
  error: 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-500',
  gray: 'bg-gray-100 text-gray-600 dark:bg-white/5 dark:text-gray-300',
}

export const activityDotClasses: Record<string, string> = {
  brand: 'bg-brand-500',
  info: 'bg-sky-500',
  success: 'bg-green-500',
  warning: 'bg-amber-500',
  error: 'bg-red-500',
  gray: 'bg-gray-400',
}
