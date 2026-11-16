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
import { normalizeApiError } from '@/services/http'
import { usersApi } from '@/services/users'
import type { AuthUser } from '@/types/auth'
import { translateFieldErrors } from './errors'
import { emptyResetPasswordForm, validateResetPassword, type ResetPasswordFormData } from './schema'

const props = defineProps<{
  open: boolean
  user: AuthUser | null
}>()

const emit = defineEmits<{
  (e: 'update:open', open: boolean): void
  (e: 'saved', message: string): void
}>()

const form = reactive<ResetPasswordFormData>(emptyResetPasswordForm())
const touched = ref<Record<string, boolean>>({})
const submitted = ref(false)
const submitting = ref(false)
const serverError = ref<string | null>(null)
const fieldErrors = ref<Record<string, string>>({})

const localErrors = computed(() => validateResetPassword(form))

const errorOf = (field: keyof ResetPasswordFormData): string | undefined => {
  const serverMessage = fieldErrors.value[field]
  if (serverMessage) return serverMessage
  if (!submitted.value && !touched.value[field]) return undefined
  return localErrors.value[field]
}

const touch = (field: keyof ResetPasswordFormData) => {
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

    Object.assign(form, emptyResetPasswordForm())
    touched.value = {}
    submitted.value = false
    serverError.value = null
    fieldErrors.value = {}
  },
  { immediate: true },
)

const onSubmit = async (event?: Event) => {
  event?.preventDefault()
  submitted.value = true
  clearServerError()

  if (Object.keys(localErrors.value).length > 0 || !props.user) return

  submitting.value = true

  try {
    const response = await usersApi.resetPassword(props.user.id, {
      password: form.password,
      password_confirmation: form.passwordConfirmation,
    })

    emit('saved', response.message || 'Password pengguna berhasil direset.')
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
    <DialogContent class="max-w-[calc(100%-2rem)] sm:max-w-md">
      <DialogHeader class="gap-2 pe-10 text-start">
        <DialogTitle class="text-xl font-semibold text-gray-800 dark:text-white/90">
          Reset password
        </DialogTitle>
        <DialogDescription class="text-sm text-gray-500 dark:text-gray-400">
          Atur password baru untuk
          <span class="font-medium text-gray-700 dark:text-gray-300">{{ user?.name }}</span
          >. Semua sesi lama pengguna ini akan otomatis keluar.
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

        <Field :data-invalid="Boolean(errorOf('password'))" class="gap-1.5">
          <FieldLabel for="reset-password">Password baru</FieldLabel>
          <Input
            id="reset-password"
            v-model="form.password"
            type="password"
            placeholder="Minimal 8 karakter"
            autocomplete="new-password"
            :aria-invalid="errorOf('password') ? 'true' : undefined"
            @blur="touch('password')"
            @input="clearServerError"
          />
          <FieldError :errors="[{ message: errorOf('password') }]" />
        </Field>

        <Field :data-invalid="Boolean(errorOf('passwordConfirmation'))" class="gap-1.5">
          <FieldLabel for="reset-password-confirmation">Konfirmasi password</FieldLabel>
          <Input
            id="reset-password-confirmation"
            v-model="form.passwordConfirmation"
            type="password"
            placeholder="Ulangi password baru"
            autocomplete="new-password"
            :aria-invalid="errorOf('passwordConfirmation') ? 'true' : undefined"
            @blur="touch('passwordConfirmation')"
            @input="clearServerError"
          />
          <FieldError :errors="[{ message: errorOf('passwordConfirmation') }]" />
        </Field>

        <DialogFooter class="mx-0 mb-0 gap-3 p-0 pt-2 sm:justify-end">
          <DialogClose as-child>
            <Button variant="outline" type="button" :disabled="submitting">Batal</Button>
          </DialogClose>
          <Button type="submit" :disabled="submitting">
            {{ submitting ? 'Menyimpan...' : 'Reset password' }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
