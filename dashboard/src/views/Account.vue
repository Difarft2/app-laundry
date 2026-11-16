<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import RoleBadges from '@/components/main/users/RoleBadges.vue'
import { translateFieldErrors } from '@/components/main/users/errors'
import { Button } from '@/components/ui/button'
import { Field, FieldError, FieldLabel } from '@/components/ui/field'
import { Input } from '@/components/ui/input'
import { isEmail } from '@/composables/useFormValidation'
import { normalizeApiError, type ApiFieldErrors } from '@/services/http'
import { useAuthStore } from '@/stores/auth'
import { getInitials } from '@/utils/get-initials'

const auth = useAuthStore()
const router = useRouter()

/* ------------------------------------------------------------------ */
/* Profil                                                              */
/* ------------------------------------------------------------------ */

const profile = reactive({ name: '', email: '' })
const profileTouched = ref<Record<string, boolean>>({})
const profileSubmitted = ref(false)
const profileSubmitting = ref(false)
const profileError = ref<string | null>(null)
const profileFieldErrors = ref<Record<string, string>>({})
const profileSuccess = ref<string | null>(null)

watch(
  () => auth.user,
  (user) => {
    if (!user) return
    profile.name = user.name
    profile.email = user.email
  },
  { immediate: true },
)

const profileLocalErrors = computed<Record<string, string>>(() => {
  const errors: Record<string, string> = {}

  if (!profile.name.trim()) errors.name = 'Nama wajib diisi.'
  else if (profile.name.trim().length > 255) errors.name = 'Nama maksimal 255 karakter.'

  if (!profile.email.trim()) errors.email = 'Email wajib diisi.'
  else if (!isEmail(profile.email.trim())) errors.email = 'Format email tidak valid.'

  return errors
})

const profileErrorOf = (field: 'name' | 'email'): string | undefined => {
  const serverMessage = profileFieldErrors.value[field]
  if (serverMessage) return serverMessage
  if (!profileSubmitted.value && !profileTouched.value[field]) return undefined
  return profileLocalErrors.value[field]
}

const touchProfile = (field: 'name' | 'email') => {
  profileTouched.value[field] = true
}

const clearProfileErrors = () => {
  profileError.value = null
  profileFieldErrors.value = {}
  profileSuccess.value = null
}

const submitProfile = async (event?: Event) => {
  event?.preventDefault()
  profileSubmitted.value = true
  clearProfileErrors()

  if (Object.keys(profileLocalErrors.value).length > 0) return

  profileSubmitting.value = true

  try {
    await auth.updateProfile({
      name: profile.name.trim(),
      email: profile.email.trim(),
    })
    profileSuccess.value = 'Profil berhasil diperbarui.'
    profileSubmitted.value = false
    profileTouched.value = {}
  } catch (error) {
    const apiError = normalizeApiError(error)
    profileFieldErrors.value = translateFieldErrors(apiError.errors)

    if (Object.keys(profileFieldErrors.value).length === 0) {
      profileError.value = apiError.message
    }
  } finally {
    profileSubmitting.value = false
  }
}

/* ------------------------------------------------------------------ */
/* Password                                                            */
/* ------------------------------------------------------------------ */

const password = reactive({
  currentPassword: '',
  newPassword: '',
  confirmation: '',
})
const passwordTouched = ref<Record<string, boolean>>({})
const passwordSubmitted = ref(false)
const passwordSubmitting = ref(false)
const passwordError = ref<string | null>(null)
const passwordFieldErrors = ref<Record<string, string>>({})

const passwordLocalErrors = computed<Record<string, string>>(() => {
  const errors: Record<string, string> = {}

  if (!password.currentPassword) errors.currentPassword = 'Password saat ini wajib diisi.'
  if (!password.newPassword) errors.newPassword = 'Password baru wajib diisi.'
  else if (password.newPassword.length < 8) errors.newPassword = 'Password minimal 8 karakter.'

  if (password.newPassword && password.newPassword !== password.confirmation) {
    errors.confirmation = 'Konfirmasi password tidak sama.'
  }

  return errors
})

