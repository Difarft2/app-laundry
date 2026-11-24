<script setup lang="ts">
import { ref, watch } from 'vue'
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
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { normalizeApiError } from '@/services/http'
import { ordersApi } from '@/services/orders'
import type { Order } from '@/types/order'

const props = defineProps<{
  open: boolean
  order: Order | null
}>()

const emit = defineEmits<{
  (e: 'update:open', open: boolean): void
  (e: 'created', message: string): void
}>()

const alamatTujuan = ref('')
const namaPenerima = ref('')
const noHpPenerima = ref('')
const catatan = ref('')
const submitting = ref(false)
const serverError = ref<string | null>(null)
const alamatError = ref<string | null>(null)

watch(
  () => props.open,
  (open) => {
    if (!open) return

    alamatTujuan.value = props.order?.pelanggan.alamat ?? ''
    namaPenerima.value = props.order?.pelanggan.nama ?? ''
    noHpPenerima.value = props.order?.pelanggan.no_hp ?? ''
    catatan.value = ''
    submitting.value = false
    serverError.value = null
    alamatError.value = null
  },
  { immediate: true },
)

const onSubmit = async (event?: Event) => {
  event?.preventDefault()
  serverError.value = null
  alamatError.value = null

  if (!props.order) return

  const alamat = alamatTujuan.value.trim()

  if (!alamat) {
    alamatError.value = 'Alamat tujuan wajib diisi.'
    return
  }

  submitting.value = true

  try {
    await ordersApi.createDelivery(props.order.id_pesanan, {
      alamat_tujuan: alamat,
      nama_penerima: namaPenerima.value.trim() || null,
      no_hp_penerima: noHpPenerima.value.trim() || null,
      catatan: catatan.value.trim() || null,
    })

    emit(
      'created',
      `Tugas pengantaran ${props.order.kode_pesanan} dibuat. Menunggu kurir menerima tugas.`,
    )
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
    <DialogContent class="max-w-[calc(100%-2rem)] sm:max-w-lg">
      <DialogHeader class="gap-2 pe-10 text-start">
        <DialogTitle class="text-xl font-semibold text-gray-800 dark:text-white/90">
          Antar pesanan
        </DialogTitle>
        <DialogDescription class="text-sm text-gray-500 dark:text-gray-400">
          Alamat ini akan dipakai kurir untuk mengantar pesanan
          <span v-if="order" class="font-medium">{{ order.kode_pesanan }}</span>.
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

        <Field :data-invalid="Boolean(alamatError)" class="gap-1.5">
          <FieldLabel for="delivery-address">Alamat tujuan <span class="text-red-500">*</span></FieldLabel>
          <Textarea
            id="delivery-address"
            v-model="alamatTujuan"
            rows="3"
            placeholder="Contoh: Jl. Mawar No. 12, RT 03/RW 04"
            :disabled="submitting"
          />
          <FieldError :errors="[{ message: alamatError ?? undefined }]" />
        </Field>

        <div class="grid gap-4 sm:grid-cols-2">
          <Field class="gap-1.5">
            <FieldLabel for="delivery-recipient">Nama penerima</FieldLabel>
            <Input
              id="delivery-recipient"
              v-model="namaPenerima"
              type="text"
              placeholder="Nama penerima"
              :disabled="submitting"
            />
          </Field>

          <Field class="gap-1.5">
            <FieldLabel for="delivery-phone">No. HP penerima</FieldLabel>
            <Input
              id="delivery-phone"
              v-model="noHpPenerima"
              type="tel"
              placeholder="08xxxxxxxxxx"
              :disabled="submitting"
            />
          </Field>
        </div>

        <Field class="gap-1.5">
          <FieldLabel for="delivery-note">Catatan untuk kurir</FieldLabel>
          <Textarea
            id="delivery-note"
            v-model="catatan"
            rows="2"
            placeholder="Opsional, misalnya patokan rumah atau titik drop"
            :disabled="submitting"
          />
        </Field>

        <DialogFooter class="mx-0 mb-0 gap-3 p-0 pt-2 sm:justify-end">
          <Button type="submit" :disabled="submitting">
            {{ submitting ? 'Membuat tugas...' : 'Buat tugas antar' }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
