import { http } from './http'
import type { MessageResponse } from '@/types/auth'
import type {
  CreateServiceTimePayload,
  PaginatedServiceTimes,
  ServiceTimeDetail,
  ServiceTimeResponse,
  ServiceTimesQuery,
  UpdateServiceTimePayload,
} from '@/types/service'

export const serviceTimesApi = {
  async list(query: ServiceTimesQuery = {}): Promise<PaginatedServiceTimes> {
    const { data } = await http.get<PaginatedServiceTimes>('/service-times', {
      params: {
        search: query.search || undefined,
        is_active: query.is_active === undefined ? undefined : query.is_active ? 1 : 0,
        per_page: query.per_page || undefined,
        page: query.page && query.page > 1 ? query.page : undefined,
      },
    })
    return data
  },

  async show(id: number): Promise<ServiceTimeDetail> {
    const { data } = await http.get<ServiceTimeResponse>(`/service-times/${id}`)
    return data.data
  },

  async create(payload: CreateServiceTimePayload): Promise<ServiceTimeDetail> {
    const { data } = await http.post<ServiceTimeResponse>('/service-times', payload)
    return data.data
  },

  async update(id: number, payload: UpdateServiceTimePayload): Promise<ServiceTimeDetail> {
    const { data } = await http.put<ServiceTimeResponse>(`/service-times/${id}`, payload)
    return data.data
  },

  async remove(id: number): Promise<MessageResponse> {
    const { data } = await http.delete<MessageResponse>(`/service-times/${id}`)
    return data
  },
}
