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
import { formatEstimasi, serviceFinishingLabel, serviceTypeLabel } from '@/constants/services'
import { fetchActivePaymentMethods, type PaymentMethodOption } from '@/services/dashboard'
import { normalizeApiError } from '@/services/http'
import { ordersApi } from '@/services/orders'
import { servicesApi } from '@/services/services'
import type { CreateOrderPayload, Order } from '@/types/order'
import type { Service } from '@/types/service'
import { formatCurrency } from '@/utils/format'
import { translateFieldErrors } from './errors'
import { emptyOrderForm, toLocalDateTime, validateOrderForm, type OrderFormData } from './schema'

const props = defineProps<{ open: boolean }>()

const emit = defineEmits<{
  (e: 'update:open', open: boolean): void
  (e: 'saved', message: string): void
  (e: 'created', order: Order): void
}>()

const form = reactive<OrderFormData>(emptyOrderForm())
const touched = ref<Record<string, boolean>>({})
const submitted = ref(false)
const submitting = ref(false)
const serverError = ref<string | null>(null)
const fieldErrors = ref<Record<string, string>>({})

const services = ref<Service[]>([])
const servicesLoading = ref(false)
const servicesError = ref<string | null>(null)

const paymentMethods = ref<PaymentMethodOption[]>([])
const paymentMethodsLoading = ref(false)
const paymentMethodsError = ref<string | null>(null)

const selectedService = computed<Service | null>(
  () => services.value.find((service) => String(service.id) === form.idLayanan) ?? null,
)

interface ServiceGroup {
  key: string
  nama: string
  services: Service[]
}

const groupKeyOf = (service: Service): string => service.nama_layanan.trim().toLowerCase()

const durationHours = (service: Service): number => {
  const waktu = service.waktu_layanan
  return Number(waktu?.estimasi_max_jam ?? waktu?.estimasi_min_jam ?? 0)
}

/** Layanan dengan nama sama digabung; pilihan jam menentukan id_layanan asli. */
const serviceGroups = computed<ServiceGroup[]>(() => {
  const groups = new Map<string, Service[]>()

  for (const service of services.value) {
    const key = groupKeyOf(service)
    groups.set(key, [...(groups.get(key) ?? []), service])
  }

  return [...groups.entries()].map(([key, list]) => ({
    key,
    nama: list[0].nama_layanan,
    services: [...list].sort((a, b) => durationHours(a) - durationHours(b) || Number(a.harga) - Number(b.harga)),
  }))
})

const selectedGroupKey = ref('')
const selectedGroup = computed<ServiceGroup | null>(
  () => serviceGroups.value.find((group) => group.key === selectedGroupKey.value) ?? null,
)

const serviceUnit = (service: Service): string => (service.jenis_layanan === 'kiloan' ? 'kg' : 'item')

const serviceTimeName = (service: Service): string => service.waktu_layanan?.nama_waktu ?? 'Tanpa estimasi'

const onSelectGroup = (key: string) => {
  selectedGroupKey.value = key
  const group = serviceGroups.value.find((item) => item.key === key)
  form.idLayanan = group && group.services.length === 1 ? String(group.services[0].id) : ''
  clearServerError()
}

const layananError = computed(() => {
  const server = fieldErrors.value[serverFieldKey('idLayanan')]
  if (server) return server
  if (!submitted.value && !touched.value.idLayanan) return undefined
  if (!selectedGroupKey.value) return 'Layanan wajib dipilih.'
  if (!form.idLayanan) return 'Estimasi waktu wajib dipilih.'
  return undefined
})

watch(
  () => form.idLayanan,
  (value) => {
    if (!value) return
    const service = services.value.find((item) => String(item.id) === value)
    if (service) selectedGroupKey.value = groupKeyOf(service)
  },
)

const isSatuan = computed(() => selectedService.value?.jenis_layanan === 'satuan')

const jumlahLabel = computed(() => (isSatuan.value ? 'Jumlah item (pcs)' : 'Berat (kg)'))

