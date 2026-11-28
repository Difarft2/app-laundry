import { http } from './http'
import type {
  CreatePaymentPayload,
  PaginatedPayments,
  Payment,
  PaymentResponse,
  PaymentsQuery,
} from '@/types/payment'

export const paymentsApi = {
  async list(query: PaymentsQuery = {}): Promise<PaginatedPayments> {
    const { data } = await http.get<PaginatedPayments>('/payments', {
      params: {
        search: query.search || undefined,
        status_pembayaran: query.status_pembayaran || undefined,
        jenis_pembayaran: query.jenis_pembayaran || undefined,
        id_metode_pembayaran: query.id_metode_pembayaran || undefined,
        tanggal_mulai: query.tanggal_mulai || undefined,
        tanggal_selesai: query.tanggal_selesai || undefined,
        per_page: query.per_page || undefined,
        page: query.page && query.page > 1 ? query.page : undefined,
      },
    })
    return data
  },

  async show(id: number): Promise<Payment> {
    const { data } = await http.get<PaymentResponse>(`/payments/${id}`)
    return data.data
  },

  async create(payload: CreatePaymentPayload): Promise<Payment> {
    const dataToSend = {
      ...payload,
      idempotency_key: payload.idempotency_key || crypto.randomUUID(),
    }
    const { data } = await http.post<PaymentResponse>('/payments', dataToSend)
    return data.data
  },

  async verify(id: number): Promise<Payment> {
    const { data } = await http.patch<PaymentResponse>(`/payments/${id}/verify`)
    return data.data
  },
}
