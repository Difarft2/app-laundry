import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { fetchDashboard, type DashboardApi, type DashboardPeriod } from '@/services/dashboard'
import { normalizeApiError } from '@/services/http'

export const useDashboardStore = defineStore('dashboard', () => {
  const data = ref<DashboardApi | null>(null)
  const period = ref<DashboardPeriod>('7d')
  const loading = ref(false)
  const error = ref<string | null>(null)

  const loaded = computed(() => data.value !== null)

  const load = async (nextPeriod: DashboardPeriod = period.value, force = false): Promise<void> => {
    if (!force && data.value !== null) return

    loading.value = true
    error.value = null

    try {
      period.value = nextPeriod
      data.value = await fetchDashboard(nextPeriod)
    } catch (requestError) {
      error.value = normalizeApiError(requestError).message
      data.value = null
    } finally {
      loading.value = false
    }
  }

  const refresh = async (): Promise<void> => {
    await load(period.value, true)
  }

  return {
    data,
    period,
    loading,
    error,
    loaded,
    load,
    refresh,
  }
})
