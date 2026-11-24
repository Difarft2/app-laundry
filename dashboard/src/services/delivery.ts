import { http } from '@/services/http'
import type { PaginatedResponse } from '@/types/auth'
import type { DeliveryTask, DeliveryTaskPayload, UpdateDeliveryStatusPayload } from '@/types/delivery'

export const deliveryApi = {
  async list(params: Record<string, any> = {}): Promise<PaginatedResponse<DeliveryTask>> {
    const { data } = await http.get('/delivery-tasks', { params })
    return data
  },

  async show(id: number): Promise<DeliveryTask> {
    const { data } = await http.get(`/delivery-tasks/${id}`)
    return data.data
  },

  async create(payload: DeliveryTaskPayload): Promise<DeliveryTask> {
    const { data } = await http.post('/delivery-tasks', payload)
    return data.data
  },

  async verify(id: number): Promise<DeliveryTask> {
    const { data } = await http.patch(`/delivery-tasks/${id}/verify`)
    return data.data
  },

  async reject(id: number, keterangan?: string): Promise<DeliveryTask> {
    const { data } = await http.patch(`/delivery-tasks/${id}/reject`, { keterangan })
    return data.data
  },

  async assign(id: number, id_kurir: number | null): Promise<DeliveryTask> {
    const { data } = await http.patch(`/delivery-tasks/${id}/assign`, { id_kurir })
    return data.data
  },

  async accept(id: number): Promise<DeliveryTask> {
    const { data } = await http.patch(`/delivery-tasks/${id}/accept`)
    return data.data
  },

  async updateStatus(id: number, payload: UpdateDeliveryStatusPayload): Promise<DeliveryTask> {
    const { data } = await http.patch(`/delivery-tasks/${id}/status`, payload)
    return data.data
  },

  async getCouriers(): Promise<{ data: { id: number; name: string; no_hp: string }[] }> {
    const { data } = await http.get('/delivery-tasks/couriers')
    return data
  }
}
