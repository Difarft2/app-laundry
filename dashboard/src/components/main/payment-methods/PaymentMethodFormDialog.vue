<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
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
import { Switch } from '@/components/ui/switch'
import { Textarea } from '@/components/ui/textarea'
import { normalizeApiError } from '@/services/http'
import { paymentMethodsApi } from '@/services/payment-methods'
import type { PaymentMethod } from '@/types/payment-method'
import { translateFieldErrors } from './errors'
import {
  emptyPaymentMethodForm,
  validatePaymentMethodForm,
  type PaymentMethodFormData,
  type PaymentMethodFormMode,
} from './schema'

const props = defineProps<{
  open: boolean
  mode: PaymentMethodFormMode
  paymentMethod: PaymentMethod | null
}>()

const emit = defineEmits<{
  (e: 'update:open', open: boolean): void
  (e: 'saved', paymentMethod: PaymentMethod, mode: PaymentMethodFormMode): void
}>()

const form = reactive<PaymentMethodFormData>(emptyPaymentMethodForm())
const touched = ref<Record<string, boolean>>({})
const submitted = ref(false)
const submitting = ref(false)
const serverError = ref<string | null>(null)
const fieldErrors = ref<Record<string, string>>({})

const isCreate = computed(() => props.mode === 'create')
const localErrors = computed(() => validatePaymentMethodForm(form))

const errorOf = (field: keyof PaymentMethodFormData): string | undefined => {
  const serverMessage = fieldErrors.value[serverFieldKey(field)]
  if (serverMessage) return serverMessage
  if (!submitted.value && !touched.value[field]) return undefined
  return localErrors.value[field]
}

const serverFieldKey = (field: keyof PaymentMethodFormData): string => {
  const mapping: Record<keyof PaymentMethodFormData, string> = {
    namaMetode: 'nama_metode',
    kode: 'kode',
    deskripsi: 'deskripsi',
    isActive: 'is_active',
  }
  return mapping[field]
}

const touch = (field: keyof PaymentMethodFormData) => {
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

    if (props.mode === 'edit' && props.paymentMethod) {
      Object.assign(form, {
        namaMetode: props.paymentMethod.nama_metode,
        kode: props.paymentMethod.kode,
        deskripsi: props.paymentMethod.deskripsi ?? '',
        isActive: props.paymentMethod.is_active,
      })
    } else {
      Object.assign(form, emptyPaymentMethodForm())
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
    const payload = {
      nama_metode: form.namaMetode.trim(),
      kode: form.kode.trim(),
      deskripsi: deskripsi ? deskripsi : null,
      is_active: form.isActive,
    }

    const saved = isCreate.value
      ? await paymentMethodsApi.create(payload)
      : props.paymentMethod
        ? await paymentMethodsApi.update(props.paymentMethod.id, payload)
        : null

    if (!saved) return

    emit('saved', saved, props.mode)
    emit('update:open', false)
  } catch (error) {
    const apiError = normalizeApiError(error)
    const translated = translateFieldErrors(apiError.errors)

    const mapping: Record<string, keyof PaymentMethodFormData> = {
      nama_metode: 'namaMetode',
      kode: 'kode',
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
    <DialogContent class="max-h-[calc(100vh-3rem)] max-w-[calc(100%-2rem)] overflow-y-auto sm:max-w-md">
      <DialogHeader class="gap-2 pe-10 text-start">
        <DialogTitle class="text-xl font-semibold text-gray-800 dark:text-white/90">
          {{ isCreate ? 'Tambah Metode Pembayaran' : 'Edit Metode Pembayaran' }}
        </DialogTitle>
        <DialogDescription class="text-sm text-gray-500 dark:text-gray-400">
          {{ isCreate ? 'Masukkan data metode pembayaran baru.' : 'Ubah data metode pembayaran.' }}
        </DialogDescription>
      </DialogHeader>

      <form @submit="onSubmit">
        <div class="grid gap-4 py-4">
          <Field>
            <FieldLabel for="namaMetode">Nama Metode</FieldLabel>
            <Input
              id="namaMetode"
              v-model="form.namaMetode"
              placeholder="Contoh: Transfer Bank, E-Wallet"
              :invalid="!!errorOf('namaMetode')"
              @blur="touch('namaMetode')"
            />
            <FieldError>{{ errorOf('namaMetode') }}</FieldError>
          </Field>

          <Field>
            <FieldLabel for="kode">Kode Metode</FieldLabel>
            <Input
              id="kode"
              v-model="form.kode"
              placeholder="Contoh: BNI, DANA"
              :invalid="!!errorOf('kode')"
              @blur="touch('kode')"
            />
            <FieldError>{{ errorOf('kode') }}</FieldError>
          </Field>

          <Field>
            <FieldLabel for="deskripsi">Deskripsi</FieldLabel>
            <Textarea
              id="deskripsi"
              v-model="form.deskripsi"
              placeholder="Opsional"
              :invalid="!!errorOf('deskripsi')"
              @blur="touch('deskripsi')"
            />
            <FieldError>{{ errorOf('deskripsi') }}</FieldError>
          </Field>

          <div class="flex items-center justify-between gap-4 rounded-lg border border-gray-200 p-3 dark:border-gray-700">
            <FieldLabel for="isActive" class="mb-0">Aktif</FieldLabel>
            <Switch id="isActive" v-model="form.isActive" />
          </div>
        </div>

        <div
          v-if="serverError"
          role="alert"
          class="mb-4 rounded-lg border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-700 dark:border-error-500/20 dark:bg-error-500/10 dark:text-error-400"
        >
          {{ serverError }}
        </div>

        <DialogFooter class="mx-0 mt-4 gap-3 p-0 sm:justify-end">
          <Button variant="outline" type="button" :disabled="submitting" @click="emit('update:open', false)">Batal</Button>
          <Button type="submit" :disabled="submitting">
            {{ submitting ? 'Menyimpan...' : 'Simpan' }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
