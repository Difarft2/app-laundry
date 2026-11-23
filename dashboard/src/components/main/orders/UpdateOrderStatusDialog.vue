<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Field, FieldError, FieldLabel } from '@/components/ui/field'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import { ORDER_STATUS_OPTIONS } from '@/constants/orders'
import { normalizeApiError } from '@/services/http'
import { ordersApi } from '@/services/orders'
import type { Order, OrderStatus } from '@/types/order'

const props = defineProps<{
  open: boolean
  order: Order | null
}>()

const emit = defineEmits<{
  (e: 'update:open', open: boolean): void
  (e: 'updated', message: string): void
}>()

const status = ref<OrderStatus>('menunggu')
const keterangan = ref('')
const submitting = ref(false)
const serverError = ref<string | null>(null)
const statusError = ref<string | null>(null)

const canChange = computed(() => {
  const current = props.order?.status_pesanan
  return current !== 'selesai' && current !== 'dibatalkan'
})

watch(
  () => props.open,
  (open) => {
    if (!open) return
    status.value = props.order?.status_pesanan ?? 'menunggu'
    keterangan.value = ''
    submitting.value = false
    serverError.value = null
    statusError.value = null
  },
  { immediate: true },
)

const onSubmit = async (event?: Event) => {
  event?.preventDefault()
  serverError.value = null
  statusError.value = null

  if (!props.order || !canChange.value) return

  if (status.value === props.order.status_pesanan) {
    statusError.value = 'Pilih status yang berbeda dari status saat ini.'
    return
  }

  submitting.value = true

  try {
    const keteranganValue = keterangan.value.trim()
    await ordersApi.updateStatus(props.order.id_pesanan, {
      status_pesanan: status.value,
      keterangan: keteranganValue ? keteranganValue : null,
    })

    emit('updated', `Status pesanan ${props.order.kode_pesanan} berhasil diperbarui.`)
    emit('update:open', false)
  } catch (error) {
    serverError.value = normalizeApiError(error).message
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="max-w-[calc(100%-2rem)] sm:max-w-md">
      <DialogHeader class="gap-2 pe-10 text-start">
        <DialogTitle class="text-xl font-semibold text-gray-800 dark:text-white/90">
          Ubah status pesanan
        </DialogTitle>
        <DialogDescription class="text-sm text-gray-500 dark:text-gray-400">
          Status proses laundry dikelola terpisah dan tidak berubah dari sini.
        </DialogDescription>
      </DialogHeader>

      <form class="space-y-5" novalidate @submit="onSubmit">
        <div
          v-if="serverError"
          role="alert"
          class="rounded-lg border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-700 dark:border-error-500/20 dark:bg-error-500/10 dark:text-error-400"
        >
          {{ serverError }}
        </div>

        <p
          v-if="!canChange"
          class="rounded-lg border border-warning-200 bg-warning-50 px-4 py-3 text-sm text-warning-700 dark:border-warning-500/20 dark:bg-warning-500/10 dark:text-warning-400"
        >
          Pesanan yang sudah selesai atau dibatalkan tidak dapat diubah statusnya lagi.
        </p>

        <Field :data-invalid="Boolean(statusError)" class="gap-1.5">
          <FieldLabel for="order-status">Status baru <span class="text-red-500">*</span></FieldLabel>
          <Select v-model="status" :disabled="!canChange">
            <SelectTrigger id="order-status" class="w-full" aria-label="Status baru">
              <SelectValue placeholder="Pilih status" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="option in ORDER_STATUS_OPTIONS" :key="option.value" :value="option.value">
                {{ option.label }}
              </SelectItem>
            </SelectContent>
          </Select>
          <FieldError :errors="[{ message: statusError ?? undefined }]" />
        </Field>

        <Field class="gap-1.5">
          <FieldLabel for="order-status-note">Keterangan</FieldLabel>
          <Textarea
            id="order-status-note"
            v-model="keterangan"
            rows="2"
            placeholder="Opsional, dicatat pada riwayat"
            :disabled="!canChange"
          />
        </Field>

        <DialogFooter class="mx-0 mb-0 gap-3 p-0 pt-2 sm:justify-end">
          <Button type="submit" :disabled="submitting || !canChange">
            {{ submitting ? 'Menyimpan...' : 'Simpan status' }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
