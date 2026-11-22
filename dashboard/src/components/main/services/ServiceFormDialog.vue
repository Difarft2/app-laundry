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
import { Switch } from '@/components/ui/switch'
import { Textarea } from '@/components/ui/textarea'
import {
  SERVICE_FINISHING_OPTIONS,
  SERVICE_TYPE_OPTIONS,
  formatEstimasi,
} from '@/constants/services'
import { normalizeApiError } from '@/services/http'
import { serviceTimesApi } from '@/services/service-times'
import { servicesApi } from '@/services/services'
import { useAuthStore } from '@/stores/auth'
import type { CreateServicePayload, Service, ServiceTimeDetail } from '@/types/service'
import { translateFieldErrors } from './errors'
import {
  emptyServiceForm,
  validateServiceForm,
  type ServiceFormData,
  type ServiceFormMode,
} from './schema'

const props = defineProps<{
  open: boolean
  mode: ServiceFormMode
  service: Service | null
}>()

const emit = defineEmits<{
  (e: 'update:open', open: boolean): void
  (e: 'saved', service: Service, mode: ServiceFormMode): void
}>()

const auth = useAuthStore()
const canManageTimes = computed(() => auth.hasPermission('service-times.view'))

const form = reactive<ServiceFormData>(emptyServiceForm())
const touched = ref<Record<string, boolean>>({})
const submitted = ref(false)
const submitting = ref(false)
const serverError = ref<string | null>(null)
const fieldErrors = ref<Record<string, string>>({})

// Daftar opsi waktu layanan untuk dropdown berbasis nama.
const serviceTimes = ref<ServiceTimeDetail[]>([])
const serviceTimesLoading = ref(false)
const serviceTimesError = ref<string | null>(null)

/**
 * Nilai Select tidak boleh string kosong (dipakai reka-ui untuk placeholder),
 * jadi bentuk "tanpa estimasi" dipetakan ke sentinel khusus.
 */
const NO_TIME = '__none__'

const loadServiceTimes = async () => {
  serviceTimesLoading.value = true
  serviceTimesError.value = null

  try {
    const result = await serviceTimesApi.list({ is_active: true, per_page: 100 })
    serviceTimes.value = result.data
  } catch (error) {
    serviceTimesError.value = normalizeApiError(error).message
  } finally {
    serviceTimesLoading.value = false
  }
}

const isCreate = computed(() => props.mode === 'create')

/**
 * Sertakan waktu layanan yang sedang dipakai meski sudah nonaktif, agar
 * Select menampilkan nama yang benar saat mengedit.
 */
const timeOptions = computed<ServiceTimeDetail[]>(() => {
  const options = [...serviceTimes.value]
  const current = props.service?.waktu_layanan

  if (current && !options.some((option) => option.id === current.id)) {
    options.unshift({
      id: current.id,
      nama_waktu: current.nama_waktu,
      estimasi_min_jam: current.estimasi_min_jam,
      estimasi_max_jam: current.estimasi_max_jam,
      deskripsi: null,
      is_active: false,
      created_at: '',
      updated_at: '',
    })
  }

  return options
})

const selectedTimeLabel = computed(() => {
  const id = form.idWaktuLayanan
  if (!id || id === NO_TIME) return 'Tanpa estimasi'
  return (
    timeOptions.value.find((option) => String(option.id) === id)?.nama_waktu ?? 'Tanpa estimasi'
  )
})

const localErrors = computed(() => validateServiceForm(form))

const errorOf = (field: keyof ServiceFormData): string | undefined => {
  const serverMessage = fieldErrors.value[serverFieldKey(field)]
  if (serverMessage) return serverMessage
  if (!submitted.value && !touched.value[field]) return undefined
  return localErrors.value[field]
}

// Backend memakai snake_case; UI memakai camelCase. Petakan keduanya.
const serverFieldKey = (field: keyof ServiceFormData): string => {
  const mapping: Record<keyof ServiceFormData, string> = {
    namaLayanan: 'nama_layanan',
    idWaktuLayanan: 'id_waktu_layanan',
    jenisLayanan: 'jenis_layanan',
    jenisFinishing: 'jenis_finishing',
    harga: 'harga',
    deskripsi: 'deskripsi',
    isActive: 'is_active',
  }
  return mapping[field]
}

const touch = (field: keyof ServiceFormData) => {
  touched.value[field] = true
}

const clearServerError = () => {
  serverError.value = null
  fieldErrors.value = {}
}

