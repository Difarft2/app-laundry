<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
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
import { ROLE_OPTIONS, roleDescription } from '@/constants/roles'
import { normalizeApiError } from '@/services/http'
import { usersApi } from '@/services/users'
import type { AuthUser } from '@/types/auth'
import { translateFieldErrors } from './errors'
import { emptyUserForm, validateUserForm, type UserFormData, type UserFormMode } from './schema'

const props = defineProps<{
  open: boolean
  mode: UserFormMode
  user: AuthUser | null
  /** Hanya owner yang boleh memberikan role owner. */
  canAssignOwner: boolean
}>()

const emit = defineEmits<{
  (e: 'update:open', open: boolean): void
  (e: 'saved', user: AuthUser, mode: UserFormMode): void
}>()

const form = reactive<UserFormData>(emptyUserForm())
const touched = ref<Record<string, boolean>>({})
const submitted = ref(false)
const submitting = ref(false)
const serverError = ref<string | null>(null)
const fieldErrors = ref<Record<string, string>>({})

const isCreate = computed(() => props.mode === 'create')

const targetIsOwner = computed(() => props.user?.roles.includes('owner') ?? false)

/**
 * Owner hanya boleh diubah role-nya oleh owner. Untuk pengguna tanpa role
 * owner, bagian role dinonaktifkan dan `roles` tidak dikirim agar role owner
 * tidak hilang.
 */
const canEditRoles = computed(() => props.canAssignOwner || !targetIsOwner.value)

const roleOptions = computed(() =>
  ROLE_OPTIONS.filter((role) => role.value !== 'owner' || props.canAssignOwner),
)

const localErrors = computed(() => validateUserForm(form, props.mode))

const errorOf = (field: keyof UserFormData): string | undefined => {
  const serverMessage = fieldErrors.value[field]
  if (serverMessage) return serverMessage
  if (!submitted.value && !touched.value[field]) return undefined
  return localErrors.value[field]
}

const touch = (field: keyof UserFormData) => {
  touched.value[field] = true
}

const clearServerError = () => {
  serverError.value = null
  fieldErrors.value = {}
}

const toggleRole = (role: string, checked: boolean) => {
  if (!canEditRoles.value) return

  form.roles = checked ? [...form.roles, role] : form.roles.filter((item) => item !== role)
  clearServerError()
}

