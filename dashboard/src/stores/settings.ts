import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { normalizeApiError, type ApiError } from '@/services/http'
import { settingsApi } from '@/services/settings'
import { DEFAULT_APP_SETTING, type AppSetting, type UpdateSettingPayload } from '@/types/settings'

export const useSettingsStore = defineStore('settings', () => {
  const current = ref<AppSetting>({ ...DEFAULT_APP_SETTING })
  const loading = ref(false)
  const saving = ref(false)
  const error = ref<string | null>(null)

  const loaded = computed(() => Boolean(current.value.updated_at))
  const namaApp = computed(() => current.value.nama_app || DEFAULT_APP_SETTING.nama_app)
  const versiApp = computed(() => current.value.versi_app || DEFAULT_APP_SETTING.versi_app)

  const apply = (setting: AppSetting) => {
    current.value = { ...DEFAULT_APP_SETTING, ...setting }
    error.value = null
  }

  const load = async (force = false): Promise<void> => {
    // Data pengaturan dipakai banyak widget, cukup satu permintaan saat aplikasi dibuka.
    if (!force && loaded.value) return
    if (loading.value) return

    loading.value = true
    try {
      apply(await settingsApi.get())
    } catch (requestError) {
      error.value = normalizeApiError(requestError).message
      // Biarkan nilai default tetap terpakai bila API tidak tersedia.
    } finally {
      loading.value = false
    }
  }

  const update = async (payload: UpdateSettingPayload): Promise<void> => {
    saving.value = true
    try {
      apply(await settingsApi.update(payload))
    } catch (requestError) {
      const apiError: ApiError = normalizeApiError(requestError)
      error.value = apiError.message
      throw apiError
    } finally {
      saving.value = false
    }
  }

  const uploadLogo = async (file: File): Promise<void> => {
    saving.value = true
    try {
      apply(await settingsApi.uploadLogo(file))
    } catch (requestError) {
      const apiError: ApiError = normalizeApiError(requestError)
      error.value = apiError.message
      throw apiError
    } finally {
      saving.value = false
    }
  }

  return {
    current,
    loading,
    saving,
    error,
    loaded,
    namaApp,
    versiApp,
    load,
    update,
    uploadLogo,
  }
})
