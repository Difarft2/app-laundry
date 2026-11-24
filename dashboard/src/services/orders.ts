import { http } from './http'
import type { MessageResponse } from '@/types/auth'
import type { DeliveryTask } from '@/types/delivery'
import type {
  CreateOrderDeliveryPayload,
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
        status_pesanan: query.status || undefined,
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

  async lookupByCode(kodePesanan: string): Promise<Order> {
    const { data } = await http.get<OrderResponse>('/orders/lookup', {
      params: { kode_pesanan: kodePesanan },
    })
    return data.data
  },

  async receipt(id: number): Promise<Blob> {
    const { data } = await http.get<Blob>(`/orders/${id}/receipt`, {
      responseType: 'blob',
    })
    return data
  },

  async create(payload: CreateOrderPayload): Promise<Order> {
    const { data } = await http.post<OrderResponse>('/orders', payload)
    return data.data
  },

  async updateStatus(id: number, payload: UpdateOrderStatusPayload): Promise<Order> {
    const { data } = await http.patch<OrderResponse>(`/orders/${id}/status`, payload)
    return data.data
  },

  async createDelivery(id: number, payload: CreateOrderDeliveryPayload = {}): Promise<DeliveryTask> {
    const { data } = await http.post<{ data: DeliveryTask }>(`/orders/${id}/delivery`, payload)
    return data.data
  },

  async remove(id: number): Promise<MessageResponse> {
    const { data } = await http.delete<MessageResponse>(`/orders/${id}`)
    return data
  },
}