watch(
  () => props.open,
  (open) => {
    if (!open) return

    submitted.value = false
    touched.value = {}
    serverError.value = null
    fieldErrors.value = {}

    if (props.mode === 'edit' && props.user) {
      Object.assign(form, {
        name: props.user.name,
        email: props.user.email,
        // UserResource tidak mengembalikan no_hp; biarkan kosong agar tidak menimpa data.
        noHp: '',
        password: '',
        passwordConfirmation: '',
        roles: [...props.user.roles],
      })
    } else {
      Object.assign(form, emptyUserForm())
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
    const noHp = form.noHp.trim()
    let saved: AuthUser

    if (isCreate.value) {
      saved = await usersApi.create({
        name: form.name.trim(),
        email: form.email.trim(),
        ...(noHp ? { no_hp: noHp } : {}),
        password: form.password,
        password_confirmation: form.passwordConfirmation,
        roles: form.roles,
      })
    } else if (props.user) {
      saved = await usersApi.update(props.user.id, {
        name: form.name.trim(),
        email: form.email.trim(),
        ...(noHp ? { no_hp: noHp } : {}),
        ...(canEditRoles.value ? { roles: form.roles } : {}),
      })
    } else {
      return
    }

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
          {{ isCreate ? 'Tambah pengguna' : 'Edit pengguna' }}
        </DialogTitle>
        <DialogDescription class="text-sm text-gray-500 dark:text-gray-400">
          {{
            isCreate
              ? 'Buat akun baru dan tentukan role yang dapat diakses.'
              : 'Perbarui data akun. Password diubah melalui menu reset password.'
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

        <div class="grid gap-5 sm:grid-cols-2">
          <Field :data-invalid="Boolean(errorOf('name'))" class="gap-1.5">
            <FieldLabel for="user-name">Nama <span class="text-red-500">*</span></FieldLabel>
            <Input
              id="user-name"
              v-model="form.name"
              placeholder="Nama lengkap"
              autocomplete="off"
              :aria-invalid="errorOf('name') ? 'true' : undefined"
              @blur="touch('name')"
              @input="clearServerError"
            />
            <FieldError :errors="[{ message: errorOf('name') }]" />
          </Field>

          <Field :data-invalid="Boolean(errorOf('email'))" class="gap-1.5">
            <FieldLabel for="user-email">Email <span class="text-red-500">*</span></FieldLabel>
            <Input
              id="user-email"
              v-model="form.email"
              type="email"
              placeholder="nama@email.com"
              autocomplete="off"
              :aria-invalid="errorOf('email') ? 'true' : undefined"
              @blur="touch('email')"
              @input="clearServerError"
            />
            <FieldError :errors="[{ message: errorOf('email') }]" />
          </Field>
        </div>

        <Field :data-invalid="Boolean(errorOf('noHp'))" class="gap-1.5">
          <FieldLabel for="user-phone">Nomor HP</FieldLabel>
          <Input
            id="user-phone"
            v-model="form.noHp"
            type="tel"
            placeholder="08xxxxxxxxxx"
            :aria-invalid="errorOf('noHp') ? 'true' : undefined"
            @blur="touch('noHp')"
            @input="clearServerError"
          />
          <FieldError :errors="[{ message: errorOf('noHp') }]" />
          <p v-if="!isCreate" class="text-xs text-gray-400 dark:text-gray-500">
            Kosongkan bila nomor HP tidak ingin diubah.
          </p>
        </Field>

        <div v-if="isCreate" class="grid gap-5 sm:grid-cols-2">
          <Field :data-invalid="Boolean(errorOf('password'))" class="gap-1.5">
            <FieldLabel for="user-password"
              >Password <span class="text-red-500">*</span></FieldLabel
            >
            <Input
              id="user-password"
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
            <FieldLabel for="user-password-confirmation">
              Konfirmasi password <span class="text-red-500">*</span>
            </FieldLabel>
            <Input
              id="user-password-confirmation"
              v-model="form.passwordConfirmation"
              type="password"
              placeholder="Ulangi password"
              autocomplete="new-password"
              :aria-invalid="errorOf('passwordConfirmation') ? 'true' : undefined"
              @blur="touch('passwordConfirmation')"
              @input="clearServerError"
            />
            <FieldError :errors="[{ message: errorOf('passwordConfirmation') }]" />
          </Field>
        </div>

        <Field :data-invalid="Boolean(errorOf('roles'))" class="gap-2.5">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <FieldLabel>Role <span class="text-red-500">*</span></FieldLabel>
            <Badge v-if="!canEditRoles" color="warning" size="sm">
              Hanya owner dapat mengubah role owner
            </Badge>
          </div>

          <div class="grid gap-2 sm:grid-cols-2">
            <label
              v-for="role in roleOptions"
              :key="role.value"
              :for="`role-${role.value}`"
              class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 p-3 transition-colors dark:border-gray-800"
              :class="
                form.roles.includes(role.value)
                  ? 'border-brand-300 bg-brand-50/60 dark:border-brand-500/40 dark:bg-brand-500/10'
                  : 'hover:bg-gray-50 dark:hover:bg-white/5'
              "
            >
              <Checkbox
                :id="`role-${role.value}`"
                :model-value="form.roles.includes(role.value)"
                :disabled="!canEditRoles"
                class="mt-0.5"
                @update:model-value="toggleRole(role.value, Boolean($event))"
              />
              <span class="min-w-0">
                <span class="block text-sm font-medium text-gray-800 dark:text-white/90">
                  {{ role.label }}
                </span>
                <span class="mt-0.5 block text-xs leading-5 text-gray-500 dark:text-gray-400">
                  {{ roleDescription(role.value) }}
                </span>
              </span>
            </label>
          </div>

          <FieldError :errors="[{ message: errorOf('roles') }]" />

          <p v-if="!canAssignOwner" class="text-xs text-gray-400 dark:text-gray-500">
            Role owner hanya dapat diberikan oleh pengguna dengan role owner.
          </p>
        </Field>

        <DialogFooter class="mx-0 mb-0 gap-3 p-0 pt-2 sm:justify-end">
          <DialogClose as-child>
            <Button variant="outline" type="button" :disabled="submitting">Batal</Button>
          </DialogClose>
          <Button type="submit" :disabled="submitting">
            {{ submitting ? 'Menyimpan...' : isCreate ? 'Tambah pengguna' : 'Simpan perubahan' }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
