import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { dashboardRoleFromRoles, roleLabel } from '@/constants/roles'
import { authApi } from '@/services/auth'
import { clearToken, getToken, setToken } from '@/services/token'
import type { AuthUser, UpdatePasswordPayload, UpdateProfilePayload } from '@/types/auth'

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(getToken())
  const user = ref<AuthUser | null>(null)
  const initialized = ref(false)
  const loggingIn = ref(false)

  const isAuthenticated = computed(() => Boolean(token.value && user.value))
  const permissions = computed(() => user.value?.permissions ?? [])
  const role = computed(() => (user.value ? dashboardRoleFromRoles(user.value.roles) : null))
  const roleName = computed(() => (role.value ? roleLabel(role.value) : 'Pengguna'))
  const canPreviewRoles = computed(() => role.value === 'owner' || role.value === 'admin')

  const hasPermission = (permission: string): boolean => permissions.value.includes(permission)

  const hasRole = (expected: string): boolean => user.value?.roles.includes(expected) ?? false

  const applySession = (accessToken: string, remember: boolean, nextUser: AuthUser) => {
    setToken(accessToken, remember)
    token.value = accessToken
    user.value = nextUser
    initialized.value = true
  }

  const clearSession = () => {
    clearToken()
    token.value = null
    user.value = null
    initialized.value = false
  }

  async function login(email: string, password: string, remember: boolean) {
    loggingIn.value = true

    try {
      const response = await authApi.login({ email, password })
      applySession(response.data.access_token, remember, response.data.user)
      return response
    } finally {
      loggingIn.value = false
    }
  }

  /**
   * Memuat profil hanya sekali per sesi. Dipanggil oleh router guard.
   */
  async function ensureLoaded(): Promise<void> {
    if (initialized.value) return

    initialized.value = true

    if (!token.value) return

    try {
      user.value = await authApi.me()
    } catch {
      clearSession()
      initialized.value = true
    }
  }

  async function refreshUser(): Promise<AuthUser | null> {
    if (!token.value) return null
    user.value = await authApi.me()
    return user.value
  }

  async function logout(): Promise<void> {
    try {
      await authApi.logout()
    } catch {
      // Token bisa saja sudah tidak valid; sesi lokal tetap dibersihkan.
    } finally {
      clearSession()
      initialized.value = true
    }
  }

  async function updateProfile(payload: UpdateProfilePayload) {
    const response = await authApi.updateProfile(payload)
    user.value = response.data.user
    return response
  }

  async function updatePassword(payload: UpdatePasswordPayload) {
    const response = await authApi.updatePassword(payload)
    // Backend mencabut seluruh token setelah password berubah.
    clearSession()
    initialized.value = true
    return response
  }

  return {
    token,
    user,
    initialized,
    loggingIn,
    isAuthenticated,
    permissions,
    role,
    roleName,
    canPreviewRoles,
    hasPermission,
    hasRole,
    login,
    ensureLoaded,
    refreshUser,
    logout,
    updateProfile,
    updatePassword,
    clearSession,
  }
})
