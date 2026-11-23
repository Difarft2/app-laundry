import { ordersApi } from '@/services/orders'
import type { Order } from '@/types/order'

/**
 * Membuka struk pesanan (PNG) di tab baru. Jika pop-up diblokir browser,
 * gambar langsung diunduh dengan nama file yang mengikuti kode pesanan.
 */
export const printOrderReceipt = async (order: Order): Promise<void> => {
  const blob = await ordersApi.receipt(order.id_pesanan)
  const url = URL.createObjectURL(blob)
  const tab = window.open(url, '_blank')

  if (!tab) {
    const link = document.createElement('a')
    link.href = url
    link.download = `struk-${order.kode_pesanan}.png`
    link.click()
  }

  window.setTimeout(() => URL.revokeObjectURL(url), 60_000)
}
