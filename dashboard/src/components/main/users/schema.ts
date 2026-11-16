import { isEmail } from '@/composables/useFormValidation'

export type UserFormMode = 'create' | 'edit'

export interface UserFormData {
  name: string
  email: string
  noHp: string
  password: string
  passwordConfirmation: string
  roles: string[]
}

export const emptyUserForm = (): UserFormData => ({
  name: '',
  email: '',
  noHp: '',
  password: '',
  passwordConfirmation: '',
  roles: [],
})

/**
 * Validasi mengikuti StoreUserRequest / UpdateUserRequest di backend.
 * `password` hanya divalidasi saat membuat pengguna; reset password
 * memiliki dialog tersendiri.
 */
export const validateUserForm = (
  data: UserFormData,
  mode: UserFormMode,
): Partial<Record<keyof UserFormData, string>> => {
  const errors: Partial<Record<keyof UserFormData, string>> = {}

  if (!data.name.trim()) errors.name = 'Nama wajib diisi.'
  else if (data.name.trim().length > 255) errors.name = 'Nama maksimal 255 karakter.'

  if (!data.email.trim()) errors.email = 'Email wajib diisi.'
  else if (!isEmail(data.email.trim())) errors.email = 'Format email tidak valid.'
  else if (data.email.trim().length > 255) errors.email = 'Email maksimal 255 karakter.'

  if (data.noHp.trim().length > 20) errors.noHp = 'Nomor HP maksimal 20 karakter.'

  if (mode === 'create') {
    if (!data.password) errors.password = 'Password wajib diisi.'
    else if (data.password.length < 8) errors.password = 'Password minimal 8 karakter.'

    if (data.password && data.password !== data.passwordConfirmation) {
      errors.passwordConfirmation = 'Konfirmasi password tidak sama.'
    }
  }

  if (data.roles.length === 0) errors.roles = 'Pilih minimal satu role.'

  return errors
}

export interface ResetPasswordFormData {
  password: string
  passwordConfirmation: string
}

export const emptyResetPasswordForm = (): ResetPasswordFormData => ({
  password: '',
  passwordConfirmation: '',
})

export const validateResetPassword = (
  data: ResetPasswordFormData,
): Partial<Record<keyof ResetPasswordFormData, string>> => {
  const errors: Partial<Record<keyof ResetPasswordFormData, string>> = {}

  if (!data.password) errors.password = 'Password baru wajib diisi.'
  else if (data.password.length < 8) errors.password = 'Password minimal 8 karakter.'

  if (data.password && data.password !== data.passwordConfirmation) {
    errors.passwordConfirmation = 'Konfirmasi password tidak sama.'
  }

  return errors
}
