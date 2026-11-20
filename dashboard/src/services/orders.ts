import { http } from './http'
import type { MessageResponse } from '@/types/auth'
import type {
  CreateOrderPayload,
  Order,
  OrderResponse,
  OrdersQuery,
  PaginatedOrders,
  UpdateOrderStatusPayload,
} from '@/types/order'

export const ordersApi = {
  async list(query: OrdersQuery = {}): Promise<PaginatedOrders> {
    const { data } = await http.get<PaginatedOrders>('/orders', {
      params: {
        search: query.search || undefined,
        status: query.status || undefined,
        tanggal_mulai: query.tanggal_mulai || undefined,
        tanggal_selesai: query.tanggal_selesai || undefined,
        per_page: query.per_page || undefined,
        page: query.page && query.page > 1 ? query.page : undefined,
      },
    })
    return data
  },

  async show(id: number): Promise<Order> {
    const { data } = await http.get<OrderResponse>(`/orders/${id}`)
    return data.data
  },

  async create(payload: CreateOrderPayload): Promise<Order> {
    const { data } = await http.post<OrderResponse>('/orders', payload)
    return data.data
  },

  async updateStatus(id: number, payload: UpdateOrderStatusPayload): Promise<Order> {
    const { data } = await http.patch<OrderResponse>(`/orders/${id}/status`, payload)
    return data.data
  },

  async remove(id: number): Promise<MessageResponse> {
    const { data } = await http.delete<MessageResponse>(`/orders/${id}`)
    return data
  },
}
