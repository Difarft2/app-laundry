import { http } from './http'
import type { AppSetting, UpdateSettingPayload } from '@/types/settings'

interface SettingResponse {
  success: boolean
  message?: string
  data: AppSetting
}

export const settingsApi = {
  async get(): Promise<AppSetting> {
    const { data } = await http.get<SettingResponse>('/settings')
    return data.data
  },

  async update(payload: UpdateSettingPayload): Promise<AppSetting> {
    const { data } = await http.put<SettingResponse>('/settings', payload)
    return data.data
  },

  async uploadLogo(file: File): Promise<AppSetting> {
    const body = new FormData()
    body.append('logo', file)

    const { data } = await http.post<SettingResponse>('/settings/logo', body, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return data.data
  },
}
