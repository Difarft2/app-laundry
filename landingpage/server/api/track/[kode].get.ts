interface TrackingResponse {
  success: boolean
  data?: Record<string, unknown>
  message?: string
}

export default defineEventHandler(async (event): Promise<TrackingResponse> => {
  const kode = getRouterParam(event, 'kode')?.trim() || ''
  if (!kode || kode.length > 50) {
    throw createError({ statusCode: 400, statusMessage: 'Nomor nota tidak valid.' })
  }

  const config = useRuntimeConfig(event)
  const apiBase = String(config.public.apiBase || '').replace(/\/+$/, '')

  try {
    return await $fetch<TrackingResponse>(`${apiBase}/v1/landing/track/${encodeURIComponent(kode)}`, {
      headers: {
        Accept: 'application/json',
        'X-Landing-Api-Key': String(config.landingApiKey || ''),
      },
      timeout: 10000,
    })
  } catch (error: unknown) {
    const failure = error as { response?: { status?: number }; data?: { message?: string } }
    throw createError({
      statusCode: failure.response?.status ?? 502,
      statusMessage: failure.data?.message || 'Data pesanan tidak dapat dimuat.',
    })
  }
})