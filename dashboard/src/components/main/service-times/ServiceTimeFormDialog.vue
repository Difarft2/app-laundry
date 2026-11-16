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
import { Switch } from '@/components/ui/switch'
import { Textarea } from '@/components/ui/textarea'
import { formatEstimasi } from '@/constants/services'
import { normalizeApiError } from '@/services/http'
import { serviceTimesApi } from '@/services/service-times'
import type { CreateServiceTimePayload, ServiceTimeDetail } from '@/types/service'
import { translateFieldErrors } from './errors'
import {
  emptyServiceTimeForm,
  validateServiceTimeForm,
  type ServiceTimeFormData,
  type ServiceTimeFormMode,
} from './schema'

const props = defineProps<{
  open: boolean
  mode: ServiceTimeFormMode
  serviceTime: ServiceTimeDetail | null
}>()

const emit = defineEmits<{
  (e: 'update:open', open: boolean): void
  (e: 'saved', serviceTime: ServiceTimeDetail, mode: ServiceTimeFormMode): void
}>()

const form = reactive<ServiceTimeFormData>(emptyServiceTimeForm())
const touched = ref<Record<string, boolean>>({})
const submitted = ref(false)
const submitting = ref(false)
const serverError = ref<string | null>(null)
const fieldErrors = ref<Record<string, string>>({})

const isCreate = computed(() => props.mode === 'create')

const localErrors = computed(() => validateServiceTimeForm(form))

const serverFieldMap: Record<keyof ServiceTimeFormData, string> = {
  namaWaktu: 'nama_waktu',
  estimasiMinJam: 'estimasi_min_jam',
  estimasiMaxJam: 'estimasi_max_jam',
  deskripsi: 'deskripsi',
  isActive: 'is_active',
}

const errorOf = (field: keyof ServiceTimeFormData): string | undefined => {
  const serverMessage = fieldErrors.value[serverFieldMap[field]]
  if (serverMessage) return serverMessage
  if (!submitted.value && !touched.value[field]) return undefined
  return localErrors.value[field]
}

const touch = (field: keyof ServiceTimeFormData) => {
  touched.value[field] = true
}

const clearServerError = () => {
  serverError.value = null
  fieldErrors.value = {}
}

const previewEstimasi = computed(() => {
  const parse = (value: string): number | null => {
    if (!value.trim()) return null
    const parsed = Number(value.replace(',', '.'))
    return Number.isInteger(parsed) ? parsed : null
  }
  return formatEstimasi(parse(form.estimasiMinJam), parse(form.estimasiMaxJam))
})

