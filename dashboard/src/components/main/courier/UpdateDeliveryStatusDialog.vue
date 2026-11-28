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
import { Field, FieldLabel } from '@/components/ui/field'
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
  ditugaskan: ['menuju_lokasi', 'gagal'],
  menuju_lokasi: ['barang_diambil', 'barang_diantar', 'gagal'],
  barang_diambil: ['menuju_laundry', 'gagal'],
  menuju_laundry: ['pakaian_sampai_laundry', 'gagal'],
  pakaian_sampai_laundry: ['selesai', 'gagal'],
  barang_diantar: ['selesai', 'gagal'],
  selesai: [],
  gagal: [],
}

const keterangan = ref('')
const submitting = ref<DeliveryStatus | null>(null)
const serverError = ref<string | null>(null)

const allowedStatuses = computed<DeliveryStatus[]>(() => {
  const task = props.task
  if (!task) return []
  return TRANSITIONS[task.status_antar_jemput].filter((next) => {
    if (next === 'ditugaskan' && !task.kurir) return false
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
    keterangan.value = ''
    submitting.value = null
    serverError.value = null
  },
  { immediate: true },
)

const submit = async (next: DeliveryStatus) => {
  if (!props.task || submitting.value) return
  serverError.value = null
  submitting.value = next

  try {
    await deliveryApi.updateStatus(props.task.id_antar_jemput, {
      status_antar_jemput: next,
      keterangan: keterangan.value.trim() || null,
    })

    emit('updated', `Status tugas ${props.task.pesanan?.kode_pesanan ?? ''} berhasil diperbarui.`)
    emit('update:open', false)
  } catch (error) {
    serverError.value = normalizeApiError(error).message
  } finally {
    submitting.value = null
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

      <div class="space-y-5">
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

        <template v-else>
          <div class="flex flex-wrap gap-2">
            <Button
              v-for="next in allowedStatuses"
              :key="next"
              :variant="next === 'gagal' ? 'outline' : 'default'"
              :class="next === 'gagal' ? 'border-error-300 text-error-600 hover:bg-error-50 dark:border-error-500/40 dark:text-error-400 dark:hover:bg-error-500/10' : ''"
              :disabled="submitting !== null"
              @click="submit(next)"
            >
              <span v-if="submitting === next" class="flex items-center gap-1.5">
                <svg class="size-3.5 animate-spin" viewBox="0 0 24 24" fill="none">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                </svg>
                Menyimpan...
              </span>
              <span v-else>{{ deliveryStatusMeta[next].label }}</span>
            </Button>
          </div>

          <Field class="gap-1.5">
            <FieldLabel for="task-status-note">Keterangan (opsional)</FieldLabel>
            <Textarea
              id="task-status-note"
              v-model="keterangan"
              rows="2"
              placeholder="Dicatat pada riwayat"
            />
          </Field>
        </template>
      </div>

      <DialogFooter class="mx-0 mb-0 p-0 pt-2 sm:justify-end">
        <DialogClose as-child>
          <Button variant="outline" type="button" :disabled="submitting !== null">Tutup</Button>
        </DialogClose>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