const loadServices = async () => {
  servicesLoading.value = true
  servicesError.value = null

  try {
    const result = await servicesApi.list({ is_active: true, per_page: 100 })
    services.value = result.data
  } catch (error) {
    servicesError.value = normalizeApiError(error).message
  } finally {
    servicesLoading.value = false
  }
}

const loadPaymentMethods = async () => {
  paymentMethodsLoading.value = true
  paymentMethodsError.value = null

  try {
    paymentMethods.value = await fetchActivePaymentMethods()
  } catch (error) {
    paymentMethodsError.value = normalizeApiError(error).message
  } finally {
    paymentMethodsLoading.value = false
  }
}

const localErrors = computed(() => validateOrderForm(form, isSatuan.value))

const errorOf = (field: keyof OrderFormData): string | undefined => {
  const serverMessage = fieldErrors.value[serverFieldKey(field)]
  if (serverMessage) return serverMessage
  if (!submitted.value && !touched.value[field]) return undefined
  return localErrors.value[field]
}

const serverFieldKey = (field: keyof OrderFormData): string => {
  const mapping: Record<keyof OrderFormData, string> = {
    namaPelanggan: 'nama_pelanggan',
    noHp: 'no_hp',
    tanggalMasuk: 'tanggal_masuk',
    diskon: 'diskon',
    diskonTipe: 'diskon',
    idLayanan: 'detail_pesanan.0.id_layanan',
    jumlah: 'detail_pesanan.0.jumlah',
    catatanDetail: 'detail_pesanan.0.catatan',
    caraBayar: 'cara_bayar',
    idMetodePembayaran: 'id_metode_pembayaran',
    jumlahBayar: 'jumlah_bayar',
    statusPembayaran: 'status_pembayaran',
  }
  return mapping[field]
}

const touch = (field: keyof OrderFormData) => {
  touched.value[field] = true
}

const clearServerError = () => {
  serverError.value = null
  fieldErrors.value = {}
}

const subtotal = computed(() => {
  const service = selectedService.value
  if (!service) return null
  const jumlah = Number(String(form.jumlah).replace(',', '.')) || 0
  return Number(service.harga) * jumlah
})

/** Nominal diskon akhir (Rupiah) yang dikirim ke backend. */
const diskonNominal = computed(() => {
  const raw = Number(String(form.diskon).replace(',', '.')) || 0
  if (form.diskonTipe === 'persen') {
    return Math.round(((subtotal.value ?? 0) * raw) / 100)
  }
  return raw
})

const estimatedTotal = computed(() => {
  if (subtotal.value === null) return null
  return Math.max(0, subtotal.value - diskonNominal.value)
})

const hoursToDays = (jam: number | null | undefined): number | null =>
  jam != null && jam > 0 ? Math.ceil(jam / 24) : null

/** Durasi layanan dalam jam dan hari, mis. "2–3 hari (48–72 jam)". */
const estimasiLayananLabel = (service: Service): string => {
  const waktu = service.waktu_layanan
  if (!waktu) return 'Estimasi belum diatur'

  const jamLabel = formatEstimasi(waktu.estimasi_min_jam, waktu.estimasi_max_jam)
  const minHari = hoursToDays(waktu.estimasi_min_jam)
  const maxHari = hoursToDays(waktu.estimasi_max_jam)

  if (minHari && maxHari) {
    return minHari === maxHari
      ? `${minHari} hari (${jamLabel})`
      : `${minHari}–${maxHari} hari (${jamLabel})`
  }

  const hari = minHari ?? maxHari
  return hari ? `${hari} hari (${jamLabel})` : jamLabel
}

/** Estimasi jam terlama dipakai agar preview sama dengan nilai di database. */
const estimasiMaxJam = computed(() => {
  const waktu = selectedService.value?.waktu_layanan
  if (!waktu) return null

  const max = Number(waktu.estimasi_max_jam ?? 0)
  const min = Number(waktu.estimasi_min_jam ?? 0)

  return max > 0 ? max : min > 0 ? min : null
})

