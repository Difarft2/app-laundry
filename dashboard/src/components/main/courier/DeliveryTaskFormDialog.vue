<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
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
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import { translateFieldErrors } from '@/components/main/orders/errors'
import { deliveryApi } from '@/services/delivery'
import { normalizeApiError } from '@/services/http'
import { ordersApi } from '@/services/orders'
import type { Order } from '@/types/order'
import type { DeliveryTaskPayload, DeliveryType } from '@/types/delivery'

const props = defineProps<{ open: boolean }>()

const emit = defineEmits<{
  (e: 'update:open', open: boolean): void
  (e: 'saved', message: string): void
}>()

interface FormData {
  idPesanan: string
  jenis: DeliveryType
  idKurir: string
  alamatTujuan: string
  namaPenerima: string
  noHpPenerima: string
  jadwal: string
  catatan: string
}

const emptyForm = (): FormData => ({
  idPesanan: '',
  jenis: 'penjemputan',
  idKurir: 'none',
  alamatTujuan: '',
  namaPenerima: '',
  noHpPenerima: '',
  jadwal: '',
  catatan: '',
})

const form = reactive<FormData>(emptyForm())

const orders = ref<Order[]>([])
const ordersLoading = ref(false)
const couriers = ref<{ id: number; name: string; no_hp: string }[]>([])
const couriersLoading = ref(false)

const submitting = ref(false)
const serverError = ref<string | null>(null)
const fieldErrors = ref<Record<string, string>>({})

const selectedOrder = computed<Order | null>(
  () => orders.value.find((order) => String(order.id_pesanan) === form.idPesanan) ?? null,
)

const loadOrders = async () => {
  ordersLoading.value = true
  try {
    const result = await ordersApi.list({ per_page: 100 })
    orders.value = result.data
  } catch (error) {
    serverError.value = normalizeApiError(error).message
  } finally {
    ordersLoading.value = false
  }
}

const loadCouriers = async () => {
  couriersLoading.value = true
  try {
    const result = await deliveryApi.getCouriers()
    couriers.value = result.data
  } catch (error) {
    serverError.value = normalizeApiError(error).message
  } finally {
    couriersLoading.value = false
  }
}

watch(
  () => props.open,
  (open) => {
    if (!open) return

    Object.assign(form, emptyForm())
    serverError.value = null
    fieldErrors.value = {}

    void loadOrders()
    void loadCouriers()
  },
  { immediate: true },
)

// Isi alamat tujuan dari alamat pesanan terpilih bila masih kosong.
watch(selectedOrder, (order) => {
  if (order && !form.alamatTujuan) {
    form.alamatTujuan = order.pelanggan.alamat ?? ''
    if (!form.namaPenerima) form.namaPenerima = order.pelanggan.nama
    if (!form.noHpPenerima) form.noHpPenerima = order.pelanggan.no_hp
  }
})

const onSubmit = async (event?: Event) => {
  event?.preventDefault()
  serverError.value = null
  fieldErrors.value = {}

  if (!form.idPesanan) {
    fieldErrors.value = { id_pesanan: 'Pesanan wajib dipilih.' }
    return
  }
  if (!form.alamatTujuan.trim()) {
    fieldErrors.value = { alamat_tujuan: 'Alamat tujuan wajib diisi.' }
    return
  }

  submitting.value = true

  try {
    const payload: DeliveryTaskPayload = {
      id_pesanan: Number(form.idPesanan),
      jenis_antar_jemput: form.jenis,
      id_kurir: form.idKurir === 'none' ? null : Number(form.idKurir),
      alamat_tujuan: form.alamatTujuan.trim(),
      nama_penerima: form.namaPenerima.trim() || null,
      no_hp_penerima: form.noHpPenerima.trim() || null,
      jadwal: form.jadwal.trim() ? form.jadwal.trim().replace('T', ' ') + ':00' : null,
      catatan: form.catatan.trim() || null,
    }

    await deliveryApi.create(payload)
    emit('saved', 'Tugas antar-jemput berhasil dibuat.')
    emit('update:open', false)
  } catch (error) {
    const apiError = normalizeApiError(error)
    const translated = translateFieldErrors(apiError.errors)

    if (Object.keys(translated).length > 0) {
      fieldErrors.value = translated
    } else {
      serverError.value = apiError.message
    }
  } finally {
    submitting.value = false
  }
}

