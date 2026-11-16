<script setup lang="ts">
import { ref, watch } from 'vue'
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
import { normalizeApiError } from '@/services/http'
import { usersApi } from '@/services/users'
import type { AuthUser } from '@/types/auth'

const props = defineProps<{
  open: boolean
  user: AuthUser | null
}>()

const emit = defineEmits<{
  (e: 'update:open', open: boolean): void
  (e: 'deleted', message: string): void
}>()

const submitting = ref(false)
const serverError = ref<string | null>(null)

watch(
  () => props.open,
  (open) => {
    if (!open) return
    submitting.value = false
    serverError.value = null
  },
  { immediate: true },
)

const onConfirm = async () => {
  if (!props.user) return

  submitting.value = true
  serverError.value = null

  try {
    const response = await usersApi.remove(props.user.id)
    emit('deleted', response.message || 'Pengguna berhasil dihapus.')
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
          Hapus pengguna
        </DialogTitle>
        <DialogDescription class="text-sm text-gray-500 dark:text-gray-400">
          Pengguna
          <span class="font-medium text-gray-700 dark:text-gray-300">{{ user?.name }}</span>
          akan dihapus permanen beserta seluruh token aksesnya. Tindakan ini tidak dapat dibatalkan.
        </DialogDescription>
      </DialogHeader>

      <div
        v-if="serverError"
        role="alert"
        class="rounded-lg border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-700 dark:border-error-500/20 dark:bg-error-500/10 dark:text-error-400"
      >
        {{ serverError }}
      </div>

      <DialogFooter class="mx-0 mb-0 gap-3 p-0 pt-2 sm:justify-end">
        <DialogClose as-child>
          <Button variant="outline" type="button" :disabled="submitting">Batal</Button>
        </DialogClose>
        <Button variant="destructive" type="button" :disabled="submitting" @click="onConfirm">
          {{ submitting ? 'Menghapus...' : 'Hapus pengguna' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
