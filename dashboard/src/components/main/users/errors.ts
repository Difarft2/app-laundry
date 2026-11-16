import type { ApiFieldErrors } from '@/services/http'

const fieldLabels: Record<string, string> = {
  name: 'Nama',
  email: 'Email',
  no_hp: 'Nomor HP',
  password: 'Password',
  current_password: 'Password saat ini',
  roles: 'Role',
}

/**
 * Laravel mengirim pesan validasi dalam bahasa Inggris. Terjemahkan pola
 * yang umum agar UI tetap berbahasa Indonesia; pesan lain ditampilkan
 * apa adanya supaya informasi dari server tidak hilang.
 */
const translateMessage = (field: string, message: string): string => {
  const lower = message.toLowerCase()
  const label = fieldLabels[field] ?? 'Data'

  if (lower.includes('already been taken')) return `${label} sudah digunakan.`
  if (lower.includes('must be a valid email')) return 'Format email tidak valid.'
  if (lower.includes('confirmation does not match')) return 'Konfirmasi password tidak sama.'
  if (lower.includes('must be at least')) return `${label} minimal 8 karakter.`
  if (lower.includes('must not be greater than')) {
    return field === 'no_hp' ? 'Nomor HP maksimal 20 karakter.' : `${label} terlalu panjang.`
  }
  if (lower.includes('is required')) return `${label} wajib diisi.`
  if (lower.includes('must be a string')) return `${label} tidak valid.`

  return message
}

export const translateFieldErrors = (errors?: ApiFieldErrors): Record<string, string> => {
  const result: Record<string, string> = {}

  if (!errors) return result

  for (const [field, messages] of Object.entries(errors)) {
    const first = messages?.[0]
    if (!first) continue

    if (field.startsWith('roles.')) {
      result.roles = 'Role yang dipilih tidak valid.'
      continue
    }

    const key = field === 'no_hp' ? 'noHp' : field
    result[key] = translateMessage(field, first)
  }

  return result
}
