<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogClose,
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
import { deliveryStatusMeta } from '@/constants/delivery'
import { deliveryApi } from '@/services/delivery'
import { normalizeApiError } from '@/services/http'
import type { DeliveryStatus, DeliveryTask } from '@/types/delivery'

const props = defineProps<{
  open: boolean
  task: DeliveryTask | null
}>()

const emit = defineEmits<{
  (e: 'update:open', open: boolean): void
  (e: 'updated', message: string): void
}>()

/**
 * Mengikuti aturan transisi di DeliveryTaskController (backend).
 */
const TRANSITIONS: Record<DeliveryStatus, DeliveryStatus[]> = {
  menunggu_verifikasi: ['menunggu', 'gagal'],
  menunggu: ['ditugaskan', 'gagal'],
  ditugaskan: ['menuju_lokasi', 'selesai', 'gagal'],
  menuju_lokasi: ['menuju_laundry', 'barang_diambil', 'barang_diantar', 'selesai', 'gagal'],
  menuju_laundry: ['pakaian_sampai_laundry', 'gagal'],
  pakaian_sampai_laundry: ['selesai', 'gagal'],
  barang_diambil: ['selesai', 'gagal'],
  barang_diantar: ['selesai', 'gagal'],
  selesai: [],
  gagal: [],
}

const status = ref<DeliveryStatus | ''>('')
const keterangan = ref('')
const submitting = ref(false)
const serverError = ref<string | null>(null)
const statusError = ref<string | null>(null)

const allowedStatuses = computed<DeliveryStatus[]>(() => {
  const task = props.task
  if (!task) return []
  return TRANSITIONS[task.status_antar_jemput].filter((next) => {
    // Status `ditugaskan` membutuhkan kurir yang sudah ditetapkan.
    if (next === 'ditugaskan' && !task.kurir) return false
    // Hanya tampilkan status yang sesuai jenis tugas.
    if (
      ['barang_diambil', 'menuju_laundry', 'pakaian_sampai_laundry'].includes(next)
      && task.jenis_antar_jemput !== 'penjemputan'
    ) {
      return false
    }
    if (next === 'barang_diantar' && task.jenis_antar_jemput !== 'pengantaran') return false
    return true
  })
})

watch(
  () => props.open,
  (open) => {
    if (!open) return
    status.value = ''
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

  if (!props.task || !status.value) {
    statusError.value = 'Pilih status baru.'
    return
  }

  submitting.value = true

  try {
    await deliveryApi.updateStatus(props.task.id_antar_jemput, {
      status_antar_jemput: status.value,
      keterangan: keterangan.value.trim() || null,
    })

    emit('updated', `Status tugas ${props.task.pesanan?.kode_pesanan ?? ''} berhasil diperbarui.`)
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
          Perbarui status tugas
        </DialogTitle>
        <DialogDescription class="text-sm text-gray-500 dark:text-gray-400">
          Status saat ini:
          <span class="font-medium text-gray-700 dark:text-gray-300">
            {{ task ? deliveryStatusMeta[task.status_antar_jemput].label : '—' }}
          </span>
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
          v-if="allowedStatuses.length === 0"
          class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600 dark:border-gray-800 dark:bg-white/5 dark:text-gray-300"
        >
          Tugas ini sudah selesai dan tidak dapat diubah lagi.
        </p>

        <Field v-else :data-invalid="Boolean(statusError)" class="gap-1.5">
          <FieldLabel for="task-status">Status baru <span class="text-red-500">*</span></FieldLabel>
          <Select v-model="status">
            <SelectTrigger id="task-status" class="w-full" aria-label="Status baru">
              <SelectValue placeholder="Pilih status" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="next in allowedStatuses" :key="next" :value="next">
                {{ deliveryStatusMeta[next].label }}
              </SelectItem>
            </SelectContent>
          </Select>
          <FieldError :errors="[{ message: statusError ?? undefined }]" />
        </Field>

        <Field class="gap-1.5">
          <FieldLabel for="task-status-note">Keterangan</FieldLabel>
          <Textarea
            id="task-status-note"
            v-model="keterangan"
            rows="2"
            placeholder="Opsional, dicatat pada riwayat"
            :disabled="allowedStatuses.length === 0"
          />
        </Field>

        <DialogFooter class="mx-0 mb-0 gap-3 p-0 pt-2 sm:justify-end">
          <DialogClose as-child>
            <Button variant="outline" type="button" :disabled="submitting">Batal</Button>
          </DialogClose>
          <Button type="submit" :disabled="submitting || allowedStatuses.length === 0">
            {{ submitting ? 'Menyimpan...' : 'Simpan status' }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