watch(
  () => props.open,
  (open) => {
    if (!open) return

    submitted.value = false
    touched.value = {}
    serverError.value = null
    fieldErrors.value = {}

    if (props.mode === 'edit' && props.service) {
      Object.assign(form, {
        namaLayanan: props.service.nama_layanan,
        idWaktuLayanan: props.service.waktu_layanan
          ? String(props.service.waktu_layanan.id)
          : NO_TIME,
        jenisLayanan: props.service.jenis_layanan,
        jenisFinishing: props.service.jenis_finishing,
        harga: String(Number(props.service.harga)),
        deskripsi: props.service.deskripsi ?? '',
        isActive: props.service.is_active,
      })
    } else {
      Object.assign(form, emptyServiceForm())
      form.idWaktuLayanan = NO_TIME
    }

    // Selalu muat ulang agar waktu yang baru ditambahkan langsung tersedia.
    void loadServiceTimes()
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
    const deskripsi = form.deskripsi.trim()
    const idWaktu = form.idWaktuLayanan.trim()
    const hasTime = idWaktu && idWaktu !== NO_TIME

    const payload: CreateServicePayload = {
      nama_layanan: form.namaLayanan.trim(),
      id_waktu_layanan: hasTime ? Number(idWaktu) : null,
      jenis_layanan: form.jenisLayanan,
      jenis_finishing: form.jenisFinishing,
      harga: Number(String(form.harga).replace(',', '.')),
      deskripsi: deskripsi ? deskripsi : null,
      is_active: form.isActive,
    }

    const saved = isCreate.value
      ? await servicesApi.create(payload)
      : props.service
        ? await servicesApi.update(props.service.id, payload)
        : null

    if (!saved) return

    emit('saved', saved, props.mode)
    emit('update:open', false)
  } catch (error) {
    const apiError = normalizeApiError(error)
    const translated = translateFieldErrors(apiError.errors)

    // Ubah kembali snake_case ke camelCase agar cocok dengan `errorOf`.
    const mapping: Record<string, keyof ServiceFormData> = {
      nama_layanan: 'namaLayanan',
      id_waktu_layanan: 'idWaktuLayanan',
      jenis_layanan: 'jenisLayanan',
      jenis_finishing: 'jenisFinishing',
      harga: 'harga',
      deskripsi: 'deskripsi',
      is_active: 'isActive',
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
      class="max-h-[calc(100vh-3rem)] max-w-[calc(100%-2rem)] overflow-y-auto sm:max-w-xl"
    >
      <DialogHeader class="gap-2 pe-10 text-start">
        <DialogTitle class="text-xl font-semibold text-gray-800 dark:text-white/90">
          {{ isCreate ? 'Tambah layanan' : 'Edit layanan' }}
        </DialogTitle>
        <DialogDescription class="text-sm text-gray-500 dark:text-gray-400">
          {{ isCreate ? 'Tentukan detail layanan laundry yang baru.' : 'Perbarui detail layanan.' }}
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

        <Field :data-invalid="Boolean(errorOf('namaLayanan'))" class="gap-1.5">
          <FieldLabel for="service-name"
            >Nama layanan <span class="text-red-500">*</span></FieldLabel
          >
          <Input
            id="service-name"
            v-model="form.namaLayanan"
            placeholder="Contoh: Cuci Setrika Reguler"
            autocomplete="off"
            :aria-invalid="errorOf('namaLayanan') ? 'true' : undefined"
            @blur="touch('namaLayanan')"
            @input="clearServerError"
          />
          <FieldError :errors="[{ message: errorOf('namaLayanan') }]" />
        </Field>

        <div class="grid gap-5 sm:grid-cols-2">
          <Field :data-invalid="Boolean(errorOf('jenisLayanan'))" class="gap-1.5">
            <FieldLabel for="service-type"
              >Jenis layanan <span class="text-red-500">*</span></FieldLabel
            >
            <Select v-model="form.jenisLayanan">
              <SelectTrigger id="service-type" class="w-full" aria-label="Jenis layanan">
                <SelectValue placeholder="Pilih jenis layanan" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="option in SERVICE_TYPE_OPTIONS"
                  :key="option.value"
                  :value="option.value"
                >
                  {{ option.label }}
                </SelectItem>
              </SelectContent>
            </Select>
            <FieldError :errors="[{ message: errorOf('jenisLayanan') }]" />
          </Field>

          <Field :data-invalid="Boolean(errorOf('jenisFinishing'))" class="gap-1.5">
            <FieldLabel for="service-finishing"
              >Jenis finishing <span class="text-red-500">*</span></FieldLabel
            >
            <Select v-model="form.jenisFinishing">
              <SelectTrigger id="service-finishing" class="w-full" aria-label="Jenis finishing">
                <SelectValue placeholder="Pilih jenis finishing" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="option in SERVICE_FINISHING_OPTIONS"
                  :key="option.value"
                  :value="option.value"
                >
                  {{ option.label }}
                </SelectItem>
              </SelectContent>
            </Select>
            <FieldError :errors="[{ message: errorOf('jenisFinishing') }]" />
          </Field>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
          <Field :data-invalid="Boolean(errorOf('harga'))" class="gap-1.5">
            <FieldLabel for="service-price">Harga <span class="text-red-500">*</span></FieldLabel>
            <Input
              id="service-price"
              v-model="form.harga"
              type="text"
              inputmode="decimal"
              placeholder="Contoh: 7000"
              :aria-invalid="errorOf('harga') ? 'true' : undefined"
              @blur="touch('harga')"
              @input="clearServerError"
            />
            <FieldError :errors="[{ message: errorOf('harga') }]" />
            <p class="text-xs text-gray-400 dark:text-gray-500">
              Harga dalam Rupiah (tanpa titik atau koma pemisah ribuan).
            </p>
          </Field>

          <Field :data-invalid="Boolean(errorOf('idWaktuLayanan'))" class="gap-1.5">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <FieldLabel for="service-time">Waktu layanan</FieldLabel>
              <RouterLink
                v-if="canManageTimes"
                to="/service-times"
                class="text-xs font-medium text-brand-500 hover:underline dark:text-brand-400"
                @click="emit('update:open', false)"
              >
                Tambah / edit waktu
              </RouterLink>
            </div>
            <Select v-model="form.idWaktuLayanan" :disabled="serviceTimesLoading">
              <SelectTrigger id="service-time" class="w-full" aria-label="Waktu layanan">
                <SelectValue
                  :placeholder="serviceTimesLoading ? 'Memuat waktu...' : selectedTimeLabel"
                />
              </SelectTrigger>
              <SelectContent>
                <SelectItem :value="NO_TIME">Tanpa estimasi</SelectItem>
                <SelectItem
                  v-for="option in timeOptions"
                  :key="option.id"
                  :value="String(option.id)"
                >
                  {{ option.nama_waktu }} ·
                  {{ formatEstimasi(option.estimasi_min_jam, option.estimasi_max_jam) }}
                </SelectItem>
              </SelectContent>
            </Select>
            <FieldError :errors="[{ message: errorOf('idWaktuLayanan') }]" />
            <p v-if="serviceTimesError" class="text-xs text-error-500">
              {{ serviceTimesError }}
              <button type="button" class="font-medium underline" @click="loadServiceTimes">
                Muat ulang
              </button>
            </p>
            <p
              v-else-if="!serviceTimesLoading && timeOptions.length === 0"
              class="text-xs text-gray-400 dark:text-gray-500"
            >
              Belum ada waktu layanan.
              <RouterLink
                v-if="canManageTimes"
                to="/service-times"
                class="font-medium text-brand-500 hover:underline dark:text-brand-400"
                @click="emit('update:open', false)"
              >
                Tambah sekarang
              </RouterLink>
            </p>
            <p v-else class="text-xs text-gray-400 dark:text-gray-500">
              Opsional. Pilih estimasi pengerjaan atau "Tanpa estimasi" untuk melepas relasi.
            </p>
          </Field>
        </div>

        <Field :data-invalid="Boolean(errorOf('deskripsi'))" class="gap-1.5">
          <FieldLabel for="service-description">Deskripsi</FieldLabel>
          <Textarea
            id="service-description"
            v-model="form.deskripsi"
            rows="3"
            placeholder="Penjelasan singkat mengenai layanan (opsional)."
            :aria-invalid="errorOf('deskripsi') ? 'true' : undefined"
            @blur="touch('deskripsi')"
            @input="clearServerError"
          />
          <FieldError :errors="[{ message: errorOf('deskripsi') }]" />
        </Field>

        <Field
          orientation="horizontal"
          class="items-center justify-between rounded-xl border border-gray-200 px-4 py-3 dark:border-gray-800"
        >
          <div>
            <FieldLabel for="service-active" class="text-sm font-medium">Status aktif</FieldLabel>
            <p class="text-xs text-gray-500 dark:text-gray-400">
              Layanan nonaktif tidak dapat dipilih pada pesanan baru.
            </p>
          </div>
          <Switch id="service-active" v-model="form.isActive" />
        </Field>

        <DialogFooter class="mx-0 mb-0 gap-3 p-0 pt-2 sm:justify-end">
          <DialogClose as-child>
            <Button variant="outline" type="button" :disabled="submitting">Batal</Button>
          </DialogClose>
          <Button type="submit" :disabled="submitting">
            {{ submitting ? 'Menyimpan...' : isCreate ? 'Tambah layanan' : 'Simpan perubahan' }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
