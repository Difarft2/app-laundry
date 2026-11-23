import { ordersApi } from '@/services/orders'
import type { Order } from '@/types/order'

/**
 * Membuka PDF kartu pesanan di tab baru. Jika pop-up diblokir browser,
 * PDF langsung diunduh dengan nama file yang mengikuti kode pesanan.
 */
export const printOrderPdf = async (order: Order): Promise<void> => {
  const blob = await ordersApi.pdf(order.id_pesanan)
  const url = URL.createObjectURL(blob)
  const tab = window.open(url, '_blank')

  if (!tab) {
    const link = document.createElement('a')
    link.href = url
    link.download = `pesanan-${order.kode_pesanan}.pdf`
    link.click()
  }

  window.setTimeout(() => URL.revokeObjectURL(url), 60_000)
}
