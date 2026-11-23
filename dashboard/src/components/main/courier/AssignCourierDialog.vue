<script setup lang="ts">
import { ref, watch } from 'vue'
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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { deliveryApi } from '@/services/delivery'
import { normalizeApiError } from '@/services/http'
import type { DeliveryTask } from '@/types/delivery'

const props = defineProps<{
  open: boolean
  task: DeliveryTask | null
}>()

const emit = defineEmits<{
  (e: 'update:open', open: boolean): void
  (e: 'assigned', message: string): void
}>()

const selectedCourierId = ref<string>('none')
const couriers = ref<{ id: number; name: string; no_hp: string }[]>([])
const loadingCouriers = ref(false)
const submitting = ref(false)
const serverError = ref<string | null>(null)

const loadCouriers = async () => {
  loadingCouriers.value = true
  try {
    const res = await deliveryApi.getCouriers()
    couriers.value = res.data
  } catch (error) {
    serverError.value = normalizeApiError(error).message
  } finally {
    loadingCouriers.value = false
  }
}

watch(
  () => props.open,
  (open) => {
    if (!open) return
    serverError.value = null
    submitting.value = false
    selectedCourierId.value = props.task?.kurir ? String(props.task.kurir.id) : 'none'
    void loadCouriers()
  },
  { immediate: true },
)

const onSubmit = async (event?: Event) => {
  event?.preventDefault()
  if (!props.task) return

  submitting.value = true
  serverError.value = null

  try {
    const courierId = selectedCourierId.value === 'none' ? null : Number(selectedCourierId.value)
    await deliveryApi.assign(props.task.id_antar_jemput, courierId)
    emit('assigned', 'Penugasan kurir berhasil diperbarui.')
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
          Penugasan Kurir
        </DialogTitle>
        <DialogDescription class="text-sm text-gray-500 dark:text-gray-400">
          Pilih kurir untuk menangani tugas {{ task?.jenis_antar_jemput }} ini.
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

        <Field class="gap-1.5">
          <FieldLabel for="assign-courier-select">Pilih Kurir</FieldLabel>
          <Select v-model="selectedCourierId" :disabled="loadingCouriers">
            <SelectTrigger id="assign-courier-select" class="w-full" aria-label="Pilih Kurir">
              <SelectValue :placeholder="loadingCouriers ? 'Memuat kurir...' : 'Tanpa Kurir'" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="none">Tanpa Kurir (Kosongkan)</SelectItem>
              <SelectItem v-for="c in couriers" :key="c.id" :value="String(c.id)">
                {{ c.name }} {{ c.no_hp ? `(${c.no_hp})` : '' }}
              </SelectItem>
            </SelectContent>
          </Select>
        </Field>

        <DialogFooter class="mx-0 mb-0 gap-3 p-0 pt-2 sm:justify-end">
          <DialogClose as-child>
            <Button variant="outline" type="button" :disabled="submitting">Batal</Button>
          </DialogClose>
          <Button type="submit" :disabled="submitting || loadingCouriers">
            {{ submitting ? 'Menyimpan...' : 'Simpan Penugasan' }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
