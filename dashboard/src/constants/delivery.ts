import type { DeliveryStatus, DeliveryType } from '@/types/delivery'

export const DELIVERY_TYPE_OPTIONS: { value: DeliveryType; label: string }[] = [
  { value: 'penjemputan', label: 'Penjemputan' },
  { value: 'pengantaran', label: 'Pengantaran' },
]

export const DELIVERY_STATUS_OPTIONS: { value: DeliveryStatus; label: string }[] = [
  { value: 'menunggu', label: 'Menunggu kurir' },
  { value: 'ditugaskan', label: 'Diterima kurir' },
  { value: 'menuju_lokasi', label: 'Menuju lokasi' },
  { value: 'barang_diambil', label: 'Barang dijemput' },
  { value: 'menuju_laundry', label: 'Menuju laundry' },
  { value: 'pakaian_sampai_laundry', label: 'Sampai laundry' },
  { value: 'barang_diantar', label: 'Barang diantar' },
  { value: 'selesai', label: 'Selesai diantar' },
  { value: 'gagal', label: 'Gagal' },
]

export const deliveryStatusMeta: Record<DeliveryStatus, { label: string; color: 'primary' | 'success' | 'error' | 'warning' | 'info' | 'gray' }> = {
  menunggu_verifikasi: { label: 'Menunggu verifikasi', color: 'warning' },
  menunggu: { label: 'Menunggu kurir', color: 'gray' },
  ditugaskan: { label: 'Diterima kurir', color: 'info' },
  menuju_lokasi: { label: 'Menuju lokasi', color: 'warning' },
  barang_diambil: { label: 'Barang dijemput', color: 'info' },
  menuju_laundry: { label: 'Menuju laundry', color: 'info' },
  pakaian_sampai_laundry: { label: 'Sampai laundry', color: 'success' },
  barang_diantar: { label: 'Barang diantar', color: 'info' },
  selesai: { label: 'Selesai diantar', color: 'success' },
  gagal: { label: 'Gagal', color: 'error' },
}
