<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { InputGroup, InputGroupAddon, InputGroupInput } from '@/components/ui/input-group'
import {
  CheckCircleIcon,
  AlertIcon,
  LoaderCircleIcon,
  SearchIcon,
  UserIcon,
  XIcon,
} from '@/icons/layout'
import { http, normalizeApiError } from '@/services/http'

type User = { id: number; name: string; email: string; roles: string[] }
type UsersResponse = { data: User[]; meta?: { total: number } }
type Permission = { name: string; granted: boolean }
type RolePermissionResponse = { success: boolean; permissions: Record<string, Permission> }
type UserPermissionResponse = { success: boolean; permissions: Record<string, { name: string; status: 'INHERIT' | 'GRANT' | 'DENY'; granted: boolean }> }

const search = ref('')
const users = ref<User[]>([])
const selected = ref<User | null>(null)
const permissions = ref<Array<{ name: string; status: 'INHERIT' | 'GRANT' | 'DENY'; granted: boolean }>>([])
const initial = ref<Record<string, 'INHERIT' | 'GRANT' | 'DENY'>>({})
const loading = ref(false)
const userLoading = ref(false)
const saving = ref(false)
const error = ref<string | null>(null)
const success = ref<string | null>(null)

const eligibleUsers = computed(() => users.value.filter((user) => user.roles.some((role) => ['karyawan', 'kurir'].includes(role))))
const filteredUsers = computed(() => eligibleUsers.value.filter((user) => `${user.name} ${user.email}`.toLowerCase().includes(search.value.toLowerCase())))
const changed = computed(() => permissions.value.some((permission) => initial.value[permission.name] !== permission.status))
const overridesCount = computed(() => permissions.value.filter((permission) => permission.status !== 'INHERIT').length)

const modules: Record<string, string> = {
  dashboard: 'Dashboard',
  orders: 'Pesanan',
  users: 'Pengguna',
  services: 'Layanan',
  'service-times': 'Waktu Layanan',
  'payment-methods': 'Metode Pembayaran',
  payments: 'Pembayaran',
  laundry: 'Proses Laundry',
  delivery: 'Antar-Jemput',
  finance: 'Keuangan',
  reports: 'Laporan',
}

const groupedPermissions = computed(() => permissions.value.reduce<Record<string, typeof permissions.value>>((groups, permission) => {
  const key = permission.name.split('.')[0]
  ;(groups[modules[key] ?? key] ??= []).push(permission)
  return groups
}, {}))

async function loadUsers() {
  loading.value = true
  error.value = null
  try {
    const { data } = await http.get<UsersResponse>('/users', { params: { role: 'karyawan' } })
    const employees = data.data
    const { data: courierData } = await http.get<UsersResponse>('/users', { params: { role: 'kurir' } })
    users.value = [...employees, ...courierData.data]
    if (eligibleUsers.value.length > 0 && !selected.value) {
      await selectUser(eligibleUsers.value[0])
    }
  } catch (cause) {
    error.value = normalizeApiError(cause).message
  } finally {
    loading.value = false
  }
}

async function selectUser(user: User) {
  selected.value = user
  error.value = null
  success.value = null
  userLoading.value = true
  try {
    const role = user.roles.find((value) => ['karyawan', 'kurir'].includes(value))!
    const [{ data: roleData }, { data: userData }] = await Promise.all([
      http.get<RolePermissionResponse>(`/role-permission/${role}`),
      http.get<UserPermissionResponse>(`/user-permission/${user.id}`),
    ])
    permissions.value = Object.keys(roleData.permissions).map((name) => ({
      name,
      status: userData.permissions[name]?.status ?? 'INHERIT',
      granted: userData.permissions[name]?.granted ?? roleData.permissions[name].granted,
    }))
    initial.value = Object.fromEntries(permissions.value.map((permission) => [permission.name, permission.status]))
  } catch (cause) {
    error.value = normalizeApiError(cause).message
  } finally {
    userLoading.value = false
  }
}