const errorOf = (field: string): string | undefined => fieldErrors.value[field]
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent
      class="max-h-[calc(100vh-3rem)] max-w-[calc(100%-2rem)] overflow-y-auto sm:max-w-xl"
    >
      <DialogHeader class="gap-2 pe-10 text-start">
        <DialogTitle class="text-xl font-semibold text-gray-800 dark:text-white/90">
          Buat tugas antar-jemput
        </DialogTitle>
        <DialogDescription class="text-sm text-gray-500 dark:text-gray-400">
          Satu pesanan hanya boleh memiliki satu penjemputan dan satu pengantaran.
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

        <div class="grid gap-5 sm:grid-cols-2">
          <Field :data-invalid="Boolean(errorOf('id_pesanan'))" class="gap-1.5">
            <FieldLabel for="task-order">Pesanan <span class="text-red-500">*</span></FieldLabel>
            <Select v-model="form.idPesanan" :disabled="ordersLoading">
              <SelectTrigger id="task-order" class="w-full" aria-label="Pesanan">
                <SelectValue :placeholder="ordersLoading ? 'Memuat pesanan...' : 'Pilih pesanan'" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="order in orders" :key="order.id_pesanan" :value="String(order.id_pesanan)">
                  {{ order.kode_pesanan }} · {{ order.pelanggan.nama }}
                </SelectItem>
              </SelectContent>
            </Select>
            <FieldError :errors="[{ message: errorOf('id_pesanan') }]" />
            <p v-if="!ordersLoading && orders.length === 0" class="text-xs text-gray-400 dark:text-gray-500">
              Belum ada pesanan. Buat pesanan terlebih dahulu.
            </p>
          </Field>

          <Field :data-invalid="Boolean(errorOf('jenis_antar_jemput'))" class="gap-1.5">
            <FieldLabel for="task-type">Jenis tugas <span class="text-red-500">*</span></FieldLabel>
            <Select v-model="form.jenis">
              <SelectTrigger id="task-type" class="w-full" aria-label="Jenis tugas">
                <SelectValue placeholder="Pilih jenis" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="penjemputan">Penjemputan</SelectItem>
                <SelectItem value="pengantaran">Pengantaran</SelectItem>
              </SelectContent>
            </Select>
            <FieldError :errors="[{ message: errorOf('jenis_antar_jemput') }]" />
          </Field>
        </div>

        <Field :data-invalid="Boolean(errorOf('alamat_tujuan'))" class="gap-1.5">
          <FieldLabel for="task-address">Alamat tujuan <span class="text-red-500">*</span></FieldLabel>
          <Input
            id="task-address"
            v-model="form.alamatTujuan"
            placeholder="Alamat penjemputan/pengantaran"
            autocomplete="off"
          />
          <FieldError :errors="[{ message: errorOf('alamat_tujuan') }]" />
        </Field>

        <div class="grid gap-5 sm:grid-cols-2">
          <Field class="gap-1.5">
            <FieldLabel for="task-recipient">Nama penerima</FieldLabel>
            <Input
              id="task-recipient"
              v-model="form.namaPenerima"
              placeholder="Opsional"
              autocomplete="off"
            />
            <FieldError :errors="[{ message: errorOf('nama_penerima') }]" />
          </Field>

          <Field class="gap-1.5">
            <FieldLabel for="task-recipient-phone">Nomor HP penerima</FieldLabel>
            <Input
              id="task-recipient-phone"
              v-model="form.noHpPenerima"
              type="tel"
              inputmode="tel"
              placeholder="Opsional"
              autocomplete="off"
            />
            <FieldError :errors="[{ message: errorOf('no_hp_penerima') }]" />
          </Field>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
          <Field class="gap-1.5">
            <FieldLabel for="task-schedule">Jadwal</FieldLabel>
            <Input id="task-schedule" v-model="form.jadwal" type="datetime-local" />
            <FieldError :errors="[{ message: errorOf('jadwal') }]" />
          </Field>

          <Field class="gap-1.5">
            <FieldLabel for="task-courier">Kurir</FieldLabel>
            <Select v-model="form.idKurir" :disabled="couriersLoading">
              <SelectTrigger id="task-courier" class="w-full" aria-label="Kurir">
                <SelectValue :placeholder="couriersLoading ? 'Memuat kurir...' : 'Tanpa kurir'" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="none">Tanpa kurir (tugaskan nanti)</SelectItem>
                <SelectItem v-for="courier in couriers" :key="courier.id" :value="String(courier.id)">
                  {{ courier.name }}
                </SelectItem>
              </SelectContent>
            </Select>
            <FieldError :errors="[{ message: errorOf('id_kurir') }]" />
          </Field>
        </div>

        <Field class="gap-1.5">
          <FieldLabel for="task-note">Catatan</FieldLabel>
          <Textarea id="task-note" v-model="form.catatan" rows="2" placeholder="Opsional" />
          <FieldError :errors="[{ message: errorOf('catatan') }]" />
        </Field>

        <DialogFooter class="mx-0 mb-0 gap-3 p-0 pt-2 sm:justify-end">
          <DialogClose as-child>
            <Button variant="outline" type="button" :disabled="submitting">Batal</Button>
          </DialogClose>
          <Button type="submit" :disabled="submitting || ordersLoading">
            {{ submitting ? 'Menyimpan...' : 'Buat tugas' }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