const passwordErrorOf = (field: 'currentPassword' | 'newPassword' | 'confirmation') => {
  const serverMessage = passwordFieldErrors.value[field]
  if (serverMessage) return serverMessage
  if (!passwordSubmitted.value && !passwordTouched.value[field]) return undefined
  return passwordLocalErrors.value[field]
}

const touchPassword = (field: 'currentPassword' | 'newPassword' | 'confirmation') => {
  passwordTouched.value[field] = true
}

const clearPasswordErrors = () => {
  passwordError.value = null
  passwordFieldErrors.value = {}
}

/** Nama field backend (`current_password`) dipetakan ke state form lokal. */
const mapPasswordFieldErrors = (errors?: ApiFieldErrors): Record<string, string> => {
  const translated = translateFieldErrors(errors)
  const result: Record<string, string> = {}

  for (const [field, message] of Object.entries(translated)) {
    if (field === 'current_password') result.currentPassword = message
    else if (field === 'password_confirmation') result.confirmation = message
    else if (field === 'password') {
      result[message.toLowerCase().includes('konfirmasi') ? 'confirmation' : 'newPassword'] =
        message
    } else result[field] = message
  }

  return result
}

const submitPassword = async (event?: Event) => {
  event?.preventDefault()
  passwordSubmitted.value = true
  clearPasswordErrors()

  if (Object.keys(passwordLocalErrors.value).length > 0) return

  passwordSubmitting.value = true

  try {
    await auth.updatePassword({
      current_password: password.currentPassword,
      password: password.newPassword,
      password_confirmation: password.confirmation,
    })

    // Backend mencabut semua token; arahkan ke halaman masuk dengan notifikasi.
    await router.replace({ name: 'Signin', query: { changed: '1' } })
  } catch (error) {
    const apiError = normalizeApiError(error)
    passwordFieldErrors.value = mapPasswordFieldErrors(apiError.errors)

    if (Object.keys(passwordFieldErrors.value).length === 0) {
      passwordError.value = apiError.message
    }
  } finally {
    passwordSubmitting.value = false
  }
}
</script>

