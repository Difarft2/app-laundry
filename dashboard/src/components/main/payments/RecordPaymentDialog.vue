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
import {
  PAYMENT_STATUS_OPTIONS,
  PAYMENT_TYPE_OPTIONS,
} from '@/constants/payments'
import { fetchActivePaymentMethods, type PaymentMethodOption } from '@/services/dashboard'
import { normalizeApiError, type ApiFieldErrors } from '@/services/http'
import { paymentsApi } from '@/services/payments'
import type { Order } from '@/types/order'
import type { PaymentType } from '@/types/payment'
import { formatCurrency } from '@/utils/format'

const props = defineProps<{
  open: boolean
  order: Order | null
}>()

const emit = defineEmits<{
  (e: 'update:open', open: boolean): void
  (e: 'saved', message: string): void
}>()

interface PaymentFormData {
  idMetodePembayaran: string
  jenisPembayaran: PaymentType
  jumlahBayar: string
  statusPembayaran: 'berhasil' | 'menunggu'
  tanggalPembayaran: string
  catatan: string
}

const defaultDateTime = (): string => {
  const now = new Date()
  const pad = (value: number) => String(value).padStart(2, '0')
  return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`
}

const toBackendDateTime = (value: string): string => {
  const normalized = value.trim().replace('T', ' ')
  return normalized.length === 16 ? `${normalized}:00` : normalized
}

const emptyForm = (): PaymentFormData => ({
  idMetodePembayaran: '',
  jenisPembayaran: 'pelunasan',
  jumlahBayar: '',
  statusPembayaran: 'berhasil',
  tanggalPembayaran: defaultDateTime(),
  catatan: '',
})

const form = reactive<PaymentFormData>(emptyForm())
const touched = ref<Record<string, boolean>>({})
const submitted = ref(false)
const submitting = ref(false)
const serverError = ref<string | null>(null)
const fieldErrors = ref<Record<string, string>>({})

const methods = ref<PaymentMethodOption[]>([])
const methodsLoading = ref(false)
const methodsError = ref<string | null>(null)

const sisaTagihan = computed(() => Math.max(0, Number(props.order?.sisa_pembayaran ?? 0)))

const statusOptions = PAYMENT_STATUS_OPTIONS.filter(
  (option) => option.value === 'berhasil' || option.value === 'menunggu',
)

const loadMethods = async () => {
  methodsLoading.value = true
  methodsError.value = null

  try {
    methods.value = await fetchActivePaymentMethods()
  } catch (error) {
    methodsError.value = normalizeApiError(error).message
  } finally {
    methodsLoading.value = false
  }
}

const localErrors = computed(() => {
  const errors: Record<string, string> = {}

  if (!form.idMetodePembayaran) {
    errors.idMetodePembayaran = 'Metode pembayaran wajib dipilih.'
  }

  const jumlah = Number(String(form.jumlahBayar).replace(',', '.'))
  if (!String(form.jumlahBayar).trim() || Number.isNaN(jumlah) || jumlah <= 0) {
    errors.jumlahBayar = 'Jumlah bayar wajib lebih dari 0.'
  } else if (jumlah > sisaTagihan.value) {
    errors.jumlahBayar = 'Jumlah bayar tidak boleh melebihi sisa tagihan.'
  } else if (
    form.jenisPembayaran === 'pelunasan' &&
    Math.abs(jumlah - sisaTagihan.value) > 0.01
  ) {
    errors.jumlahBayar = 'Pelunasan harus sama dengan sisa tagihan.'
  }

  if (!form.tanggalPembayaran) {
    errors.tanggalPembayaran = 'Tanggal pembayaran wajib diisi.'
  }

  return errors
})

const errorOf = (field: keyof PaymentFormData): string | undefined => {
  const serverMessage = fieldErrors.value[field]
  if (serverMessage) return serverMessage
  if (!submitted.value && !touched.value[field]) return undefined
  return localErrors.value[field]
}

const touch = (field: keyof PaymentFormData) => {
  touched.value[field] = true
}

const firstMessages = (errors?: ApiFieldErrors): Record<string, string> => {
  const result: Record<string, string> = {}
  if (!errors) return result

  for (const [field, messages] of Object.entries(errors)) {
    const first = messages?.[0]
    if (first) result[field] = first
  }

  return result
}

const clearServerError = () => {
  serverError.value = null
  fieldErrors.value = {}
}

watch(
  () => props.open,
  (open) => {
    if (!open) return

    Object.assign(form, emptyForm())
    form.jumlahBayar = sisaTagihan.value > 0 ? String(sisaTagihan.value) : ''
    submitted.value = false
    touched.value = {}
    clearServerError()
    void loadMethods()
  },
  { immediate: true },
)

watch(
  () => [form.jenisPembayaran, sisaTagihan.value],
  ([jenis]) => {
    if (props.open && jenis === 'pelunasan') {
      form.jumlahBayar = sisaTagihan.value > 0 ? String(sisaTagihan.value) : ''
    }
  },
)

const onSubmit = async (event?: Event) => {
  event?.preventDefault()
  submitted.value = true
  clearServerError()

  if (!props.order) return
  if (Object.keys(localErrors.value).length > 0) return

  submitting.value = true

  try {
    const saved = await paymentsApi.create({
      id_pesanan: props.order.id_pesanan,
      id_metode_pembayaran: Number(form.idMetodePembayaran),
      jumlah_bayar: Number(String(form.jumlahBayar).replace(',', '.')),
      jenis_pembayaran: form.jenisPembayaran,
      status_pembayaran: form.statusPembayaran,
      tanggal_pembayaran: toBackendDateTime(form.tanggalPembayaran),
      catatan: form.catatan.trim() || null,
    })

    emit('saved', `Pembayaran ${saved.kode_pembayaran} berhasil dicatat.`)
    emit('update:open', false)
  } catch (error) {
    const apiError = normalizeApiError(error)

    const mapping: Record<string, keyof PaymentFormData> = {
      id_metode_pembayaran: 'idMetodePembayaran',
      jumlah_bayar: 'jumlahBayar',
      jenis_pembayaran: 'jenisPembayaran',
      status_pembayaran: 'statusPembayaran',
      tanggal_pembayaran: 'tanggalPembayaran',
      catatan: 'catatan',
    }

    const mappedErrors: Record<string, string> = {}
    const generalErrors: string[] = []

    for (const [key, value] of Object.entries(firstMessages(apiError.errors))) {
      const field = mapping[key]
      if (field) mappedErrors[field] = value
      else generalErrors.push(value)
    }

    fieldErrors.value = mappedErrors

    if (generalErrors.length > 0) {
      serverError.value = generalErrors.join(' ')
    } else if (Object.keys(mappedErrors).length === 0) {
      serverError.value = apiError.message
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent
      class="max-h-[calc(100vh-1.5rem)] max-w-[calc(100%-1rem)] overflow-y-auto p-4 sm:max-h-[calc(100vh-3rem)] sm:max-w-lg sm:p-6"
    >
      <DialogHeader class="gap-2 pe-10 text-start">
        <DialogTitle class="text-xl font-semibold text-gray-800 dark:text-white/90">
          Catat pembayaran
        </DialogTitle>
        <DialogDescription class="text-sm text-gray-500 dark:text-gray-400">
          Catat DP, angsuran, atau pelunasan untuk pesanan yang dibayar nanti.
        </DialogDescription>
      </DialogHeader>

      <form class="space-y-5" novalidate @submit="onSubmit">
        <div
          v-if="order"
          class="min-w-0 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-white/3"
        >
          <p class="text-xs text-gray-500 dark:text-gray-400">Pesanan</p>
          <p class="mt-0.5 break-words text-sm font-semibold text-gray-800 dark:text-white/90">
            {{ order.kode_pesanan }} · {{ order.pelanggan.nama }}
          </p>
          <div class="mt-2 flex flex-wrap gap-x-6 gap-y-1 text-xs">
            <span class="text-gray-500 dark:text-gray-400">
              Total:
              <span class="font-medium text-gray-700 dark:text-gray-200">
                {{ formatCurrency(order.total_harga) }}
              </span>
            </span>
            <span class="text-gray-500 dark:text-gray-400">
              Sisa:
              <span class="font-semibold text-brand-600 dark:text-brand-400">
                {{ formatCurrency(sisaTagihan) }}
              </span>
            </span>
          </div>
        </div>

        <div
          v-if="serverError"
          role="alert"
          class="rounded-lg border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-700 dark:border-error-500/20 dark:bg-error-500/10 dark:text-error-400"
        >
          {{ serverError }}
        </div>

        <Field :data-invalid="Boolean(errorOf('idMetodePembayaran'))" class="gap-1.5">
          <FieldLabel for="payment-method">Metode pembayaran <span class="text-red-500">*</span></FieldLabel>
          <Select v-model="form.idMetodePembayaran" :disabled="methodsLoading">
            <SelectTrigger id="payment-method" class="w-full" aria-label="Metode pembayaran">
              <SelectValue
                :placeholder="methodsLoading ? 'Memuat metode...' : 'Pilih metode pembayaran'"
              />
            </SelectTrigger>
            <SelectContent>
              <SelectItem
                v-for="method in methods"
                :key="method.id"
                :value="String(method.id)"
              >
                {{ method.nama_metode }} ({{ method.kode }})
              </SelectItem>
            </SelectContent>
          </Select>
          <FieldError :errors="[{ message: errorOf('idMetodePembayaran') }]" />
          <p v-if="methodsError" class="text-xs text-error-500">
            {{ methodsError }}
            <button type="button" class="font-medium underline" @click="loadMethods">Muat ulang</button>
          </p>
          <p v-else-if="!methodsLoading && methods.length === 0" class="text-xs text-gray-400 dark:text-gray-500">
            Belum ada metode pembayaran aktif.
          </p>
        </Field>

        <div class="grid gap-5 sm:grid-cols-2">
          <Field :data-invalid="Boolean(errorOf('jenisPembayaran'))" class="gap-1.5">
            <FieldLabel for="payment-type">Jenis pembayaran</FieldLabel>
            <Select v-model="form.jenisPembayaran">
              <SelectTrigger id="payment-type" class="w-full" aria-label="Jenis pembayaran">
                <SelectValue placeholder="Pilih jenis" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="option in PAYMENT_TYPE_OPTIONS"
                  :key="option.value"
                  :value="option.value"
                >
                  {{ option.label }}
                </SelectItem>
              </SelectContent>
            </Select>
            <FieldError :errors="[{ message: errorOf('jenisPembayaran') }]" />
          </Field>

          <Field :data-invalid="Boolean(errorOf('statusPembayaran'))" class="gap-1.5">
            <FieldLabel for="payment-status">Status pembayaran</FieldLabel>
            <Select v-model="form.statusPembayaran">
              <SelectTrigger id="payment-status" class="w-full" aria-label="Status pembayaran">
                <SelectValue placeholder="Pilih status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="option in statusOptions"
                  :key="option.value"
                  :value="option.value"
                >
                  {{ option.label }}
                </SelectItem>
              </SelectContent>
            </Select>
            <FieldError :errors="[{ message: errorOf('statusPembayaran') }]" />
          </Field>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
          <Field :data-invalid="Boolean(errorOf('jumlahBayar'))" class="gap-1.5">
            <FieldLabel for="payment-amount">Jumlah bayar <span class="text-red-500">*</span></FieldLabel>
            <Input
              id="payment-amount"
              v-model="form.jumlahBayar"
              type="text"
              inputmode="decimal"
              placeholder="0"
              :aria-invalid="errorOf('jumlahBayar') ? 'true' : undefined"
              @blur="touch('jumlahBayar')"
              @input="clearServerError"
            />
            <FieldError :errors="[{ message: errorOf('jumlahBayar') }]" />
          </Field>

          <Field :data-invalid="Boolean(errorOf('tanggalPembayaran'))" class="gap-1.5">
            <FieldLabel for="payment-date">Tanggal pembayaran <span class="text-red-500">*</span></FieldLabel>
            <Input
              id="payment-date"
              v-model="form.tanggalPembayaran"
              type="datetime-local"
              :aria-invalid="errorOf('tanggalPembayaran') ? 'true' : undefined"
              @blur="touch('tanggalPembayaran')"
              @input="clearServerError"
            />
            <FieldError :errors="[{ message: errorOf('tanggalPembayaran') }]" />
          </Field>
        </div>

        <Field :data-invalid="Boolean(errorOf('catatan'))" class="gap-1.5">
          <FieldLabel for="payment-note">Catatan</FieldLabel>
          <Textarea
            id="payment-note"
            v-model="form.catatan"
            rows="2"
            placeholder="Opsional, mis. Bayar tunai di counter"
            @input="clearServerError"
          />
          <FieldError :errors="[{ message: errorOf('catatan') }]" />
        </Field>

        <DialogFooter class="mx-0 mb-0 gap-3 p-0 pt-2 sm:justify-end">
          <DialogClose as-child>
            <Button variant="outline" type="button" :disabled="submitting">Batal</Button>
          </DialogClose>
          <Button type="submit" :disabled="submitting || methodsLoading || sisaTagihan <= 0">
            {{ submitting ? 'Menyimpan...' : 'Simpan pembayaran' }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
