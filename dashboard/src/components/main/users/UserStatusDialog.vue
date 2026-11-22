<script setup lang="ts">
import { computed, ref, watch } from 'vue'
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
import { Field, FieldLabel } from '@/components/ui/field'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { normalizeApiError } from '@/services/http'
import { usersApi } from '@/services/users'
import type { AuthUser, UserStatus } from '@/types/auth'

const props = defineProps<{
  open: boolean
  user: AuthUser | null
}>()

const emit = defineEmits<{
  (e: 'update:open', open: boolean): void
  (e: 'updated', message: string): void
}>()

interface StatusOption {
  value: UserStatus
  label: string
  description: string
}

const STATUS_OPTIONS: StatusOption[] = [
  {
    value: 'active',
    label: 'Aktif',
    description: 'Pengguna dapat login dan memakai seluruh fitur sesuai role-nya.',
  },
  {
    value: 'pending',
    label: 'Menunggu persetujuan',
    description: 'Pengguna belum dapat login sampai akunnya disetujui admin.',
  },
  {
    value: 'rejected',
    label: 'Ditolak',
    description: 'Pengguna tidak dapat login ke sistem.',
  },
]

const status = ref<UserStatus>('active')
const submitting = ref(false)
const serverError = ref<string | null>(null)

const selectedOption = computed(
  () => STATUS_OPTIONS.find((option) => option.value === status.value) ?? STATUS_OPTIONS[0],
)

const isUnchanged = computed(() => props.user?.status === status.value)

watch(
  () => props.open,
  (open) => {
    if (!open) return
    status.value = props.user?.status ?? 'active'
    submitting.value = false
    serverError.value = null
  },
  { immediate: true },
)

const onConfirm = async () => {
  if (!props.user || isUnchanged.value) return

  submitting.value = true
  serverError.value = null

  try {
    const response = await usersApi.updateStatus(props.user.id, status.value)
    emit('updated', response.message || 'Status akun berhasil diperbarui.')
    emit('update:open', false)
  } catch (error) {
    serverError.value = normalizeApiError(error).message
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
          Ubah status akun
        </DialogTitle>
        <DialogDescription class="text-sm text-gray-500 dark:text-gray-400">
          Atur status akun
          <span class="font-medium text-gray-700 dark:text-gray-300">{{ user?.name }}</span
          >. Akun yang tidak aktif akan langsung kehilangan sesi loginnya.
        </DialogDescription>
      </DialogHeader>

      <div
        v-if="serverError"
        role="alert"
        class="rounded-lg border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-700 dark:border-error-500/20 dark:bg-error-500/10 dark:text-error-400"
      >
        {{ serverError }}
      </div>

      <Field class="gap-1.5">
        <FieldLabel for="user-status">Status akun</FieldLabel>
        <Select v-model="status">
          <SelectTrigger id="user-status" class="w-full" aria-label="Status akun">
            <SelectValue placeholder="Pilih status akun" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="option in STATUS_OPTIONS" :key="option.value" :value="option.value">
              {{ option.label }}
            </SelectItem>
          </SelectContent>
        </Select>
        <p class="text-xs text-gray-500 dark:text-gray-400">
          {{ selectedOption.description }}
        </p>
      </Field>

      <DialogFooter class="mx-0 mb-0 gap-3 p-0 pt-2 sm:justify-end">
        <DialogClose as-child>
          <Button variant="outline" type="button" :disabled="submitting">Batal</Button>
        </DialogClose>
        <Button
          type="button"
          :disabled="submitting || isUnchanged"
          @click="onConfirm"
        >
          {{ submitting ? 'Menyimpan...' : 'Simpan status' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
