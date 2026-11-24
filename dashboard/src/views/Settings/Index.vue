<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Field, FieldError, FieldLabel } from '@/components/ui/field'
import { FileInput } from '@/components/ui/file-input'
import { Input } from '@/components/ui/input'
import { normalizeApiError, type ApiFieldErrors } from '@/services/http'
import { useSettingsStore } from '@/stores/settings'
import type { UpdateSettingPayload } from '@/types/settings'

const settings = useSettingsStore()

const form = reactive<UpdateSettingPayload>({
  nama_app: '',
  versi_app: '',
  nomor_laundry: '',
  logo_path: null,
})
const fieldErrors = ref<ApiFieldErrors>({})
const generalError = ref<string | null>(null)
const successMessage = ref<string | null>(null)
const logoFile = ref<File | null>(null)
const logoPreview = ref<string | null>(null)

const errorOf = (field: string): string | undefined => fieldErrors.value[field]?.[0]

const previewSource = computed(
  () => logoPreview.value ?? settings.current.logo_url ?? null,
)

const syncForm = () => {
  form.nama_app = settings.current.nama_app
  form.versi_app = settings.current.versi_app
  form.nomor_laundry = settings.current.nomor_laundry
  form.logo_path = settings.current.logo_path
}

onMounted(async () => {
  await settings.load()
  syncForm()
})

onBeforeUnmount(() => {
  if (logoPreview.value) URL.revokeObjectURL(logoPreview.value)
})

const onLogoChange = (event: Event) => {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0] ?? null

  if (logoPreview.value) URL.revokeObjectURL(logoPreview.value)
  logoFile.value = file
  logoPreview.value = file ? URL.createObjectURL(file) : null
}

const clearMessages = () => {
  fieldErrors.value = {}
  generalError.value = null
  successMessage.value = null
}

const save = async () => {
  clearMessages()

  try {
    await settings.update({
      nama_app: form.nama_app.trim(),
      versi_app: form.versi_app.trim(),
      nomor_laundry: form.nomor_laundry.trim(),
    })

    if (logoFile.value) {
      await settings.uploadLogo(logoFile.value)
    }

    logoFile.value = null
    if (logoPreview.value) {
      URL.revokeObjectURL(logoPreview.value)
      logoPreview.value = null
    }
    const logoInput = document.getElementById('logo') as HTMLInputElement | null
    if (logoInput) logoInput.value = ''
    syncForm()
    successMessage.value = 'Pengaturan aplikasi berhasil disimpan.'
  } catch (error) {
    const apiError = normalizeApiError(error)
    fieldErrors.value = apiError.errors ?? {}
    if (Object.keys(fieldErrors.value).length === 0) {
      generalError.value = apiError.message
    }
  }
}

const reset = async () => {
  clearMessages()
  await settings.load(true)
  syncForm()
}
</script>

<template>
  <AdminLayout>
    <div>
      <PageBreadcrumb page-title="Pengaturan" />

      <div class="grid gap-6 xl:grid-cols-12">
        <form
          class="rounded-2xl border border-gray-200 bg-white p-5 lg:p-6 xl:col-span-8 dark:border-gray-800 dark:bg-white/3"
          novalidate
          @submit.prevent="save"
        >
          <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 pb-5 dark:border-gray-800">
            <div>
              <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                Identitas aplikasi
              </h3>
              <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Berlaku untuk seluruh dashboard dan halaman masuk. Hanya admin yang dapat mengubah.
              </p>
            </div>
            <Badge color="info" size="sm">Admin only</Badge>
          </div>

          <div
            v-if="successMessage"
            role="status"
            class="mt-5 rounded-lg border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700 dark:border-success-500/20 dark:bg-success-500/10 dark:text-success-400"
          >
            {{ successMessage }}
          </div>
          <div
            v-if="generalError"
            role="alert"
            class="mt-5 rounded-lg border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-700 dark:border-error-500/20 dark:bg-error-500/10 dark:text-error-400"
          >
            {{ generalError }}
          </div>

          <div class="mt-5 grid gap-5 sm:grid-cols-2">
            <Field>
              <FieldLabel for="nama_app">Nama aplikasi</FieldLabel>
              <Input id="nama_app" v-model="form.nama_app" maxlength="150" />
              <FieldError v-if="errorOf('nama_app')">{{ errorOf('nama_app') }}</FieldError>
            </Field>

            <Field>
              <FieldLabel for="versi_app">Versi aplikasi</FieldLabel>
              <Input id="versi_app" v-model="form.versi_app" maxlength="50" />
              <FieldError v-if="errorOf('versi_app')">{{ errorOf('versi_app') }}</FieldError>
            </Field>

            <Field class="sm:col-span-2">
              <FieldLabel for="nomor_laundry">Nomor laundry</FieldLabel>
              <Input
                id="nomor_laundry"
                v-model="form.nomor_laundry"
                maxlength="30"
                placeholder="08xxxxxxxxxx"
              />
              <p class="text-xs text-gray-500 dark:text-gray-400">
                Dipakai sebagai tautan WhatsApp pada dashboard.
              </p>
              <FieldError v-if="errorOf('nomor_laundry')">{{ errorOf('nomor_laundry') }}</FieldError>
            </Field>
          </div>

          <div class="mt-6 flex flex-wrap items-center justify-end gap-3">
            <Button type="button" variant="outline" :disabled="settings.saving" @click="reset">
              Kembalikan
            </Button>
            <Button type="submit" :disabled="settings.saving">
              {{ settings.saving ? 'Menyimpan...' : 'Simpan pengaturan' }}
            </Button>
          </div>
        </form>

        <div
          class="rounded-2xl border border-gray-200 bg-white p-5 lg:p-6 xl:col-span-4 dark:border-gray-800 dark:bg-white/3"
        >
          <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Logo aplikasi</h3>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Format PNG, JPG, WEBP, atau SVG maksimal 2 MB.
          </p>

          <div
            class="mt-5 flex items-center gap-4 rounded-xl border border-gray-200 p-4 dark:border-gray-800"
          >
            <img
              v-if="previewSource"
              :src="previewSource"
              :alt="`Logo ${settings.current.nama_app}`"
              class="size-14 rounded-xl bg-gray-50 object-contain p-1 dark:bg-white/5"
            />
            <span
              v-else
              class="flex size-14 items-center justify-center rounded-xl bg-brand-50 text-sm font-semibold text-brand-600 dark:bg-brand-500/15 dark:text-brand-400"
            >
              Logo
            </span>
            <div class="min-w-0">
              <p class="truncate text-sm font-medium text-gray-800 dark:text-white/90">
                {{ logoFile?.name ?? 'Logo saat ini' }}
              </p>
              <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                Tampil di sidebar dan halaman masuk.
              </p>
            </div>
          </div>

          <form class="mt-5" novalidate @submit.prevent="save">
            <Field>
              <FieldLabel for="logo">Unggah logo baru</FieldLabel>
              <FileInput
                id="logo"
                accept="image/png,image/jpeg,image/webp,image/svg+xml"
                @change="onLogoChange"
              />
              <FieldError v-if="errorOf('logo')">{{ errorOf('logo') }}</FieldError>
            </Field>
            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
              Logo akan ikut tersimpan saat tombol <strong>Simpan pengaturan</strong> ditekan.
            </p>
          </form>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
