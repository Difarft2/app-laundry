# Payments API Documentation

**Project:** Pemuda Laundry
**Module:** Payment History & Recording
**API Version:** v1
**Base URL:** `http://localhost:8000/api/v1`
**Authentication:** Laravel Sanctum
**Content-Type:** `application/json`

---

## 1. Overview

Payments API digunakan untuk mencatat dan melihat pembayaran pesanan, khususnya
pesanan yang dibayar nanti (`bayar nanti`).

Fitur yang tersedia:

- Mencatat pembayaran lanjutan: DP, angsuran, dan pelunasan.
- Melihat riwayat seluruh pembayaran (dengan filter dan pagination).
- Melihat detail satu pembayaran.
- Menampilkan riwayat pembayaran pada resource pesanan.

Data pembayaran **hanya** disimpan pada tabel `pembayaran`, tidak pernah
disimpan pada tabel `pesanan`. Tabel `pesanan` hanya menyimpan total tagihan
(`total_harga`); nilai terbayar dan sisa dihitung dari tabel `pembayaran`.

## 2. Authentication

Seluruh endpoint Payments membutuhkan autentikasi Laravel Sanctum.

```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer {token}
```

Token diperoleh melalui endpoint login.

## 3. Permission

| Role | Lihat riwayat (`payments.view`) | Catat pembayaran (`payments.create`) |
|---|---|---|
| Owner | ✓ | - |
| Admin | ✓ | - |
| Karyawan | ✓ | ✓ |
| Kurir | - | - |

Daftar permission:

- `payments.view` — melihat daftar dan detail pembayaran
- `payments.create` — mencatat pembayaran baru

Admin dan owner bersifat **read-only**: hanya dapat melihat riwayat pembayaran.
Hanya karyawan yang dapat mencatat pembayaran baru. Kurir tidak memiliki akses
ke modul pembayaran.

Jika pengguna tidak memiliki permission yang dibutuhkan, API mengembalikan
**HTTP 403 Forbidden**.

## 4. Struktur Data

Tabel database: `pembayaran`

| Field | Tipe | Keterangan |
|---|---|---|
| `id_pembayaran` | BIGINT | Primary key |
| `id_pesanan` | BIGINT | Relasi ke `pesanan` |
| `id_metode_pembayaran` | BIGINT | Relasi ke `metode_pembayaran` |
| `id_user` | BIGINT/null | Pengguna yang mencatat pembayaran |
| `kode_pembayaran` | VARCHAR(50) | Kode unik, format `PAY-YYYYMMDD-XXXXXX` |
| `jumlah_bayar` | DECIMAL(12,2) | Nominal pembayaran |
| `jenis_pembayaran` | ENUM | `dp`, `angsuran`, `pelunasan` |
| `status_pembayaran` | ENUM | `berhasil`, `menunggu`, `dibatalkan` |
| `tanggal_pembayaran` | DATETIME | Waktu pembayaran |
| `catatan` | TEXT/null | Catatan tambahan |
| `created_at` | TIMESTAMP | Waktu dibuat |
| `updated_at` | TIMESTAMP | Waktu diperbarui |

Hanya pembayaran dengan status `berhasil` yang dihitung sebagai `total_terbayar`
dan mengurangi `sisa_pembayaran`.

### 4.1 Bentuk Resource Pembayaran

```json
{
    "id_pembayaran": 3,
    "kode_pembayaran": "PAY-20261123-6BWOKU",
    "id_pesanan": 1,
    "kode_pesanan": "ORD-20261122-4KKRMY8C",
    "nama_pelanggan": "Budi",
    "id_metode_pembayaran": 1,
    "nama_metode": "Tunai",
    "kode_metode": "tunai",
    "id_user": 3,
    "nama_user": "Karyawan Laundry",
    "jumlah_bayar": 15000,
    "jenis_pembayaran": "pelunasan",
    "status_pembayaran": "berhasil",
    "tanggal_pembayaran": "2026-11-23T08:05:53.000000Z",
    "catatan": "Pelunasan di counter",
    "created_at": "2026-11-23T08:05:53.000000Z",
    "updated_at": "2026-11-23T08:05:53.000000Z"
}
```

## 5. Ringkasan Endpoint

| Method | Endpoint | Fungsi | Permission |
|---|---|---|---|
| GET | `/payments` | Riwayat pembayaran | `payments.view` |
| GET | `/payments/{payment}` | Detail pembayaran | `payments.view` |
| POST | `/payments` | Catat pembayaran | `payments.create` |

---

## 6. Riwayat Pembayaran

Mengambil daftar pembayaran, diurutkan dari `tanggal_pembayaran` terbaru.

**Endpoint**

```http
GET /payments
```

**Permission:** `payments.view`

**Query Parameters**

