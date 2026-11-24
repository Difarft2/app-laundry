# API Documentation — Pengaturan Global Aplikasi

**Project:** Pemuda Laundry
**API Version:** v1
**Base URL:** `http://127.0.0.1:8000/api/v1`

---

## 1. Deskripsi

Pengaturan global disimpan sebagai satu baris pada tabel `app_settings`
(`id_setting = 1`). Nilainya dipakai untuk branding seluruh dashboard dan
halaman masuk:

| Field | Keterangan |
|---|---|
| `nama_app` | Nama aplikasi, ditampilkan di sidebar, header, footer, dan judul halaman |
| `versi_app` | Versi aplikasi yang diedit admin, ditampilkan di footer dan widget Status App |
| `logo_path` | Path logo relatif terhadap `public/` (`/uploads/logo/<file>`) |
| `nomor_laundry` | Nomor kontak laundry, dipakai sebagai tautan WhatsApp di widget Status App |

`GET /settings` **sengaja publik** (tanpa token) agar halaman masuk bisa
menampilkan nama dan logo sebelum pengguna login. Mutasi hanya untuk role dengan
permission `settings.manage` — saat ini **hanya admin**. Owner, karyawan, dan
kurir mendapat `403` bila mencoba mengubah.

Versi build frontend berbeda dari `versi_app`: angkanya diambil dari
`dashboard/package.json` dan disuntikkan Vite sebagai `__APP_BUILD_VERSION__`,
lalu ditampilkan pada widget **Status App** di sidebar.

## 2. Endpoint

| Method | Path | Permission | Keterangan |
|---|---|---|---|
| `GET` | `/settings` | Publik | Ambil pengaturan global (default bila belum ada) |
| `PUT` | `/settings` | `settings.manage` | Perbarui nama, versi, nomor, dan/atau `logo_path` |
| `POST` | `/settings/logo` | `settings.manage` | Unggah berkas logo, mengganti logo lama |

Endpoint bertanda permission memakai header:

```http
Authorization: Bearer YOUR_ACCESS_TOKEN
Accept: application/json
```

## 3. GET /settings

**Response**

```json
{
  "success": true,
  "data": {
    "id_setting": 1,
    "nama_app": "Pemuda Laundry",
    "versi_app": "1.0.0",
    "logo_path": null,
    "logo_url": null,
    "nomor_laundry": "081217931918",
    "updated_at": "2026-11-24T11:21:34.000000Z"
  }
}
```

`logo_url` adalah URL absolut hasil `url($logo_path)`, atau `null` bila belum ada logo. Frontend memakai `logo_url` dan fallback ke logo SVG bawaan bila
gambar gagal dimuat.

## 4. PUT /settings

**Body**

| Field | Tipe | Wajib | Aturan |
|---|---|---|---|
| `nama_app` | string | Ya | Maksimal 150 karakter |
| `versi_app` | string | Ya | Maksimal 50 karakter |
| `nomor_laundry` | string | Ya | Maksimal 30 karakter, hanya angka dan karakter telepon (`+ - spasi ( )`) |
| `logo_path` | string | Tidak | Maksimal 500 karakter; biasanya diisi lewat endpoint unggah logo |

Contoh:

```json
{
  "nama_app": "Pemuda Laundry",
  "versi_app": "1.2.0",
  "nomor_laundry": "081217931918"
}
```

**Response**

```json
{
  "success": true,
  "message": "Pengaturan aplikasi berhasil diperbarui.",
  "data": { "...": "objek pengaturan seperti pada GET" }
}
```

Kesalahan validasi menghasilkan `422` dengan `errors` per field.

## 5. POST /settings/logo

Kirim sebagai `multipart/form-data`.

| Field | Tipe | Wajib | Aturan |
|---|---|---|---|
| `logo` | file | Ya | `png`, `jpg`, `jpeg`, `webp`, atau `svg`; maksimal 2 MB |

Logo disimpan ke `public/uploads/logo/logo-1-<random>.<ekstensi>` dan
`logo_path` diperbarui. File lama dihapus otomatis. Endpoint ini tidak memakai
symlink `storage`, sehingga `logo_url` langsung dapat dibuka dari domain yang
sama.

## 6. Tampilan frontend

- Halaman **Pengaturan** (`/settings`, menu Lainnya → Pengaturan) hanya
  muncul untuk role dengan `settings.manage`.
- Footer pada semua halaman dashboard menampilkan
  `© <tahun> <nama_app> · Versi <versi_app>`.
- Widget sidebar **Status App** menampilkan badge **Aktif**, `nama_app`,
  `Versi v<versi_app>`, `Build <versi build>`, dan tautan WhatsApp
  `nomor_laundry`. Widget **Status Outlet** sudah dihapus.
- Store `useSettingsStore` memuat `GET /settings` sekali saat aplikasi dibuka
  dan memakai nilai default bila API tidak tersedia.
