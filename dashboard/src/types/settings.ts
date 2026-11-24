export interface AppSetting {
  id_setting: number
  nama_app: string
  versi_app: string
  logo_path: string | null
  logo_url: string | null
  nomor_laundry: string
  updated_at?: string | null
}

export interface UpdateSettingPayload {
  nama_app: string
  versi_app: string
  nomor_laundry: string
  logo_path?: string | null
}

/** Dipakai saat API belum sempat memuat atau sedang bermasalah. */
export const DEFAULT_APP_SETTING: AppSetting = {
  id_setting: 1,
  nama_app: 'Pemuda Laundry',
  versi_app: '1.0.0',
  logo_path: null,
  logo_url: null,
  nomor_laundry: '081217931918',
}
