import { http } from './http'

export interface ExpensePayload {
  kategori: string
  deskripsi?: string
  jumlah: number
  tanggal: string
}

export const expenseApi = {
  create: async (payload: ExpensePayload) => {
    const { data } = await http.post('/expenses', payload)
    return data
  },
}
