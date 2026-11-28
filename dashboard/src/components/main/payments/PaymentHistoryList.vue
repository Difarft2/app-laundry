<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Trash2Icon, LoaderCircleIcon } from '@/icons/layout'
import { paymentStatusColor, paymentStatusLabel, paymentTypeLabel } from '@/constants/payments'
import { paymentsApi } from '@/services/payments'
import type { Payment } from '@/types/payment'
import { formatCurrency } from '@/utils/format'
import { ref } from 'vue'

const props = withDefaults(
  defineProps<{
    payments: Payment[]
    showOrder?: boolean
  }>(),
  { showOrder: false },
)

const isVerifying = ref<Record<number, boolean>>({})

const canVerify = (payment: Payment): boolean => {
  return payment.status_pembayaran === 'menunggu' &&
    (payment.kode_metode === 'transfer' || payment.kode_metode === 'qris')
}

const handleVerify = async (payment: Payment) => {
  if (!confirm(`Yakin mverifikasi pembayaran ${payment.kode_pembayaran}? Status akan berubah menjadi 'Berhasil'.`)) {
    return
  }

  isVerifying.value[payment.id_pembayaran] = true
  try {
    await paymentsApi.verify(payment.id_pembayaran)
    window.location.reload()
  } catch (error) {
    alert('Gagal memverifikasi pembayaran. Silakan coba lagi.')
  } finally {
    isVerifying.value[payment.id_pembayaran] = false
  }
}

const formatDateTime = (iso: string | null): string => {
  if (!iso) return '—'
  const date = new Date(iso)
  if (Number.isNaN(date.getTime())) return iso

  return new Intl.DateTimeFormat('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date)
}
</script>

<template>
  <div class="max-w-full overflow-x-auto">
    <table class="w-full">
      <thead class="border-b border-gray-100 bg-gray-50 dark:border-gray-800 dark:bg-gray-900">
        <tr>
          <th class="px-5 py-3 text-start text-xs font-medium text-gray-500 lg:px-6 dark:text-gray-400">
            Kode / tanggal
          </th>
          <th v-if="props.showOrder" class="px-5 py-3 text-start text-xs font-medium text-gray-500 lg:px-6 dark:text-gray-400">
            Pesanan
          </th>
          <th class="px-5 py-3 text-start text-xs font-medium text-gray-500 lg:px-6 dark:text-gray-400">
            Metode
          </th>
          <th class="px-5 py-3 text-start text-xs font-medium text-gray-500 lg:px-6 dark:text-gray-400">
            Jenis
          </th>
          <th class="px-5 py-3 text-end text-xs font-medium text-gray-500 lg:px-6 dark:text-gray-400">
            Jumlah
          </th>
          <th class="px-5 py-3 text-start text-xs font-medium text-gray-500 lg:px-6 dark:text-gray-400">
            Status
          </th>
          <th class="px-5 py-3 text-start text-xs font-medium text-gray-500 lg:px-6 dark:text-gray-400">
            Dicatat oleh
          </th>
          <th class="px-5 py-3 text-start text-xs font-medium text-gray-500 lg:px-6 dark:text-gray-400">
            Tindakan
          </th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
        <tr v-if="props.payments.length === 0">
          <td
            :colspan="props.showOrder ? 8 : 7"
            class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400"
          >
            Belum ada riwayat pembayaran.
          </td>
        </tr>
        <tr v-for="payment in props.payments" :key="payment.id_pembayaran">
          <td class="px-5 py-4 lg:px-6">
            <p class="text-sm font-medium whitespace-nowrap text-gray-800 dark:text-white/90">
              {{ payment.kode_pembayaran }}
            </p>
            <p class="mt-0.5 text-xs whitespace-nowrap text-gray-500 dark:text-gray-400">
              {{ formatDateTime(payment.tanggal_pembayaran) }}
            </p>
          </td>
          <td v-if="props.showOrder" class="px-5 py-4 lg:px-6">
            <RouterLink
              v-if="payment.kode_pesanan"
              :to="`/orders/${payment.id_pesanan}`"
              class="text-sm font-medium text-gray-800 hover:text-brand-500 dark:text-white/90 dark:hover:text-brand-400"
            >
              {{ payment.kode_pesanan }}
            </RouterLink>
            <p v-else class="text-sm font-medium text-gray-800 dark:text-white/90">—</p>
            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
              {{ payment.nama_pelanggan ?? '-' }}
            </p>
          </td>
          <td class="px-5 py-4 text-sm text-gray-600 lg:px-6 dark:text-gray-300">
            {{ payment.nama_metode ?? payment.kode_metode ?? '—' }}
          </td>
          <td class="px-5 py-4 text-sm text-gray-600 lg:px-6 dark:text-gray-300">
            {{ paymentTypeLabel(payment.jenis_pembayaran) }}
          </td>
          <td class="px-5 py-4 text-end text-sm font-medium whitespace-nowrap text-gray-800 lg:px-6 dark:text-white/90">
            {{ formatCurrency(payment.jumlah_bayar) }}
          </td>
          <td class="px-5 py-4 lg:px-6">
            <Badge :color="paymentStatusColor(payment.status_pembayaran)" size="sm">
              {{ paymentStatusLabel(payment.status_pembayaran) }}
            </Badge>
            <p v-if="payment.catatan" class="mt-1 max-w-48 break-words text-xs text-gray-500 dark:text-gray-400">
              {{ payment.catatan }}
            </p>
          </td>
          <td class="px-5 py-4 text-sm text-gray-500 lg:px-6 dark:text-gray-400">
            {{ payment.nama_user ?? '—' }}
          </td>
          <td class="px-5 py-4 lg:px-6">
            <Button
              v-if="canVerify(payment)"
              variant="outline"
              size="sm"
              :disabled="isVerifying[payment.id_pembayaran]"
              @click="handleVerify(payment)"
            >
              <LoaderCircleIcon
                v-if="isVerifying[payment.id_pembayaran]"
                class="size-4 animate-spin"
              />
              <span v-else>Verifikasi</span>
            </Button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