async function save() {
  if (!selected.value || !changed.value) return
  saving.value = true
  error.value = null
  success.value = null
  try {
    for (const permission of permissions.value.filter((item) => initial.value[item.name] !== item.status)) {
      await http.put(`/user-permission/${selected.value.id}`, {
        permission_name: permission.name,
        status: permission.status,
      })
    }
    initial.value = Object.fromEntries(permissions.value.map((permission) => [permission.name, permission.status]))
    success.value = `Override permission untuk ${selected.value.name} berhasil disimpan.`
  } catch (cause) {
    error.value = normalizeApiError(cause).message
  } finally {
    saving.value = false
  }
}

function resetOne(permission: (typeof permissions.value)[number]) {
  permission.status = 'INHERIT'
}

async function resetAll() {
  if (!selected.value) return
  permissions.value.forEach((p) => { p.status = 'INHERIT' })
  await save()
}

watch(permissions, (items) => items.forEach((permission) => {
  if (permission.status === 'GRANT') permission.granted = true
  if (permission.status === 'DENY') permission.granted = false
}), { deep: true })

onMounted(loadUsers)
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">
      <PageBreadcrumb page-title="Manajemen Role & Permission" />

      <!-- Tab Navigation -->
      <div class="flex border-b border-gray-200 dark:border-gray-800">
        <RouterLink
          class="border-b-2 border-transparent px-5 py-3 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
          to="/settings/role-permission"
        >
          Permission Role
        </RouterLink>
        <RouterLink
          class="border-b-2 border-brand-500 px-5 py-3 text-sm font-medium text-brand-600 dark:text-brand-400"
          to="/settings/user-permission"
        >
          Permission Pengguna
        </RouterLink>
      </div>

      <!-- Feedback Alerts -->
      <div
        v-if="success"
        role="status"
        class="flex items-center justify-between gap-3 rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700 dark:border-success-500/20 dark:bg-success-500/10 dark:text-success-400"
      >
        <div class="flex items-center gap-2.5">
          <CheckCircleIcon class="size-5 shrink-0" />
          <span>{{ success }}</span>
        </div>
        <button type="button" class="rounded p-1 hover:bg-success-100 dark:hover:bg-success-500/20" @click="success = null">
          <XIcon class="size-4" />
        </button>
      </div>

      <div
        v-if="error"
        role="alert"
        class="flex items-center justify-between gap-3 rounded-xl border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-700 dark:border-error-500/20 dark:bg-error-500/10 dark:text-error-400"
      >
        <div class="flex items-center gap-2.5">
          <AlertIcon class="size-5 shrink-0" />
          <span>{{ error }}</span>
        </div>
        <button type="button" class="rounded p-1 hover:bg-error-100 dark:hover:bg-error-500/20" @click="error = null">
          <XIcon class="size-4" />
        </button>
      </div>

      <!-- Main Layout Grid -->
      <div class="grid gap-6 lg:grid-cols-12">
        <!-- Sidebar: User List -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 lg:col-span-4 dark:border-gray-800 dark:bg-white/3">
          <h3 class="mb-1 text-base font-semibold text-gray-800 dark:text-white/90">Pilih Akun</h3>
          <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">Pilih karyawan atau kurir untuk override permission.</p>
          
          <div class="mb-4">
            <InputGroup class="border-gray-300 dark:border-gray-700">
              <InputGroupAddon align="inline-start" class="ps-3.5">
                <SearchIcon class="size-5 text-gray-500 dark:text-gray-400" />
              </InputGroupAddon>
              <InputGroupInput
                v-model="search"
                type="search"
                placeholder="Cari nama atau email..."
                class="text-sm"
              />
            </InputGroup>
          </div>

          <div v-if="loading" class="py-12 text-center">
            <LoaderCircleIcon class="mx-auto size-6 animate-spin text-brand-500" />
            <p class="mt-2 text-xs text-gray-500">Memuat daftar akun...</p>
          </div>

          <div v-else-if="filteredUsers.length === 0" class="py-12 text-center">
            <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada akun Karyawan atau Kurir.</p>
          </div>

          <div v-else class="max-h-125 space-y-2 overflow-y-auto pr-1">
            <button
              v-for="user in filteredUsers"
              :key="user.id"
              type="button"
              class="w-full rounded-xl border p-3.5 text-left transition-all hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/5"
              :class="selected?.id === user.id ? 'border-brand-500 bg-brand-50/50 dark:bg-brand-500/10' : 'border-gray-200 bg-white dark:bg-gray-900'"
              @click="selectUser(user)"
            >
              <div class="flex items-center justify-between">
                <span class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ user.name }}</span>
                <Badge color="info" size="sm">
                  {{ user.roles.join(', ') }}
                </Badge>
              </div>
              <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400 truncate">{{ user.email }}</span>
            </button>
          </div>
        </div>

        <!-- Main Content: Permission Overrides -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 lg:col-span-8 lg:p-6 dark:border-gray-800 dark:bg-white/3">
          <template v-if="selected">
            <div class="flex flex-col gap-4 border-b border-gray-100 pb-5 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
              <div>
                <div class="flex items-center gap-2">
                  <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ selected.name }}</h3>
                  <Badge color="primary" size="sm">{{ selected.roles.join(', ') }}</Badge>
                </div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                  {{ selected.email }} · <span class="font-medium text-brand-600 dark:text-brand-400">{{ overridesCount }} override aktif</span>
                </p>
              </div>
              <Button
                variant="outline"
                size="sm"
                class="text-error-600 hover:bg-error-50 dark:hover:bg-error-500/10"
                :disabled="!overridesCount || saving"
                @click="resetAll"
              >
                Reset Semua Override
              </Button>
            </div>

            <div v-if="userLoading" class="py-24 text-center">
              <LoaderCircleIcon class="mx-auto size-8 animate-spin text-brand-500" />
              <p class="mt-3 text-sm text-gray-500">Memuat permission pengguna...</p>
            </div>

            <div v-else class="mt-5 space-y-6">
              <div
                v-for="(group, moduleName) in groupedPermissions"
                :key="moduleName"
                class="rounded-xl border border-gray-200 bg-gray-50/50 overflow-hidden dark:border-gray-800 dark:bg-gray-900/50"
              >
                <div class="border-b border-gray-200 px-4 py-3 bg-white dark:border-gray-800 dark:bg-gray-900">
                  <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ moduleName }}</h4>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-800">
                  <div
                    v-for="permission in group"
                    :key="permission.name"
                    class="flex flex-col gap-3 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between bg-white dark:bg-gray-900"
                  >
                    <div>
                      <span class="text-sm font-medium text-gray-800 dark:text-white/90">{{ permission.name }}</span>
                      <p class="text-xs text-gray-500 dark:text-gray-400">
                        Status efektif:
                        <span :class="permission.granted ? 'text-success-600 font-medium' : 'text-error-600 font-medium'">
                          {{ permission.granted ? 'Diizinkan' : 'Ditolak' }}
                        </span>
                      </p>
                    </div>

                    <div class="flex items-center gap-2">
                      <Select v-model="permission.status" :disabled="saving">
                        <SelectTrigger class="w-32">
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="INHERIT">INHERIT</SelectItem>
                          <SelectItem value="GRANT">GRANT</SelectItem>
                          <SelectItem value="DENY">DENY</SelectItem>
                        </SelectContent>
                      </Select>

                      <Button
                        v-if="permission.status !== 'INHERIT'"
                        variant="outline"
                        size="sm"
                        @click="resetOne(permission)"
                      >
                        Reset
                      </Button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Action Bar -->
              <div class="flex items-center justify-between border-t border-gray-200 pt-5 dark:border-gray-800">
                <span class="text-sm text-gray-500">
                  {{ changed ? 'Ada perubahan override yang belum disimpan.' : 'Tidak ada perubahan.' }}
                </span>
                <Button :disabled="!changed || saving" @click="save">
                  <LoaderCircleIcon v-if="saving" class="size-4 animate-spin mr-1.5" />
                  {{ saving ? 'Menyimpan...' : 'Simpan Override' }}
                </Button>
              </div>
            </div>
          </template>

          <div v-else class="py-32 text-center">
            <UserIcon class="mx-auto size-12 text-gray-300 dark:text-gray-600" />
            <p class="mt-3 text-sm font-medium text-gray-700 dark:text-gray-300">Pilih akun dari daftar di samping</p>
            <p class="mt-1 text-xs text-gray-500">Kelola izin khusus atau override per pengguna.</p>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
