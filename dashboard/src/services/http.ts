import axios from 'axios'
import { clearToken, getToken } from './token'

/**
 * Base URL mengikuti dokumentasi API: `{{base_url}}/api/v1`.
 * `VITE_API_BASE_URL` berisi base URL tanpa versi, contoh:
 * http://127.0.0.1:8000/api
 */
const rawBaseUrl = import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000/api'
const baseURL = `${rawBaseUrl.replace(/\/+$/, '')}/v1`

export const http = axios.create({
  baseURL,
  timeout: 20000,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

http.interceptors.request.use((config) => {
  const token = getToken()

  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  return config
})

http.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error.response?.status
    const url = String(error.config?.url ?? '')
    const isLoginRequest = url.includes('/auth/login')

    // Token tidak valid/kedaluwarsa: bersihkan sesi dan minta login kembali.
    if (status === 401 && !isLoginRequest) {
      clearToken()

      if (!window.location.pathname.startsWith('/signin')) {
        const redirect = encodeURIComponent(`${window.location.pathname}${window.location.search}`)
        window.location.assign(`/signin?redirect=${redirect}&expired=1`)
      }
    }

    return Promise.reject(error)
  },
)

export interface ApiFieldErrors {
  [field: string]: string[]
}

export interface ApiError {
  status?: number
  message: string
  errors?: ApiFieldErrors
  isNetworkError?: boolean
}

const genericMessage: Record<number, string> = {
  400: 'Permintaan tidak dapat diproses.',
  401: 'Sesi Anda berakhir. Silakan login kembali.',
  403: 'Anda tidak memiliki izin untuk tindakan ini.',
  404: 'Data yang diminta tidak ditemukan.',
  422: 'Data yang dikirim belum valid.',
  429: 'Terlalu banyak percobaan. Coba lagi dalam satu menit.',
  500: 'Terjadi kesalahan pada server. Coba beberapa saat lagi.',
}

export const normalizeApiError = (error: unknown): ApiError => {
  if (axios.isAxiosError(error)) {
    if (!error.response) {
      return {
        message:
          `Tidak dapat terhubung ke server API. Pastikan backend berjalan di ${rawBaseUrl.replace(/\/+$/, '')}`,
        isNetworkError: true,
      }
    }

    const status = error.response.status
    const raw = error.response.data as { message?: string; errors?: ApiFieldErrors } | undefined

    // 422 bisa berarti validasi per field atau aturan bisnis (mis. owner terakhir).
    // Aturan bisnis tidak punya `errors`, jadi pesan backend ditampilkan apa adanya.
    const isValidationError = status === 422 && Boolean(raw?.errors)

    return {
      status,
      message: isValidationError
        ? genericMessage[422]
        : (raw?.message ?? genericMessage[status] ?? 'Terjadi kesalahan.'),
      errors: raw?.errors,
    }
  }

  return { message: 'Terjadi kesalahan tak terduga.' }
}
