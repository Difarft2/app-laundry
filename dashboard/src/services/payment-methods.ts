import { http } from './http'
import type { MessageResponse } from '@/types/auth'
import type {
  CreatePaymentMethodPayload,
  PaginatedPaymentMethods,
  PaymentMethod,
  PaymentMethodResponse,
  PaymentMethodsQuery,
  UpdatePaymentMethodPayload,
} from '@/types/payment-method'

export const paymentMethodsApi = {
  async list(query: PaymentMethodsQuery = {}): Promise<PaginatedPaymentMethods> {
    const { data } = await http.get<PaginatedPaymentMethods>('/payment-methods', {
      params: {
        search: query.search || undefined,
        // Filter status hanya dikirim bila dipilih; backend memakai `has()`.
        is_active: query.is_active === undefined ? undefined : query.is_active ? 1 : 0,
        per_page: query.per_page || undefined,
        page: query.page && query.page > 1 ? query.page : undefined,
      },
    })
    return data
  },

  async show(id: number): Promise<PaymentMethod> {
    const { data } = await http.get<PaymentMethodResponse>(`/payment-methods/${id}`)
    return data.data
  },

  async create(payload: CreatePaymentMethodPayload): Promise<PaymentMethod> {
    const { data } = await http.post<PaymentMethodResponse>('/payment-methods', payload)
    return data.data
  },

  async update(id: number, payload: UpdatePaymentMethodPayload): Promise<PaymentMethod> {
    const { data } = await http.put<PaymentMethodResponse>(`/payment-methods/${id}`, payload)
    return data.data
  },

  /** Aktif/nonaktifkan tanpa menghapus data. */
  async updateStatus(id: number, isActive: boolean): Promise<PaymentMethod> {
    const { data } = await http.patch<PaymentMethodResponse>(`/payment-methods/${id}/status`, {
      is_active: isActive,
    })
    return data.data
  },

  async remove(id: number): Promise<MessageResponse> {
    const { data } = await http.delete<MessageResponse>(`/payment-methods/${id}`)
    return data
  },
}
