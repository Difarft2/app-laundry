import { http } from './http'
import type {
  AuthUser,
  LoginPayload,
  LoginResponse,
  MessageResponse,
  ProfileResponse,
  UpdatePasswordPayload,
  UpdateProfilePayload,
} from '@/types/auth'

export const authApi = {
  async login(payload: LoginPayload): Promise<LoginResponse> {
    const { data } = await http.post<LoginResponse>('/auth/login', {
      device_name: 'web-dashboard',
      ...payload,
    })
    return data
  },

  async logout(): Promise<MessageResponse> {
    const { data } = await http.post<MessageResponse>('/auth/logout')
    return data
  },

  async me(): Promise<AuthUser> {
    const { data } = await http.get<{ data: AuthUser }>('/auth/me')
    return data.data
  },

  async updateProfile(payload: UpdateProfilePayload): Promise<ProfileResponse> {
    const { data } = await http.put<ProfileResponse>('/auth/profile', payload)
    return data
  },

  async updatePassword(payload: UpdatePasswordPayload): Promise<MessageResponse> {
    const { data } = await http.put<MessageResponse>('/auth/password', payload)
    return data
  },
}
