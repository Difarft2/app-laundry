import type { Order } from '@/types/order'

/**
 * Pesanan hanya boleh dibuatkan satu tugas pengantaran aktif.
 * Tugas selesai/gagal tidak menghalangi pembuatan tugas baru.
 */
export const hasActiveDelivery = (order: Order): boolean =>
  (order.antar_jemput ?? []).some(
    (task) =>
      task.jenis_antar_jemput === 'pengantaran'
      && !['selesai', 'gagal'].includes(task.status_antar_jemput),
  )
