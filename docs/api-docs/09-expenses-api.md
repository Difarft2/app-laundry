# API Documentation — Expenses (Pengeluaran)

**Project:** Pemuda Laundry
**API Version:** v1
**Base URL:** `http://127.0.0.1:8000/api/v1`
**Authentication:** Laravel Sanctum (`Authorization: Bearer {token}`)

---

## 1. Deskripsi

Modul pengeluaran mencatat biaya operasional outlet. Data ini menjadi sumber
`metrics.expenses` dan `charts.expenses` pada
[Dashboard API](./08-dashboard-api.md).

- Role dengan `finance.view` (owner, admin) dapat melihat data.
- Role dengan `finance.manage` (**owner Only**) dapat mencatat, mengubah, dan
  menghapus data. Admin bersifat read-only.
- Karyawan dan kurir tidak memiliki akses ke endpoint ini.

## 2. Endpoint

| Method | Path | Permission | Keterangan |
|---|---|---|---|
| `GET` | `/expenses` | `finance.view` | Daftar pengeluaran (pagination) |
| `POST` | `/expenses` | `finance.manage` | Catat pengeluaran |
| `PUT` | `/expenses/{pengeluaran}` | `finance.manage` | Ubah pengeluaran |
| `DELETE` | `/expenses/{pengeluaran}` | `finance.manage` | Hapus pengeluaran |

`{pengeluaran}` adalah `id_pengeluaran`.

## 3. GET /expenses

**Query parameter**

| Nama | Tipe | Keterangan |
|---|---|---|
| `search` | string | Cari di `kode_pengeluaran`, `kategori`, atau `deskripsi` |
| `kategori` | string | Filter kategori persis |
| `tanggal_mulai` | date | Filter tanggal paling awal |
| `tanggal_selesai` | date | Filter tanggal paling akhir |
| `per_page` | integer | 1–100, default 15 |

**Response**

```json
{
  "current_page": 1,
  "data": [
    {
      "id_pengeluaran": 1,
      "kode_pengeluaran": "PGL-20261124-A1B2C",
      "kategori": "Listrik",
      "deskripsi": "Listrik outlet pusat",
      "jumlah": "125000.00",
      "tanggal": "2026-11-24T00:00:00.000000Z",
      "id_user": 1,
      "created_at": "2026-11-24T08:00:00.000000Z",
      "updated_at": "2026-11-24T08:00:00.000000Z",
      "user": { "id": 1, "name": "Owner Laundry" }
    }
  ],
  "last_page": 1,
  "per_page": 15,
  "total": 1
}
```

## 4. POST /expenses

**Body**

| Field | Tipe | Wajib | Aturan |
|---|---|---|---|
| `kategori` | string | Ya | Maksimal 100 karakter |
| `deskripsi` | string | Tidak | Maksimal 255 karakter |
| `jumlah` | number | Ya | Minimal 0 |
| `tanggal` | date | Ya | Format tanggal valid |

`kode_pengeluaran` dan `id_user` diisi backend. Format kode:
`PGL-YYYYMMDD-XXXXX`.

```json
{
  "kategori": "Listrik",
  "deskripsi": "Listrik outlet pusat",
  "jumlah": 125000,
  "tanggal": "2026-11-24"
}
```

**Response `201`**

```json
{
  "success": true,
  "message": "Pengeluaran berhasil dicatat.",
  "data": { "id_pengeluaran": 1, "kode_pengeluaran": "PGL-20261124-A1B2C" }
}
```

## 5. PUT /expenses/{pengeluaran}

Field sama dengan `POST`, tetapi semua opsional (`sometimes`); hanya field yang
dikirim yang diperbarui. Balasan `200` dengan `success`, `message`, dan `data`.

## 6. DELETE /expenses/{pengeluaran}

**Response**

```json
{
  "success": true,
  "message": "Pengeluaran berhasil dihapus."
}
```

## 7. Error

- `401` — token tidak ada/tidak valid.
- `403` — role tidak memiliki permission yang dibutuhkan.
- `404` — `id_pengeluaran` tidak ditemukan.
- `422` — validasi gagal; baca field pada `errors` untuk pesan per field.
