import { http } from './http'
import type {
  AuthUser,
  CreateUserPayload,
  MessageResponse,
  PaginatedUsers,
  ResetUserPasswordPayload,
  UpdateUserPayload,
  UsersQuery,
} from '@/types/auth'

export const usersApi = {
  async list(query: UsersQuery = {}): Promise<PaginatedUsers> {
    const { data } = await http.get<PaginatedUsers>('/users', {
      params: {
        search: query.search || undefined,
        role: query.role || undefined,
        page: query.page && query.page > 1 ? query.page : undefined,
      },
    })
    return data
  },

  async show(id: number): Promise<AuthUser> {
    const { data } = await http.get<{ data: AuthUser }>(`/users/${id}`)
    return data.data
  },

  async create(payload: CreateUserPayload): Promise<AuthUser> {
    const { data } = await http.post<{ data: AuthUser }>('/users', payload)
    return data.data
  },

  async update(id: number, payload: UpdateUserPayload): Promise<AuthUser> {
    const { data } = await http.put<{ data: AuthUser }>(`/users/${id}`, payload)
    return data.data
  },

  async resetPassword(id: number, payload: ResetUserPasswordPayload): Promise<MessageResponse> {
    const { data } = await http.put<MessageResponse>(`/users/${id}/reset-password`, payload)
    return data
  },

  async remove(id: number): Promise<MessageResponse> {
    const { data } = await http.delete<MessageResponse>(`/users/${id}`)
    return data
  },
}
