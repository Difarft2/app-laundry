interface DeliveryRequestPayload {
  success: boolean
  message: string
  data: {
    id: number
    status: string
    created_at: string
  }
}

export default defineEventHandler(async (event): Promise<DeliveryRequestPayload> => {
  const body = await readBody(event)
  const config = useRuntimeConfig(event)

  const nama = typeof body?.nama === 'string' ? body.nama.trim() : ''
  const telepon = typeof body?.telepon === 'string' ? body.telepon.trim() : ''
  const alamat = typeof body?.alamat === 'string' ? body.alamat.trim() : ''

  if (!nama || !telepon || !alamat) {
    throw createError({ statusCode: 400, statusMessage: 'Nama, telepon, dan alamat wajib diisi.' })
  }

  if (nama.length > 150 || telepon.length > 20 || alamat.length > 1000) {
    throw createError({ statusCode: 400, statusMessage: 'Isian terlalu panjang.' })
  }

  if (!/^[0-9+\-\s()]+$/.test(telepon)) {
    throw createError({
      statusCode: 400,
      statusMessage: 'Nomor telepon hanya boleh berisi angka dan karakter telepon.',
    })
  }

  const apiBase = String(config.public.apiBase || '').replace(/\/+$/, '')
  const endpoint = `${apiBase}/v1/landing/delivery-requests`

  try {
    return await $fetch<DeliveryRequestPayload>(endpoint, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Landing-Api-Key': String(config.landingApiKey || ''),
      },
      body: { nama, telepon, alamat },
      timeout: 15000,
    })
  } catch (error: unknown) {
    const failure = error as {
      response?: { status?: number }
      data?: { message?: unknown; errors?: Record<string, unknown> }
    }
    const backendMessage = failure.data?.message
    const firstFieldError = Object.values(failure.data?.errors ?? {})[0]
    const detail = Array.isArray(firstFieldError) ? firstFieldError[0] : undefined

    let message = 'Permintaan gagal dikirim. Silakan coba lagi.'

    if (typeof detail === 'string' && detail.length <= 200) {
      message = detail
    } else if (typeof backendMessage === 'string' && backendMessage.length <= 200) {
      message = backendMessage
    }

    // Jangan pernah meneruskan kunci API atau detail internal ke browser.
    throw createError({
      statusCode: failure.response?.status === 401 ? 502 : (failure.response?.status ?? 502),
      statusMessage: message,
    })
  }
})