| Parameter | Tipe | Keterangan |
|---|---|---|
| `search` | string | Cari kode pembayaran, catatan, kode pesanan, atau nama pelanggan |
| `status_pembayaran` | string | Filter status: `berhasil`, `menunggu`, `dibatalkan` |
| `jenis_pembayaran` | string | Filter jenis: `dp`, `angsuran`, `pelunasan` |
| `id_metode_pembayaran` | integer | Filter berdasarkan metode pembayaran |
| `tanggal_mulai` | date | Filter tanggal pembayaran mulai |
| `tanggal_selesai` | date | Filter tanggal pembayaran sampai |
| `per_page` | integer | Jumlah data per halaman (1–100, default 15) |
| `page` | integer | Nomor halaman |

**Contoh Request**

```http
GET /payments?status_pembayaran=berhasil&jenis_pembayaran=pelunasan&per_page=15
```

**Contoh Response — HTTP 200**

```json
{
    "data": [
        {
            "id_pembayaran": 3,
            "kode_pembayaran": "PAY-20261123-6BWOKU",
            "id_pesanan": 1,
            "kode_pesanan": "ORD-20261122-4KKRMY8C",
            "nama_pelanggan": "Budi",
            "id_metode_pembayaran": 1,
            "nama_metode": "Tunai",
            "kode_metode": "tunai",
            "id_user": 3,
            "nama_user": "Karyawan Laundry",
            "jumlah_bayar": 15000,
            "jenis_pembayaran": "pelunasan",
            "status_pembayaran": "berhasil",
            "tanggal_pembayaran": "2026-11-23T08:05:53.000000Z",
            "catatan": "Pelunasan di counter",
            "created_at": "2026-11-23T08:05:53.000000Z",
            "updated_at": "2026-11-23T08:05:53.000000Z"
        }
    ],
    "links": {},
    "meta": {}
}
```

`links` dan `meta` mengikuti format pagination Laravel.

---

## 7. Detail Pembayaran

Mengambil satu data pembayaran berdasarkan ID.

**Endpoint**

```http
GET /payments/{payment}
```

**Permission:** `payments.view`

Contoh:

```http
GET /payments/3
```

Response menggunakan bentuk resource pembayaran pada bagian 4.1. Jika ID tidak
ditemukan, API mengembalikan **HTTP 404 Not Found**.

---

## 8. Catat Pembayaran

Mencatat pembayaran baru untuk pesanan yang dibayar nanti (DP, angsuran, atau
pelunasan).

**Endpoint**

```http
POST /payments
```

**Permission:** `payments.create` (hanya karyawan)

**Request Body**

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `id_pesanan` | integer | Ya | ID pesanan yang ada |
| `id_metode_pembayaran` | integer | Ya | Metode pembayaran yang **aktif** |
| `jumlah_bayar` | numeric | Ya | Harus lebih dari 0 |
| `jenis_pembayaran` | string | Ya | `dp`, `angsuran`, atau `pelunasan` |
| `status_pembayaran` | string | Tidak | `berhasil` atau `menunggu`, default `berhasil` |
| `tanggal_pembayaran` | datetime | Tidak | Default waktu sekarang |
| `catatan` | string/null | Tidak | Catatan pembayaran |

**Contoh Request**

```json
{
    "id_pesanan": 1,
    "id_metode_pembayaran": 1,
    "jumlah_bayar": 10000,
    "jenis_pembayaran": "dp",
    "status_pembayaran": "berhasil",
    "tanggal_pembayaran": "2026-11-23 08:00:00",
    "catatan": "DP tunai di counter"
}
```

**Contoh Response — HTTP 201 Created**

```json
{
    "success": true,
    "message": "Pembayaran berhasil dicatat.",
    "data": {
        "id_pembayaran": 1,
        "kode_pembayaran": "PAY-20261123-EQ3FEE",
        "id_pesanan": 1,
        "kode_pesanan": "ORD-20261122-4KKRMY8C",
        "nama_pelanggan": "Budi",
        "id_metode_pembayaran": 1,
        "nama_metode": "Tunai",
        "kode_metode": "tunai",
        "id_user": 3,
        "nama_user": "Karyawan Laundry",
        "jumlah_bayar": 10000,
        "jenis_pembayaran": "dp",
        "status_pembayaran": "berhasil",
        "tanggal_pembayaran": "2026-11-23T08:00:00.000000Z",
        "catatan": "DP tunai di counter",
        "created_at": "2026-11-23T08:00:00.000000Z",
        "updated_at": "2026-11-23T08:00:00.000000Z"
    }
}
```

### 8.1 Aturan Validasi & Bisnis

Pembayaran ditolak dengan **HTTP 422** apabila:

