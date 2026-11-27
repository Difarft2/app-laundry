<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Switch } from '@/components/ui/switch'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  CheckCircleIcon,
  LoaderCircleIcon,
  XIcon,
  InfoCircleIcon,
  AlertIcon,
} from '@/icons/layout'
import { http, normalizeApiError } from '@/services/http'

type Permission = { name: string; granted: boolean }
type RoleResponse = { success: boolean; role: string; permissions: Record<string, Permission>; message?: string }

const selectedRole = ref<'karyawan' | 'kurir'>('karyawan')
const permissions = ref<Permission[]>([])
const initial = ref<Record<string, boolean>>({})
const loading = ref(false)
const saving = ref(false)
const error = ref<string | null>(null)
const success = ref<string | null>(null)

const changed = computed(() => permissions.value.some((permission) => initial.value[permission.name] !== permission.granted))

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

const groupedPermissions = computed(() => permissions.value.reduce<Record<string, Permission[]>>((groups, permission) => {
  const key = permission.name.split('.')[0]
  ;(groups[modules[key] ?? key] ??= []).push(permission)
  return groups
}, {}))

async function load() {
  loading.value = true
  error.value = null
  success.value = null
  try {
    const { data } = await http.get<RoleResponse>(`/role-permission/${selectedRole.value}`)
    if (!data.success) throw new Error(data.message ?? 'Permission tidak dapat dimuat.')
    permissions.value = Object.values(data.permissions)
    initial.value = Object.fromEntries(permissions.value.map((permission) => [permission.name, permission.granted]))
  } catch (cause) {
    error.value = cause instanceof Error ? cause.message : normalizeApiError(cause).message
  } finally {
    loading.value = false
  }
}

function reset() {
  permissions.value.forEach((permission) => { permission.granted = initial.value[permission.name] })
  success.value = null
}

async function save() {
  if (!changed.value) return
  saving.value = true
  error.value = null
  success.value = null
  try {
    await http.put(`/role-permission/${selectedRole.value}`, {
      permissions: permissions.value.map((permission) => ({
        name: permission.name,
        status: permission.granted ? 'GRANT' : 'REVOKE',
      })),
    })
    initial.value = Object.fromEntries(permissions.value.map((permission) => [permission.name, permission.granted]))
    success.value = `Permission role ${selectedRole.value} berhasil disimpan.`
  } catch (cause) {
    error.value = normalizeApiError(cause).message
  } finally {
    saving.value = false
  }
}

watch(selectedRole, load)
onMounted(load)
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">
      <PageBreadcrumb page-title="Manajemen Role & Permission" />

      <!-- Tab Navigation -->
      <div class="flex border-b border-gray-200 dark:border-gray-800">
        <RouterLink
          class="border-b-2 border-brand-500 px-5 py-3 text-sm font-medium text-brand-600 dark:text-brand-400"
          to="/settings/role-permission"
        >
          Permission Role
        </RouterLink>
        <RouterLink
          class="border-b-2 border-transparent px-5 py-3 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
          to="/settings/user-permission"
        >
          Permission Pengguna
        </RouterLink>
      </div>

      <!-- Role Selection Card -->
      <div class="rounded-2xl border border-gray-200 bg-white p-5 lg:p-6 dark:border-gray-800 dark:bg-white/3">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Pilih Role</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              Pilih role Karyawan atau Kurir untuk mengatur hak akses modul. Admin dan Owner dilindungi dari perubahan langsung.
            </p>
          </div>
          <div class="w-full sm:w-64">
            <Select v-model="selectedRole" :disabled="loading || saving">
              <SelectTrigger>
                <SelectValue placeholder="Pilih role" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="karyawan">Karyawan</SelectItem>
                <SelectItem value="kurir">Kurir</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>
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

      <!-- Loading State -->
      <div v-if="loading" class="rounded-2xl border border-gray-200 bg-white py-16 text-center dark:border-gray-800 dark:bg-white/3">
        <LoaderCircleIcon class="mx-auto size-8 animate-spin text-brand-500" />
        <p class="mt-3 text-sm font-medium text-gray-600 dark:text-gray-400">Memuat data permission...</p>
      </div>

      <!-- Permission Modules List -->
      <template v-else>
        <div class="grid gap-6">
          <div
            v-for="(group, moduleName) in groupedPermissions"
            :key="moduleName"
            class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/3"
          >
            <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
              <h4 class="font-semibold text-gray-800 dark:text-white/90">{{ moduleName }}</h4>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-800">
              <div
                v-for="permission in group"
                :key="permission.name"
                class="flex items-center justify-between px-5 py-3.5 transition-colors hover:bg-gray-50/50 dark:hover:bg-white/2"
              >
                <div>
                  <span class="text-sm font-medium text-gray-800 dark:text-white/90">{{ permission.name }}</span>
                  <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ permission.granted ? 'Akses diizinkan' : 'Akses ditolak' }}
                  </p>
                </div>
                <div class="flex items-center gap-3">
                  <Badge :color="permission.granted ? 'success' : 'gray'" size="sm">
                    {{ permission.granted ? 'Aktif' : 'Nonaktif' }}
                  </Badge>
                  <Switch
                    v-model:checked="permission.granted"
                    :disabled="saving"
                    aria-label="Toggle permission"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Sticky / Bottom Action Bar -->
        <div class="sticky bottom-6 flex items-center justify-between rounded-2xl border border-gray-200 bg-white/95 p-4 shadow-lg backdrop-blur-sm sm:px-6 dark:border-gray-800 dark:bg-gray-900/95">
          <div class="flex items-center gap-2 text-sm">
            <span
              class="inline-block size-2 rounded-full"
              :class="changed ? 'bg-amber-500 animate-pulse' : 'bg-green-500'"
            />
            <span class="font-medium text-gray-700 dark:text-gray-300">
              {{ changed ? 'Ada perubahan yang belum disimpan.' : 'Semua perubahan telah tersimpan.' }}
            </span>
          </div>
          <div class="flex items-center gap-3">
            <Button variant="outline" :disabled="!changed || saving" @click="reset">
              Batal
            </Button>
            <Button :disabled="!changed || saving" @click="save">
              <LoaderCircleIcon v-if="saving" class="size-4 animate-spin mr-1.5" />
              {{ saving ? 'Menyimpan...' : 'Simpan Perubahan' }}
            </Button>
          </div>
        </div>
      </template>
    </div>
  </AdminLayout>
</template>
