import { isEmail } from '@/composables/useFormValidation'

export interface SignInFormData {
  email: string
  password: string
  keepLoggedIn?: boolean
}

export const validateSignIn = (data: SignInFormData) => {
  const errors: Partial<Record<keyof SignInFormData, string>> = {}

  if (!data.email.trim()) errors.email = 'Email wajib diisi.'
  else if (!isEmail(data.email.trim())) errors.email = 'Format email tidak valid.'

  if (!data.password) errors.password = 'Password wajib diisi.'

  return errors
}