| Kondisi | Pesan |
|---|---|
| Metode pembayaran tidak aktif/tidak ditemukan | `Metode pembayaran tidak aktif atau tidak ditemukan.` |
| `jumlah_bayar` ≤ 0 | `Jumlah bayar harus lebih dari 0.` |
| Pesanan berstatus `dibatalkan` | `Pesanan yang dibatalkan tidak dapat menerima pembayaran.` |
| Tagihan sudah lunas (`sisa` ≤ 0) | `Tagihan pesanan ini sudah lunas.` |
| Jumlah melebihi sisa tagihan | `Jumlah bayar tidak boleh melebihi sisa tagihan (...).` |
| `pelunasan` yang tidak sama dengan sisa | `Pelunasan harus sama dengan sisa tagihan (...).` |

Perhitungan sisa tagihan:

```text
sisa = total_harga pesanan - jumlah semua pembayaran berstatus "berhasil"
```

Pencatatan dilakukan di dalam database transaction dengan `lockForUpdate` pada
pesanan, sehingga dua pembayaran bersamaan tidak dapat melebihi sisa tagihan.

### 8.2 Nilai Otomatis

- `kode_pembayaran` dibuat otomatis: `PAY-YYYYMMDD-XXXXXX` (6 karakter acak).
- `id_user` diisi dari pengguna yang sedang login.
- `status_pembayaran` default `berhasil`.
- `tanggal_pembayaran` default waktu sekarang.

---

## 9. Riwayat Pembayaran pada Resource Pesanan

Endpoint `GET /orders` dan `GET /orders/{order}` menyertakan array
`pembayaran` (diurutkan dari tanggal terbaru) beserta ringkasan:

- `total_terbayar` — total pembayaran `berhasil`
- `sisa_pembayaran` — `max(0, total_harga - total_terbayar)`

Contoh potongan response pesanan:

```json
{
    "id_pesanan": 1,
    "kode_pesanan": "ORD-20261122-4KKRMY8C",
    "total_harga": 35000,
    "total_terbayar": 35000,
    "sisa_pembayaran": 0,
    "pembayaran": [
        {
            "kode_pembayaran": "PAY-20261123-6BWOKU",
            "jenis_pembayaran": "pelunasan",
            "jumlah_bayar": 15000,
            "status_pembayaran": "berhasil",
            "nama_metode": "Tunai",
            "nama_user": "Karyawan Laundry"
        },
        {
            "kode_pembayaran": "PAY-20261123-8WEMGE",
            "jenis_pembayaran": "angsuran",
            "jumlah_bayar": 10000,
            "status_pembayaran": "berhasil",
            "nama_metode": "Tunai",
            "nama_user": "Karyawan Laundry"
        }
    ]
}
```

Frontend dapat memakai `sisa_pembayaran` > 0 untuk menampilkan tombol
**Catat pembayaran**.

## 10. Pembayaran Saat Membuat Pesanan

Selain endpoint `POST /payments`, pembayaran awal dapat dicatat langsung saat
membuat pesanan melalui `POST /orders` dengan `cara_bayar = "now"`. Detail
field dijelaskan pada dokumentasi Orders API. Data tetap disimpan ke tabel
`pembayaran`, dan harga akhir tetap dihitung backend.

---

## 11. Error Response

**401 Unauthorized** — token tidak ada/tidak valid.

```json
{ "message": "Unauthenticated." }
```

**403 Forbidden** — pengguna tidak memiliki permission.

```json
{ "message": "User does not have the right permissions." }
```

**404 Not Found** — pembayaran tidak ditemukan.

```json
{ "message": "Not Found" }
```

**422 Unprocessable Entity** — validasi/aturan bisnis gagal.

```json
{
    "message": "Pelunasan harus sama dengan sisa tagihan (15.000,00).",
    "errors": {
        "jumlah_bayar": [
            "Pelunasan harus sama dengan sisa tagihan (15.000,00)."
        ]
    }
}
```

---

## 12. Catatan Integrasi Frontend

1. Gunakan `payments.view` untuk menampilkan halaman riwayat pembayaran.
2. Tampilkan tombol **Catat pembayaran** hanya jika pengguna memiliki
   `payments.create`, pesanan tidak `dibatalkan`, dan `sisa_pembayaran` > 0.
3. Jangan menghitung sisa sendiri sebagai sumber utama; gunakan
   `sisa_pembayaran` dari backend.
4. Untuk `jenis_pembayaran = pelunasan`, isi nominal sama dengan sisa tagihan.
5. Tampilkan pesan validasi `422` pada field `jumlah_bayar` dan
   `id_metode_pembayaran`.
6. Bedakan `status_pembayaran` (pembayaran) dari `status_pesanan` (pesanan) dan
   `status_proses` (proses laundry).

---

**Dokumen:** Payments API
**Versi:** 1.0
**Base Path:** `/api/v1/payments`