const estimasiSelesai = computed<Date | null>(() => {
  const jam = estimasiMaxJam.value
  if (!jam || !form.tanggalMasuk) return null

  const tanggal = new Date(form.tanggalMasuk)
  if (Number.isNaN(tanggal.getTime())) return null

  tanggal.setHours(tanggal.getHours() + jam)
  return tanggal
})

const estimasiSelesaiLabel = computed(() => {
  if (!estimasiSelesai.value) return '—'

  return new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(estimasiSelesai.value)
})

const estimasiDurasiLabel = computed(() =>
  selectedService.value ? estimasiLayananLabel(selectedService.value) : '—',
)

watch(
  () => estimatedTotal.value,
  (total) => {
    form.jumlahBayar = total === null ? '' : String(Math.max(0, total))
  },
)

watch(
  () => props.open,
  (open) => {
    if (!open) return

    Object.assign(form, emptyOrderForm())
    selectedGroupKey.value = ''
    submitted.value = false
    touched.value = {}
    serverError.value = null
    fieldErrors.value = {}

    void loadServices()
    void loadPaymentMethods()
  },
  { immediate: true },
)

const onSubmit = async (event?: Event) => {
  event?.preventDefault()
  submitted.value = true
  clearServerError()

  if (Object.keys(localErrors.value).length > 0) return

  submitting.value = true

  try {
    const catatanDetail = form.catatanDetail.trim()
    const diskon = diskonNominal.value
    const bayarSekarang = form.caraBayar === 'now'

    const payload: CreateOrderPayload = {
      nama_pelanggan: form.namaPelanggan.trim(),
      no_hp: form.noHp.trim(),
      tanggal_masuk: toLocalDateTime(form.tanggalMasuk),
      diskon,
      detail_pesanan: [
        {
          id_layanan: Number(form.idLayanan),
          jumlah: Number(String(form.jumlah).replace(',', '.')),
          catatan: catatanDetail ? catatanDetail : null,
        },
      ],
      cara_bayar: form.caraBayar,
      id_metode_pembayaran: bayarSekarang ? Number(form.idMetodePembayaran) : null,
      jumlah_bayar: bayarSekarang ? Number(String(form.jumlahBayar).replace(',', '.')) : null,
      status_pembayaran: bayarSekarang ? form.statusPembayaran : 'pending',
    }

    const saved = await ordersApi.create(payload)
    emit('saved', `Pesanan ${saved.kode_pesanan} berhasil dibuat.`)
    emit('created', saved)
    emit('update:open', false)
  } catch (error) {
    const apiError = normalizeApiError(error)
    const translated = translateFieldErrors(apiError.errors)

    const mapping: Record<string, keyof OrderFormData> = {
      nama_pelanggan: 'namaPelanggan',
      no_hp: 'noHp',
      tanggal_masuk: 'tanggalMasuk',
      diskon: 'diskon',
      'detail_pesanan.0.id_layanan': 'idLayanan',
      'detail_pesanan.0.jumlah': 'jumlah',
      'detail_pesanan.0.catatan': 'catatanDetail',
      id_metode_pembayaran: 'idMetodePembayaran',
      jumlah_bayar: 'jumlahBayar',
      status_pembayaran: 'statusPembayaran',
    }

    fieldErrors.value = Object.fromEntries(
      Object.entries(translated).map(([key, value]) => [mapping[key] ?? key, value]),
    )

    if (Object.keys(fieldErrors.value).length === 0) {
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
      class="max-h-[calc(100vh-1.5rem)] max-w-[calc(100%-1rem)] overflow-y-auto p-4 sm:max-h-[calc(100vh-3rem)] sm:max-w-2xl sm:p-6"
    >
      <DialogHeader class="gap-2 pe-10 text-start">
        <DialogTitle class="text-xl font-semibold text-gray-800 dark:text-white/90">
          Buat pesanan baru
        </DialogTitle>
        <DialogDescription class="text-sm text-gray-500 dark:text-gray-400">
          Satu nota hanya untuk satu layanan. Untuk layanan berbeda, buat nota terpisah.
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

        <fieldset class="space-y-4">
          <legend class="text-sm font-semibold text-gray-800 dark:text-white/90">Data pelanggan</legend>

          <div class="grid gap-5 sm:grid-cols-2">
            <Field :data-invalid="Boolean(errorOf('namaPelanggan'))" class="gap-1.5">
              <FieldLabel for="order-customer">Nama pelanggan <span class="text-red-500">*</span></FieldLabel>
              <Input
                id="order-customer"
                v-model="form.namaPelanggan"
                placeholder="Contoh: Budi"
                autocomplete="off"
                :aria-invalid="errorOf('namaPelanggan') ? 'true' : undefined"
                @blur="touch('namaPelanggan')"
                @input="clearServerError"
              />
              <FieldError :errors="[{ message: errorOf('namaPelanggan') }]" />
            </Field>

            <Field :data-invalid="Boolean(errorOf('noHp'))" class="gap-1.5">
              <FieldLabel for="order-phone">Nomor HP <span class="text-red-500">*</span></FieldLabel>
              <Input
                id="order-phone"
                v-model="form.noHp"
                type="tel"
                inputmode="tel"
                placeholder="Contoh: 081234567890"
                autocomplete="off"
                :aria-invalid="errorOf('noHp') ? 'true' : undefined"
                @blur="touch('noHp')"
                @input="clearServerError"
              />
              <FieldError :errors="[{ message: errorOf('noHp') }]" />
            </Field>
          </div>
        </fieldset>

        <fieldset class="space-y-4">
          <legend class="text-sm font-semibold text-gray-800 dark:text-white/90">Layanan</legend>

          <Field :data-invalid="Boolean(layananError)" class="gap-1.5">
            <FieldLabel for="order-service">Layanan <span class="text-red-500">*</span></FieldLabel>
            <Select
              :model-value="selectedGroupKey"
              :disabled="servicesLoading"
              @update:model-value="onSelectGroup($event as string)"
            >
              <SelectTrigger id="order-service" class="w-full" aria-label="Layanan">
                <SelectValue :placeholder="servicesLoading ? 'Memuat layanan...' : 'Pilih layanan'" />
              </SelectTrigger>
              <SelectContent class="max-w-[calc(100vw-2rem)]">
                <SelectItem
                  v-for="group in serviceGroups"
                  :key="group.key"
                  :value="group.key"
                  multiline
                >
                  <span class="flex min-w-0 flex-col gap-0.5 text-start">
                    <span class="wrap-break-word font-semibold">{{ group.nama }}</span>
                    <span class="wrap-break-word text-xs font-normal text-gray-500 dark:text-gray-400">
                      <template v-if="group.services.length > 1">
                        {{ group.services.length }} pilihan waktu
                      </template>
                      <template v-else>
                        {{ formatCurrency(Number(group.services[0].harga)) }}/{{ serviceUnit(group.services[0]) }}
                        · {{ estimasiLayananLabel(group.services[0]) }}
                      </template>
                    </span>
                  </span>
                </SelectItem>
              </SelectContent>
            </Select>
            <p v-if="servicesError" class="text-xs text-error-500">
              {{ servicesError }}
              <button type="button" class="font-medium underline" @click="loadServices">Muat ulang</button>
            </p>
            <p
              v-else-if="!servicesLoading && services.length === 0"
              class="text-xs text-gray-400 dark:text-gray-500"
            >
              Belum ada layanan aktif. Tambahkan layanan terlebih dahulu.
            </p>
          </Field>

          <Field v-if="selectedGroup" :data-invalid="Boolean(layananError)" class="gap-1.5">
            <FieldLabel for="order-service-time">
              Estimasi waktu <span class="text-red-500">*</span>
            </FieldLabel>
            <Select v-model="form.idLayanan">
              <SelectTrigger id="order-service-time" class="w-full" aria-label="Estimasi waktu">
                <SelectValue placeholder="Pilih estimasi waktu" />
              </SelectTrigger>
              <SelectContent class="max-w-[calc(100vw-2rem)]">
                <SelectItem
                  v-for="service in selectedGroup.services"
                  :key="service.id"
                  :value="String(service.id)"
                  multiline
                >
                  <span class="flex min-w-0 flex-col gap-0.5 text-start">
                    <span class="wrap-break-word font-semibold">
                      {{ serviceTimeName(service) }} ·
                      {{ formatEstimasi(service.waktu_layanan?.estimasi_min_jam, service.waktu_layanan?.estimasi_max_jam) }}
                    </span>
                    <span class="wrap-break-word text-xs font-normal text-gray-500 dark:text-gray-400">
                      {{ formatCurrency(Number(service.harga)) }}/{{ serviceUnit(service) }}
                    </span>
                  </span>
                </SelectItem>
              </SelectContent>
            </Select>
            <FieldError :errors="[{ message: layananError }]" />
          </Field>
          <FieldError v-else :errors="[{ message: layananError }]" />

          <div
            v-if="selectedService"
            class="min-w-0 space-y-2.5 rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-3 dark:border-gray-700 dark:bg-white/3 sm:px-4"
          >
            <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-1">
              <p class="min-w-0 wrap-break-word font-semibold text-gray-800 dark:text-white/90">
                {{ selectedService.nama_layanan }}
              </p>
              <p class="shrink-0 font-semibold text-brand-600 dark:text-brand-400">
                {{ formatCurrency(Number(selectedService.harga)) }}/{{
                  selectedService.jenis_layanan === 'kiloan' ? 'kg' : 'item'
                }}
              </p>
            </div>

            <div class="flex flex-wrap gap-1.5">
              <span
                class="rounded-md bg-white px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-white/5 dark:text-gray-300"
              >
                {{ serviceTypeLabel(selectedService.jenis_layanan) }}
              </span>
              <span
                class="rounded-md bg-white px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-white/5 dark:text-gray-300"
              >
                Finishing: {{ serviceFinishingLabel(selectedService.jenis_finishing) }}
              </span>
            </div>

            <p
              v-if="selectedService.deskripsi"
              class="wrap-break-word text-xs leading-relaxed text-gray-600 dark:text-gray-400"
            >
              {{ selectedService.deskripsi }}
            </p>

            <dl class="grid gap-x-4 gap-y-2 border-t border-gray-200 pt-2.5 text-xs dark:border-gray-700 sm:grid-cols-2">
              <div class="min-w-0">
                <dt class="text-gray-500 dark:text-gray-400">Lama pengerjaan</dt>
                <dd class="mt-0.5 wrap-break-word font-medium text-gray-800 dark:text-white/90">
                  {{ estimasiDurasiLabel }}
                </dd>
              </div>
              <div class="min-w-0">
                <dt class="text-gray-500 dark:text-gray-400">Estimasi selesai</dt>
                <dd class="mt-0.5 wrap-break-word font-medium text-gray-800 dark:text-white/90">
                  {{ estimasiSelesaiLabel }}
                </dd>
              </div>
            </dl>

            <p class="text-[11px] leading-relaxed text-gray-400 dark:text-gray-500">
              Estimasi dihitung otomatis dari tanggal masuk dan durasi layanan.
            </p>
          </div>

          <div class="grid gap-5 sm:grid-cols-2">
            <Field :data-invalid="Boolean(errorOf('jumlah'))" class="gap-1.5">
              <FieldLabel for="order-quantity">{{ jumlahLabel }} <span class="text-red-500">*</span></FieldLabel>
              <Input
                id="order-quantity"
                v-model="form.jumlah"
                type="text"
                inputmode="decimal"
                placeholder="0"
                :aria-invalid="errorOf('jumlah') ? 'true' : undefined"
                @blur="touch('jumlah')"
                @input="clearServerError"
              />
              <FieldError :errors="[{ message: errorOf('jumlah') }]" />
            </Field>

            <Field :data-invalid="Boolean(errorOf('tanggalMasuk'))" class="gap-1.5">
              <FieldLabel for="order-entry">Tanggal masuk <span class="text-red-500">*</span></FieldLabel>
              <Input
                id="order-entry"
                v-model="form.tanggalMasuk"
                type="datetime-local"
                :aria-invalid="errorOf('tanggalMasuk') ? 'true' : undefined"
                @blur="touch('tanggalMasuk')"
                @input="clearServerError"
              />
              <FieldError :errors="[{ message: errorOf('tanggalMasuk') }]" />
            </Field>
          </div>

          <Field :data-invalid="Boolean(errorOf('catatanDetail'))" class="gap-1.5">
            <FieldLabel for="order-service-note">Catatan layanan</FieldLabel>
            <Input
              id="order-service-note"
              v-model="form.catatanDetail"
              placeholder="Contoh: Pisahkan pakaian putih"
              autocomplete="off"
              @input="clearServerError"
            />
            <FieldError :errors="[{ message: errorOf('catatanDetail') }]" />
          </Field>
        </fieldset>

        <fieldset class="space-y-4">
          <legend class="text-sm font-semibold text-gray-800 dark:text-white/90">Pembayaran</legend>

          <div class="flex flex-wrap gap-5">
            <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
              <input v-model="form.caraBayar" type="radio" name="cara_bayar" value="later" class="accent-brand-500" />
              Bayar nanti
            </label>
            <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
              <input v-model="form.caraBayar" type="radio" name="cara_bayar" value="now" class="accent-brand-500" />
              Bayar sekarang
            </label>
          </div>

          <template v-if="form.caraBayar === 'now'">
            <Field :data-invalid="Boolean(errorOf('idMetodePembayaran'))" class="gap-1.5">
              <FieldLabel for="order-payment-method">Metode pembayaran <span class="text-red-500">*</span></FieldLabel>
              <Select
                v-model="form.idMetodePembayaran"
                :disabled="paymentMethodsLoading"
              >
                <SelectTrigger id="order-payment-method" class="w-full" aria-label="Metode pembayaran">
                  <SelectValue
                    :placeholder="paymentMethodsLoading ? 'Memuat metode...' : 'Pilih metode pembayaran'"
                  />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="method in paymentMethods"
                    :key="method.id"
                    :value="String(method.id)"
                  >
                    {{ method.nama_metode }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <FieldError :errors="[{ message: errorOf('idMetodePembayaran') }]" />
              <p v-if="paymentMethodsError" class="text-xs text-error-500">
                {{ paymentMethodsError }}
                <button type="button" class="font-medium underline" @click="loadPaymentMethods">
                  Muat ulang
                </button>
              </p>
              <p
                v-else-if="!paymentMethodsLoading && paymentMethods.length === 0"
                class="text-xs text-gray-400 dark:text-gray-500"
              >
                Belum ada metode pembayaran aktif.
              </p>
            </Field>

            <div class="grid gap-5 sm:grid-cols-2">
              <Field :data-invalid="Boolean(errorOf('jumlahBayar'))" class="gap-1.5">
                <FieldLabel for="order-paid-amount">Jumlah bayar</FieldLabel>
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 dark:border-gray-700 dark:bg-white/5">
                  <p id="order-paid-amount" class="text-sm font-semibold text-gray-800 dark:text-white/90">
                    {{ estimatedTotal === null ? '—' : formatCurrency(estimatedTotal) }}
                  </p>
                  <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                    Otomatis mengikuti total pesanan.
                  </p>
                </div>
                <FieldError :errors="[{ message: errorOf('jumlahBayar') }]" />
              </Field>

              <Field :data-invalid="Boolean(errorOf('statusPembayaran'))" class="gap-1.5">
                <FieldLabel for="order-payment-status">Status pembayaran</FieldLabel>
                <Select v-model="form.statusPembayaran">
                  <SelectTrigger id="order-payment-status" class="w-full" aria-label="Status pembayaran">
                    <SelectValue placeholder="Pilih status" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="pending">Menunggu konfirmasi</SelectItem>
                    <SelectItem value="berhasil">Berhasil</SelectItem>
                  </SelectContent>
                </Select>
                <FieldError :errors="[{ message: errorOf('statusPembayaran') }]" />
              </Field>
            </div>
          </template>

          <p v-else class="text-xs text-gray-500 dark:text-gray-400">
            Pembayaran akan dicatat kemudian. Status pesanan tetap menunggu.
          </p>
        </fieldset>

        <fieldset class="space-y-4">
          <legend class="text-sm font-semibold text-gray-800 dark:text-white/90">Ringkasan</legend>

          <div class="grid gap-5 sm:grid-cols-2">
            <Field :data-invalid="Boolean(errorOf('diskon'))" class="gap-1.5">
              <FieldLabel for="order-discount">
                Diskon {{ form.diskonTipe === 'persen' ? '(%)' : '(Rp)' }}
              </FieldLabel>
              <div class="flex gap-2">
                <Select v-model="form.diskonTipe">
                  <SelectTrigger class="w-24 shrink-0" aria-label="Tipe diskon">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="nominal">Rp</SelectItem>
                    <SelectItem value="persen">%</SelectItem>
                  </SelectContent>
                </Select>
                <Input
                  id="order-discount"
                  v-model="form.diskon"
                  type="text"
                  inputmode="decimal"
                  placeholder="0"
                  :aria-invalid="errorOf('diskon') ? 'true' : undefined"
                  @blur="touch('diskon')"
                  @input="clearServerError"
                />
              </div>
              <p v-if="form.diskonTipe === 'persen' && diskonNominal > 0" class="text-xs text-gray-500 dark:text-gray-400">
                Setara {{ formatCurrency(diskonNominal) }}
              </p>
              <FieldError :errors="[{ message: errorOf('diskon') }]" />
            </Field>

            <div
              class="flex flex-col justify-center rounded-xl border border-gray-200 px-4 py-3 dark:border-gray-800"
            >
              <p class="text-xs text-gray-500 dark:text-gray-400">Estimasi total</p>
              <p class="text-lg font-semibold text-gray-800 dark:text-white/90">
                {{ estimatedTotal === null ? '—' : formatCurrency(estimatedTotal) }}
              </p>
              <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                Perhitungan akhir ditentukan backend.
              </p>
            </div>
          </div>

          <div class="grid gap-3 sm:grid-cols-2">
            <div class="min-w-0 rounded-xl border border-gray-200 px-4 py-3 dark:border-gray-800">
              <p class="text-xs text-gray-500 dark:text-gray-400">Lama pengerjaan</p>
              <p class="mt-0.5 wrap-break-word text-sm font-semibold text-gray-800 dark:text-white/90">
                {{ estimasiDurasiLabel }}
              </p>
            </div>
            <div class="min-w-0 rounded-xl border border-gray-200 px-4 py-3 dark:border-gray-800">
              <p class="text-xs text-gray-500 dark:text-gray-400">Estimasi selesai</p>
              <p class="mt-0.5 wrap-break-word text-sm font-semibold text-gray-800 dark:text-white/90">
                {{ estimasiSelesaiLabel }}
              </p>
              <p class="mt-0.5 text-[11px] text-gray-400 dark:text-gray-500">
                Disimpan pada pesanan.
              </p>
            </div>
          </div>
        </fieldset>

        <DialogFooter class="mx-0 mb-0 gap-3 p-0 pt-2 sm:justify-end">
          <DialogClose as-child>
            <Button variant="outline" type="button" :disabled="submitting">Batal</Button>
          </DialogClose>
          <Button type="submit" :disabled="submitting || servicesLoading">
            {{ submitting ? 'Menyimpan...' : 'Buat pesanan' }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
