/**
 * Penyimpanan token akses.
 *
 * Token disimpan di localStorage bila pengguna memilih "Ingat saya",
 * selain itu di sessionStorage agar sesi berakhir saat tab ditutup.
 */

const TOKEN_KEY = 'pemuda_laundry.token'
const REMEMBER_KEY = 'pemuda_laundry.remember'

export const getToken = (): string | null =>
  localStorage.getItem(TOKEN_KEY) ?? sessionStorage.getItem(TOKEN_KEY)

export const setToken = (token: string, remember: boolean): void => {
  clearToken()
  const storage = remember ? localStorage : sessionStorage
  storage.setItem(TOKEN_KEY, token)
  localStorage.setItem(REMEMBER_KEY, remember ? '1' : '0')
}

export const clearToken = (): void => {
  localStorage.removeItem(TOKEN_KEY)
  sessionStorage.removeItem(TOKEN_KEY)
}

export const isRemembered = (): boolean => localStorage.getItem(REMEMBER_KEY) === '1'
