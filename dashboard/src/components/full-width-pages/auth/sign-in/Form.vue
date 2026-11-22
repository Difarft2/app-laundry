<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Field, FieldError, FieldLabel } from '@/components/ui/field'
import { Input } from '@/components/ui/input'
import {
  InputGroup,
  InputGroupAddon,
  InputGroupButton,
  InputGroupInput,
} from '@/components/ui/input-group'
import { useFormValidation } from '@/composables/useFormValidation'
import { EyeCloseIcon, EyeIcon } from '@/icons/layout'
import { normalizeApiError } from '@/services/http'
import { useAuthStore } from '@/stores/auth'
import { validateSignIn, type SignInFormData } from './schema'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const showPassword = ref(false)
const formError = ref<string | null>(null)

const { values, errorOf, touch, handleSubmit, isSubmitting } = useFormValidation<SignInFormData>(
  { email: '', password: '', keepLoggedIn: false },
  validateSignIn,
)

const notice = computed(() => {
  if (route.query.expired)
    return 'Sesi Anda berakhir atau token tidak valid. Silakan login kembali.'
  if (route.query.changed) return 'Password berhasil diperbarui. Silakan login kembali.'
  return null
})

const redirectTarget = computed(() => {
  const redirect = route.query.redirect
  if (typeof redirect === 'string' && redirect.startsWith('/') && !redirect.startsWith('//')) {
    return redirect
  }
  return '/'
})

const onSubmit = handleSubmit(async (data) => {
  formError.value = null

  try {
    await auth.login(data.email.trim(), data.password, Boolean(data.keepLoggedIn))
    await router.replace(redirectTarget.value)
  } catch (error) {
    const apiError = normalizeApiError(error)
    formError.value =
      apiError.status === 401 ? 'Email atau password tidak valid.' : apiError.message
  }
})
</script>

<template>
  <form class="space-y-5" novalidate @submit="onSubmit">
    <div
      v-if="notice"
      role="status"
      class="rounded-lg border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700 dark:border-success-500/20 dark:bg-success-500/10 dark:text-success-400"
    >
      {{ notice }}
    </div>

    <div
      v-if="formError"
      role="alert"
      class="rounded-lg border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-700 dark:border-error-500/20 dark:bg-error-500/10 dark:text-error-400"
    >
      {{ formError }}
    </div>

    <Field :data-invalid="Boolean(errorOf('email'))">
      <FieldLabel class="mb-1.5 block"> Email <span class="text-red-500">*</span> </FieldLabel>
      <Input
        v-model="values.email"
        type="email"
        autocomplete="username"
        placeholder="nama@email.com"
        :aria-invalid="errorOf('email') ? 'true' : undefined"
        @blur="touch('email')"
      />
      <FieldError :errors="[{ message: errorOf('email') }]" />
    </Field>

    <Field :data-invalid="Boolean(errorOf('password'))">
      <FieldLabel class="mb-1.5 block"> Password <span class="text-red-500">*</span> </FieldLabel>
      <InputGroup>
        <InputGroupInput
          v-model="values.password"
          :type="showPassword ? 'text' : 'password'"
          autocomplete="current-password"
          placeholder="Masukkan password"
          :aria-invalid="errorOf('password') ? 'true' : undefined"
          @blur="touch('password')"
        />
        <InputGroupAddon align="inline-end">
          <InputGroupButton
            size="icon-xs"
            :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
            class="text-gray-500 dark:text-gray-400"
            @click="showPassword = !showPassword"
          >
            <EyeIcon v-if="showPassword" class="fill-current" />
            <EyeCloseIcon v-else class="fill-current" />
          </InputGroupButton>
        </InputGroupAddon>
      </InputGroup>
      <FieldError :errors="[{ message: errorOf('password') }]" />
    </Field>

    <div class="flex items-center justify-between">
      <Field orientation="horizontal" class="w-auto items-center gap-3">
        <Checkbox
          id="keep-logged-in"
          :model-value="values.keepLoggedIn"
          @update:model-value="values.keepLoggedIn = Boolean($event)"
        />
        <FieldLabel
          for="keep-logged-in"
          class="text-theme-sm block cursor-pointer font-normal text-gray-700 dark:text-gray-400"
        >
          Ingat saya
        </FieldLabel>
      </Field>
    </div>

    <div>
      <Button class="w-full" size="lg" type="submit" :disabled="isSubmitting || auth.loggingIn">
        {{ isSubmitting || auth.loggingIn ? 'Memproses...' : 'Masuk' }}
      </Button>
    </div>

    <p class="text-center text-xs leading-5 text-gray-400 dark:text-gray-500">
      Gunakan akun yang diberikan owner. Hubungi owner bila lupa password.
    </p>
  </form>
</template>