watch(
  () => props.open,
  (open) => {
    if (!open) return

    submitted.value = false
    touched.value = {}
    serverError.value = null
    fieldErrors.value = {}

    if (props.mode === 'edit' && props.serviceTime) {
      Object.assign(form, {
        namaWaktu: props.serviceTime.nama_waktu,
        estimasiMinJam:
          props.serviceTime.estimasi_min_jam == null
            ? ''
            : String(props.serviceTime.estimasi_min_jam),
        estimasiMaxJam:
          props.serviceTime.estimasi_max_jam == null
            ? ''
            : String(props.serviceTime.estimasi_max_jam),
        deskripsi: props.serviceTime.deskripsi ?? '',
        isActive: props.serviceTime.is_active,
      })
    } else {
      Object.assign(form, emptyServiceTimeForm())
    }
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
    const min = form.estimasiMinJam.trim() ? Number(form.estimasiMinJam.replace(',', '.')) : null
    const max = form.estimasiMaxJam.trim() ? Number(form.estimasiMaxJam.replace(',', '.')) : null

    const payload: CreateServiceTimePayload = {
      nama_waktu: form.namaWaktu.trim(),
      estimasi_min_jam: min,
      estimasi_max_jam: max,
      deskripsi: deskripsi ? deskripsi : null,
      is_active: form.isActive,
    }

    const saved = isCreate.value
      ? await serviceTimesApi.create(payload)
      : props.serviceTime
        ? await serviceTimesApi.update(props.serviceTime.id, payload)
        : null

    if (!saved) return

    emit('saved', saved, props.mode)
    emit('update:open', false)
  } catch (error) {
    const apiError = normalizeApiError(error)
    fieldErrors.value = translateFieldErrors(apiError.errors)

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
          {{ isCreate ? 'Tambah waktu layanan' : 'Edit waktu layanan' }}
        </DialogTitle>
        <DialogDescription class="text-sm text-gray-500 dark:text-gray-400">
          {{
            isCreate
              ? 'Tentukan nama dan estimasi pengerjaan layanan laundry.'
              : 'Perbarui nama dan estimasi pengerjaan waktu layanan.'
          }}
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

        <Field :data-invalid="Boolean(errorOf('namaWaktu'))" class="gap-1.5">
          <FieldLabel for="service-time-name"
            >Nama waktu layanan <span class="text-red-500">*</span></FieldLabel
          >
          <Input
            id="service-time-name"
            v-model="form.namaWaktu"
            placeholder="Contoh: Reguler, Express 1 Hari"
            autocomplete="off"
            :aria-invalid="errorOf('namaWaktu') ? 'true' : undefined"
            @blur="touch('namaWaktu')"
            @input="clearServerError"
          />
          <FieldError :errors="[{ message: errorOf('namaWaktu') }]" />
        </Field>

        <div class="grid gap-5 sm:grid-cols-2">
          <Field :data-invalid="Boolean(errorOf('estimasiMinJam'))" class="gap-1.5">
            <FieldLabel for="service-time-min">Estimasi minimal (jam)</FieldLabel>
            <Input
              id="service-time-min"
              v-model="form.estimasiMinJam"
              type="number"
              min="0"
              step="1"
              inputmode="numeric"
              placeholder="Contoh: 48"
              :aria-invalid="errorOf('estimasiMinJam') ? 'true' : undefined"
              @blur="touch('estimasiMinJam')"
              @input="clearServerError"
            />
            <FieldError :errors="[{ message: errorOf('estimasiMinJam') }]" />
          </Field>

          <Field :data-invalid="Boolean(errorOf('estimasiMaxJam'))" class="gap-1.5">
            <FieldLabel for="service-time-max">Estimasi maksimal (jam)</FieldLabel>
            <Input
              id="service-time-max"
              v-model="form.estimasiMaxJam"
              type="number"
              min="0"
              step="1"
              inputmode="numeric"
              placeholder="Contoh: 72"
              :aria-invalid="errorOf('estimasiMaxJam') ? 'true' : undefined"
              @blur="touch('estimasiMaxJam')"
              @input="clearServerError"
            />
            <FieldError :errors="[{ message: errorOf('estimasiMaxJam') }]" />
          </Field>
        </div>

        <p class="text-xs text-gray-400 dark:text-gray-500">
          Estimasi opsional. Pratinjau:
          <span class="font-medium text-gray-700 dark:text-gray-300">{{ previewEstimasi }}</span>
        </p>

        <Field :data-invalid="Boolean(errorOf('deskripsi'))" class="gap-1.5">
          <FieldLabel for="service-time-description">Deskripsi</FieldLabel>
          <Textarea
            id="service-time-description"
            v-model="form.deskripsi"
            rows="3"
            placeholder="Keterangan singkat mengenai waktu layanan (opsional)."
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
            <FieldLabel for="service-time-active" class="text-sm font-medium"
              >Status aktif</FieldLabel
            >
            <p class="text-xs text-gray-500 dark:text-gray-400">
              Waktu nonaktif tidak dapat dipilih pada layanan.
            </p>
          </div>
          <Switch id="service-time-active" v-model="form.isActive" />
        </Field>

        <DialogFooter class="mx-0 mb-0 gap-3 p-0 pt-2 sm:justify-end">
          <DialogClose as-child>
            <Button variant="outline" type="button" :disabled="submitting">Batal</Button>
          </DialogClose>
          <Button type="submit" :disabled="submitting">
            {{ submitting ? 'Menyimpan...' : isCreate ? 'Tambah waktu' : 'Simpan perubahan' }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
