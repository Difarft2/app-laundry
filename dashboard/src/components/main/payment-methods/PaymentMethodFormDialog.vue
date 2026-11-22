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
import { normalizeApiError } from '@/services/http'
import { paymentMethodsApi } from '@/services/payment-methods'
import type { CreatePaymentMethodPayload, PaymentMethod } from '@/types/payment-method'
import { translateFieldErrors } from './errors'
import {
  emptyPaymentMethodForm,
  suggestKode,
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
/** Kode tidak lagi diisi otomatis setelah pengguna mengubahnya sendiri. */
const kodeTouched = ref(false)

const isCreate = computed(() => props.mode === 'create')

const localErrors = computed(() => validatePaymentMethodForm(form))

const serverFieldMap: Record<keyof PaymentMethodFormData, string> = {
  namaMetode: 'nama_metode',
  kode: 'kode',
  deskripsi: 'deskripsi',
  isActive: 'is_active',
}

const errorOf = (field: keyof PaymentMethodFormData): string | undefined => {
  const serverMessage = fieldErrors.value[serverFieldMap[field]]
  if (serverMessage) return serverMessage
  if (!submitted.value && !touched.value[field]) return undefined
  return localErrors.value[field]
}

const touch = (field: keyof PaymentMethodFormData) => {
  touched.value[field] = true
}

const clearServerError = () => {
  serverError.value = null
  fieldErrors.value = {}
}

const onKodeInput = () => {
  kodeTouched.value = true
  clearServerError()
}

// Saran kode otomatis saat menambah metode baru.
watch(
  () => form.namaMetode,
  (nama) => {
    if (!isCreate.value || kodeTouched.value) return
    form.kode = suggestKode(nama)
  },
)

watch(
  () => props.open,
  (open) => {
    if (!open) return

    submitted.value = false
    touched.value = {}
    serverError.value = null
    fieldErrors.value = {}
    kodeTouched.value = false

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

    const payload: CreatePaymentMethodPayload = {
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
          {{ isCreate ? 'Tambah metode pembayaran' : 'Edit metode pembayaran' }}
        </DialogTitle>
        <DialogDescription class="text-sm text-gray-500 dark:text-gray-400">
          {{
            isCreate
              ? 'Tentukan nama dan kode metode pembayaran yang baru.'
              : 'Perbarui detail metode pembayaran.'
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

        <Field :data-invalid="Boolean(errorOf('namaMetode'))" class="gap-1.5">
          <FieldLabel for="payment-method-name"
            >Nama metode <span class="text-red-500">*</span></FieldLabel
          >
          <Input
            id="payment-method-name"
            v-model="form.namaMetode"
            placeholder="Contoh: Transfer Bank"
            autocomplete="off"
            :aria-invalid="errorOf('namaMetode') ? 'true' : undefined"
            @blur="touch('namaMetode')"
            @input="clearServerError"
          />
          <FieldError :errors="[{ message: errorOf('namaMetode') }]" />
        </Field>

        <Field :data-invalid="Boolean(errorOf('kode'))" class="gap-1.5">
          <FieldLabel for="payment-method-code"
            >Kode <span class="text-red-500">*</span></FieldLabel
          >
          <Input
            id="payment-method-code"
            v-model="form.kode"
            placeholder="Contoh: transfer_bank"
            autocomplete="off"
            :aria-invalid="errorOf('kode') ? 'true' : undefined"
            @blur="touch('kode')"
            @input="onKodeInput"
          />
          <FieldError :errors="[{ message: errorOf('kode') }]" />
          <p class="text-xs text-gray-400 dark:text-gray-500">
            Kode unik untuk integrasi, gunakan huruf kecil dan garis bawah. Contoh: transfer_bank.
          </p>
        </Field>

        <Field :data-invalid="Boolean(errorOf('deskripsi'))" class="gap-1.5">
          <FieldLabel for="payment-method-description">Deskripsi</FieldLabel>
          <Textarea
            id="payment-method-description"
            v-model="form.deskripsi"
            rows="3"
            placeholder="Penjelasan singkat mengenai metode pembayaran (opsional)."
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
            <FieldLabel for="payment-method-active" class="text-sm font-medium"
              >Status aktif</FieldLabel
            >
            <p class="text-xs text-gray-500 dark:text-gray-400">
              Metode nonaktif tidak dapat dipilih pada pembayaran baru.
            </p>
          </div>
          <Switch id="payment-method-active" v-model="form.isActive" />
        </Field>

        <DialogFooter class="mx-0 mb-0 gap-3 p-0 pt-2 sm:justify-end">
          <DialogClose as-child>
            <Button variant="outline" type="button" :disabled="submitting">Batal</Button>
          </DialogClose>
          <Button type="submit" :disabled="submitting">
            {{
              submitting
                ? 'Menyimpan...'
                : isCreate
                  ? 'Tambah metode'
                  : 'Simpan perubahan'
            }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