<template>
  <AdminLayout>
    <div>
      <PageBreadcrumb page-title="Akun Saya" />

      <div class="grid gap-6 xl:grid-cols-12">
        <!-- Profil -->
        <div class="xl:col-span-7">
          <div
            class="rounded-2xl border border-gray-200 bg-white p-5 lg:p-6 dark:border-gray-800 dark:bg-white/3"
          >
            <div
              class="flex flex-col gap-4 border-b border-gray-100 pb-5 sm:flex-row sm:items-center dark:border-gray-800"
            >
              <span
                class="flex size-16 shrink-0 items-center justify-center rounded-2xl bg-brand-50 text-xl font-semibold text-brand-600 dark:bg-brand-500/15 dark:text-brand-400"
                aria-hidden="true"
              >
                {{ getInitials(auth.user?.name ?? '?') }}
              </span>

              <div class="min-w-0">
                <h3 class="truncate text-lg font-semibold text-gray-800 dark:text-white/90">
                  {{ auth.user?.name }}
                </h3>
                <p class="truncate text-sm text-gray-500 dark:text-gray-400">
                  {{ auth.user?.email }}
                </p>
                <div class="mt-2">
                  <RoleBadges :roles="auth.user?.roles ?? []" />
                </div>
              </div>
            </div>

            <form class="mt-5 space-y-5" novalidate @submit="submitProfile">
              <div>
                <h4 class="text-base font-semibold text-gray-800 dark:text-white/90">
                  Informasi profil
                </h4>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                  Nama dan email ini digunakan untuk login dan ditampilkan di dashboard.
                </p>
              </div>

              <div
                v-if="profileSuccess"
                role="status"
                class="rounded-lg border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700 dark:border-success-500/20 dark:bg-success-500/10 dark:text-success-400"
              >
                {{ profileSuccess }}
              </div>

              <div
                v-if="profileError"
                role="alert"
                class="rounded-lg border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-700 dark:border-error-500/20 dark:bg-error-500/10 dark:text-error-400"
              >
                {{ profileError }}
              </div>

              <div class="grid gap-5 sm:grid-cols-2">
                <Field :data-invalid="Boolean(profileErrorOf('name'))" class="gap-1.5">
                  <FieldLabel for="account-name">Nama</FieldLabel>
                  <Input
                    id="account-name"
                    v-model="profile.name"
                    autocomplete="name"
                    :aria-invalid="profileErrorOf('name') ? 'true' : undefined"
                    @blur="touchProfile('name')"
                    @input="clearProfileErrors"
                  />
                  <FieldError :errors="[{ message: profileErrorOf('name') }]" />
                </Field>

                <Field :data-invalid="Boolean(profileErrorOf('email'))" class="gap-1.5">
                  <FieldLabel for="account-email">Email</FieldLabel>
                  <Input
                    id="account-email"
                    v-model="profile.email"
                    type="email"
                    autocomplete="email"
                    :aria-invalid="profileErrorOf('email') ? 'true' : undefined"
                    @blur="touchProfile('email')"
                    @input="clearProfileErrors"
                  />
                  <FieldError :errors="[{ message: profileErrorOf('email') }]" />
                </Field>
              </div>

              <div class="flex justify-end">
                <Button type="submit" :disabled="profileSubmitting">
                  {{ profileSubmitting ? 'Menyimpan...' : 'Simpan profil' }}
                </Button>
              </div>
            </form>
          </div>
        </div>

        <!-- Password -->
        <div class="xl:col-span-5">
          <div
            class="rounded-2xl border border-gray-200 bg-white p-5 lg:p-6 dark:border-gray-800 dark:bg-white/3"
          >
            <form class="space-y-5" novalidate @submit="submitPassword">
              <div>
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">
                  Ubah password
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                  Gunakan minimal 8 karakter. Setelah password diubah, semua sesi akan berakhir dan
                  Anda perlu login kembali.
                </p>
              </div>

              <div
                v-if="passwordError"
                role="alert"
                class="rounded-lg border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-700 dark:border-error-500/20 dark:bg-error-500/10 dark:text-error-400"
              >
                {{ passwordError }}
              </div>

              <Field :data-invalid="Boolean(passwordErrorOf('currentPassword'))" class="gap-1.5">
                <FieldLabel for="account-current-password">Password saat ini</FieldLabel>
                <Input
                  id="account-current-password"
                  v-model="password.currentPassword"
                  type="password"
                  autocomplete="current-password"
                  placeholder="Masukkan password saat ini"
                  :aria-invalid="passwordErrorOf('currentPassword') ? 'true' : undefined"
                  @blur="touchPassword('currentPassword')"
                  @input="clearPasswordErrors"
                />
                <FieldError :errors="[{ message: passwordErrorOf('currentPassword') }]" />
              </Field>

              <Field :data-invalid="Boolean(passwordErrorOf('newPassword'))" class="gap-1.5">
                <FieldLabel for="account-new-password">Password baru</FieldLabel>
                <Input
                  id="account-new-password"
                  v-model="password.newPassword"
                  type="password"
                  autocomplete="new-password"
                  placeholder="Minimal 8 karakter"
                  :aria-invalid="passwordErrorOf('newPassword') ? 'true' : undefined"
                  @blur="touchPassword('newPassword')"
                  @input="clearPasswordErrors"
                />
                <FieldError :errors="[{ message: passwordErrorOf('newPassword') }]" />
              </Field>

              <Field :data-invalid="Boolean(passwordErrorOf('confirmation'))" class="gap-1.5">
                <FieldLabel for="account-password-confirmation"
                  >Konfirmasi password baru</FieldLabel
                >
                <Input
                  id="account-password-confirmation"
                  v-model="password.confirmation"
                  type="password"
                  autocomplete="new-password"
                  placeholder="Ulangi password baru"
                  :aria-invalid="passwordErrorOf('confirmation') ? 'true' : undefined"
                  @blur="touchPassword('confirmation')"
                  @input="clearPasswordErrors"
                />
                <FieldError :errors="[{ message: passwordErrorOf('confirmation') }]" />
              </Field>

              <div class="flex justify-end">
                <Button type="submit" :disabled="passwordSubmitting">
                  {{ passwordSubmitting ? 'Menyimpan...' : 'Ubah password' }}
                </Button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
