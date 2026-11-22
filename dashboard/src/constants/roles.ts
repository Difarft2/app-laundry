export type ApiRole = 'owner' | 'admin' | 'karyawan' | 'kurir'

export type RoleBadgeColor = 'primary' | 'success' | 'error' | 'warning' | 'info' | 'gray'

export interface RoleOption {
  value: ApiRole
  label: string
  description: string
  color: RoleBadgeColor
}

/**
 * Role mengikuti RolePermissionSeeder di backend.
 */
export const ROLE_OPTIONS: RoleOption[] = [
  {
    value: 'owner',
    label: 'Owner',
    description: 'Memantau transaksi, laporan, histori, dan mengelola akun pengguna.',
    color: 'primary',
  },
  {
    value: 'admin',
    label: 'Admin',
    description: 'Akses baca untuk riwayat pesanan dan seluruh data historis.',
    color: 'info',
  },
  {
    value: 'karyawan',
    label: 'Karyawan',
    description: 'Membuat pesanan serta mengelola operasional dan master data laundry.',
    color: 'warning',
  },
  {
    value: 'kurir',
    label: 'Kurir',
    description: 'Hanya mengelola tugas antar-jemput.',
    color: 'success',
  },
]

const roleMap = new Map(ROLE_OPTIONS.map((role) => [role.value, role]))

export const isApiRole = (value: string): value is ApiRole => roleMap.has(value as ApiRole)

export const roleLabel = (role: string): string =>
  isApiRole(role) ? (roleMap.get(role)?.label ?? role) : role

export const roleColor = (role: string): RoleBadgeColor =>
  isApiRole(role) ? (roleMap.get(role)?.color ?? 'gray') : 'gray'

export const roleDescription = (role: string): string =>
  isApiRole(role) ? (roleMap.get(role)?.description ?? '') : ''

export const dashboardRoleFromRoles = (
  roles: string[],
): 'owner' | 'admin' | 'karyawan' | 'kurir' | null => {
  for (const role of ['owner', 'admin', 'karyawan', 'kurir'] as const) {
    if (roles.includes(role)) return role
  }
  return null
}
