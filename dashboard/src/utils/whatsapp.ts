/**
 * Buat tautan WhatsApp dari nomor telepon pelanggan.
 * Nomor lokal Indonesia (08xxxxxxxxxx) diubah ke format 628xxxxxxxxxx.
 */
export const whatsappLink = (
  phone: string | null | undefined,
  message?: string,
): string | null => {
  const digits = (phone ?? '').replace(/\D/g, '')

  if (digits.length < 8) return null

  let normalized = digits

  if (normalized.startsWith('0')) {
    normalized = `62${normalized.slice(1)}`
  } else if (normalized.startsWith('8')) {
    normalized = `62${normalized}`
  }

  const query = message ? `?text=${encodeURIComponent(message)}` : ''

  return `https://wa.me/${normalized}${query}`
}
