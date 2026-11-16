import { http } from './http'
import type {
  CreateServicePayload,
  PaginatedServices,
  Service,
  ServiceResponse,
  ServicesQuery,
  UpdateServicePayload,
} from '@/types/service'
import type { MessageResponse } from '@/types/auth'

export const servicesApi = {
  async list(query: ServicesQuery = {}): Promise<PaginatedServices> {
    const { data } = await http.get<PaginatedServices>('/services', {
      params: {
        search: query.search || undefined,
        jenis_layanan: query.jenis_layanan || undefined,
        // Filter status hanya dikirim bila dipilih; backend memakai `has()`.
        is_active: query.is_active === undefined ? undefined : query.is_active ? 1 : 0,
        per_page: query.per_page || undefined,
        page: query.page && query.page > 1 ? query.page : undefined,
      },
    })
    return data
  },

  async show(id: number): Promise<Service> {
    const { data } = await http.get<ServiceResponse>(`/services/${id}`)
    return data.data
  },

  async create(payload: CreateServicePayload): Promise<Service> {
    const { data } = await http.post<ServiceResponse>('/services', payload)
    return data.data
  },

  async update(id: number, payload: UpdateServicePayload): Promise<Service> {
    const { data } = await http.put<ServiceResponse>(`/services/${id}`, payload)
    return data.data
  },

  async remove(id: number): Promise<MessageResponse> {
    const { data } = await http.delete<MessageResponse>(`/services/${id}`)
    return data
  },
}
