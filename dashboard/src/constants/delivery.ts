import type { DeliveryStatus, DeliveryType } from '@/types/delivery'

export const DELIVERY_TYPE_OPTIONS: { value: DeliveryType; label: string }[] = [
  { value: 'penjemputan', label: 'Penjemputan' },
  { value: 'pengantaran', label: 'Pengantaran' },
]

export const DELIVERY_STATUS_OPTIONS: { value: DeliveryStatus; label: string }[] = [
  { value: 'menunggu', label: 'Menunggu kurir' },
  { value: 'ditugaskan', label: 'Proses antar' },
  { value: 'menuju_lokasi', label: 'Menuju lokasi' },
  { value: 'barang_diambil', label: 'Barang diambil' },
  { value: 'barang_diantar', label: 'Barang diantar' },
  { value: 'selesai', label: 'Selesai diantar' },
  { value: 'gagal', label: 'Gagal' },
]

export const deliveryStatusMeta: Record<DeliveryStatus, { label: string; color: 'primary' | 'success' | 'error' | 'warning' | 'info' | 'gray' }> = {
  menunggu: { label: 'Menunggu kurir', color: 'gray' },
  ditugaskan: { label: 'Proses antar', color: 'info' },
  menuju_lokasi: { label: 'Menuju lokasi', color: 'warning' },
  barang_diambil: { label: 'Barang diambil', color: 'info' },
  barang_diantar: { label: 'Barang diantar', color: 'info' },
  selesai: { label: 'Selesai diantar', color: 'success' },
  gagal: { label: 'Gagal', color: 'error' },
}
