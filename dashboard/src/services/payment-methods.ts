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

  async remove(id: number): Promise<MessageResponse> {
    const { data } = await http.delete<MessageResponse>(`/payment-methods/${id}`)
    return data
  },
}
