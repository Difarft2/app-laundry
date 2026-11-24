<script setup lang="ts">
import { ref, watch } from 'vue'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Field, FieldError, FieldLabel } from '@/components/ui/field'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { expenseApi } from '@/services/expenses'
import { normalizeApiError } from '@/services/http'

const props = defineProps<{ open: boolean }>()

const emit = defineEmits<{
  (e: 'update:open', open: boolean): void
  (e: 'created', message: string): void
}>()

const kategori = ref('')
const deskripsi = ref('')
const jumlah = ref('')
const tanggal = ref(new Date().toISOString().slice(0, 10))
const submitting = ref(false)
const serverError = ref<string | null>(null)

watch(
  () => props.open,
  (open) => {
    if (!open) return
    kategori.value = ''
    deskripsi.value = ''
    jumlah.value = ''
    tanggal.value = new Date().toISOString().slice(0, 10)
    serverError.value = null
  },
)

const onSubmit = async (event?: Event) => {
  event?.preventDefault()
  serverError.value = null

  if (!kategori.value.trim() || !jumlah.value || !tanggal.value) {
    serverError.value = 'Kategori, jumlah, dan tanggal wajib diisi.'
    return
  }

  submitting.value = true

  try {
    await expenseApi.create({
      kategori: kategori.value.trim(),
      deskripsi: deskripsi.value.trim() || undefined,
      jumlah: Number(jumlah.value),
      tanggal: tanggal.value,
    })

    emit('created', 'Pengeluaran berhasil dicatat.')
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
    <DialogContent class="max-w-[calc(100%-2rem)] sm:max-w-lg">
      <DialogHeader class="gap-2 pe-10 text-start">
        <DialogTitle class="text-xl font-semibold text-gray-800 dark:text-white/90">
          Catat pengeluaran
        </DialogTitle>
        <DialogDescription class="text-sm text-gray-500 dark:text-gray-400">
          Data ini langsung masuk ke laporan pengeluaran dan dashboard owner.
        </DialogDescription>
      </DialogHeader>

      <form class="space-y-4" @submit="onSubmit">
        <Field>
          <FieldLabel>Kategori</FieldLabel>
          <Input v-model="kategori" placeholder="Contoh: Listrik" />
        </Field>

        <div class="grid gap-4 sm:grid-cols-2">
          <Field>
            <FieldLabel>Jumlah (Rp)</FieldLabel>
            <Input v-model="jumlah" type="number" min="0" step="1000" placeholder="0" />
          </Field>
          <Field>
            <FieldLabel>Tanggal</FieldLabel>
            <Input v-model="tanggal" type="date" />
          </Field>
        </div>

        <Field>
          <FieldLabel>Deskripsi</FieldLabel>
          <Textarea v-model="deskripsi" rows="3" placeholder="Keterangan opsional" />
        </Field>

        <FieldError v-if="serverError">{{ serverError }}</FieldError>

        <DialogFooter>
          <Button type="button" variant="outline" @click="emit('update:open', false)">
            Batal
          </Button>
          <Button type="submit" :disabled="submitting">
            {{ submitting ? 'Menyimpan…' : 'Simpan' }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
