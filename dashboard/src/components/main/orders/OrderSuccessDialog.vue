<script setup lang="ts">
import { ref } from 'vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { CheckCircleIcon, LoaderCircleIcon, PrinterIcon } from '@/icons/layout'
import { orderStatusColor, orderStatusLabel } from '@/constants/orders'
import { normalizeApiError } from '@/services/http'
import type { Order } from '@/types/order'
import { formatCurrency, formatDateTime } from '@/utils/format'
import { printOrderReceipt } from '@/utils/order-receipt'

const props = defineProps<{
  open: boolean
  order: Order | null
}>()

const emit = defineEmits<{
  (e: 'update:open', open: boolean): void
}>()

const printing = ref(false)
const printError = ref<string | null>(null)

const printOrder = async () => {
  const order = props.order
  if (!order || printing.value) return

  printing.value = true
  printError.value = null

  try {
    await printOrderReceipt(order)
  } catch (error) {
    printError.value = normalizeApiError(error).message
  } finally {
    printing.value = false
  }
}
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="max-w-[calc(100%-2rem)] sm:max-w-lg">
      <DialogHeader class="gap-2 pe-10 text-start">
        <div class="flex items-center gap-2 text-success-600 dark:text-success-400">
          <CheckCircleIcon class="size-6" />
          <span class="text-sm font-semibold">Pesanan berhasil dibuat</span>
        </div>
        <DialogTitle class="text-xl font-semibold text-gray-800 dark:text-white/90">
          {{ order?.kode_pesanan ?? '-' }}
        </DialogTitle>
        <DialogDescription class="text-sm text-gray-500 dark:text-gray-400">
          Cetak struk pesanan (PNG) untuk pelanggan. QR di dalam struk bisa di-scan karyawan untuk
          melihat rincian dan memperbarui status.
        </DialogDescription>
      </DialogHeader>

      <div v-if="order" class="space-y-3 rounded-xl border border-gray-200 p-4 text-sm dark:border-gray-800">
        <div class="flex items-center justify-between gap-3">
          <span class="text-gray-500 dark:text-gray-400">Nama pelanggan</span>
          <span class="font-medium text-gray-800 dark:text-white/90">{{ order.pelanggan.nama }}</span>
        </div>
        <div class="flex items-center justify-between gap-3">
          <span class="text-gray-500 dark:text-gray-400">No. HP</span>
          <span class="font-medium text-gray-800 dark:text-white/90">{{ order.pelanggan.no_hp }}</span>
        </div>
        <div class="flex items-center justify-between gap-3">
          <span class="text-gray-500 dark:text-gray-400">Tanggal masuk</span>
          <span class="font-medium text-gray-800 dark:text-white/90">
            {{ formatDateTime(order.tanggal_masuk) }}
          </span>
        </div>
        <div class="flex items-center justify-between gap-3">
          <span class="text-gray-500 dark:text-gray-400">Status</span>
          <Badge :color="orderStatusColor(order.status_pesanan)" size="sm">
            {{ orderStatusLabel(order.status_pesanan) }}
          </Badge>
        </div>

        <div v-if="order.detail_pesanan?.length" class="border-t border-gray-200 pt-3 dark:border-gray-800">
          <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
            Layanan
          </p>
          <div
            v-for="detail in order.detail_pesanan"
            :key="detail.id_detail_pesanan"
            class="flex items-center justify-between gap-3 py-0.5"
          >
            <span class="text-gray-700 dark:text-gray-200">
              {{ detail.nama_layanan }} &times; {{ detail.jumlah }} {{ detail.satuan }}
            </span>
            <span class="font-medium text-gray-800 dark:text-white/90">
              {{ formatCurrency(detail.subtotal) }}
            </span>
          </div>
        </div>

        <div class="flex items-center justify-between border-t border-gray-200 pt-3 dark:border-gray-800">
          <span class="font-semibold text-gray-800 dark:text-white/90">Total</span>
          <span class="font-semibold text-brand-600 dark:text-brand-400">
            {{ formatCurrency(order.total_harga) }}
          </span>
        </div>
      </div>

      <div
        v-if="printError"
        role="alert"
        class="rounded-lg border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-700 dark:border-error-500/20 dark:bg-error-500/10 dark:text-error-400"
      >
        {{ printError }}
      </div>

      <DialogFooter class="mx-0 mb-0 gap-3 p-0 pt-2 sm:justify-end">
        <Button variant="outline" @click="emit('update:open', false)">Tutup</Button>
        <Button :disabled="!order || printing" @click="printOrder">
          <LoaderCircleIcon v-if="printing" class="size-4 animate-spin" />
          <PrinterIcon v-else class="size-4" />
          {{ printing ? 'Menyiapkan struk...' : 'Cetak struk' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
