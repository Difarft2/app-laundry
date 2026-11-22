# Orders API Documentation

**Project:** Pemuda Laundry
**API Version:** v1
**Base URL:** `http://localhost:8000/api/v1`
**Authentication:** Laravel Sanctum
**Content-Type:** `application/json`

---

## 1. Overview

Orders API digunakan untuk mengelola transaksi pesanan laundry, mulai dari membuat pesanan, melihat daftar pesanan, melihat detail, mengubah data, mengubah status, hingga menghapus pesanan.

Setiap pesanan dapat memiliki beberapa detail layanan. Ketika pesanan dibuat, sistem juga membuat data proses laundry untuk setiap detail layanan dan mencatat riwayat status awal pesanan.

## 2. Authentication

Seluruh endpoint Orders membutuhkan autentikasi menggunakan Laravel Sanctum.

Tambahkan header berikut pada setiap request:

```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer {token}
```

Token diperoleh melalui endpoint login.

## 3. Permission

| Role | Permission |
|---|---|
| Owner | Melihat, mengubah, mengubah status, dan menghapus pesanan. **Tidak dapat membuat pesanan.** |
| Admin | Hanya melihat daftar/detail pesanan (read-only). |
| Karyawan | Melihat, **membuat**, mengubah, dan mengubah status pesanan. |
| Kurir | Tidak memiliki akses Orders. |

Daftar permission:

- `orders.view`
- `orders.create`
- `orders.update`
- `orders.delete`
- `orders.update-status`

## 4. Endpoint Summary

| Method | Endpoint | Fungsi | Permission |
|---|---|---|---|
| GET | `/orders` | Daftar pesanan | `orders.view` |
| POST | `/orders` | Membuat pesanan | `orders.create` |
| GET | `/orders/{order}` | Detail pesanan | `orders.view` |
| PUT | `/orders/{order}` | Mengubah pesanan | `orders.update` |
| PATCH | `/orders/{order}/status` | Mengubah status pesanan | `orders.update-status` |
| DELETE | `/orders/{order}` | Menghapus pesanan | `orders.delete` |

---

## 5. Daftar Pesanan

Mengambil daftar pesanan yang tersimpan di database.

**Endpoint**

```http
GET /orders
```

**Query Parameters**

| Parameter | Tipe | Keterangan |
|---|---|---|
| `search` | string | Pencarian berdasarkan kode pesanan, nama pelanggan, atau nomor HP |
| `status` | string | Filter berdasarkan status pesanan |
| `tanggal_mulai` | date | Filter tanggal masuk mulai |
| `tanggal_selesai` | date | Filter tanggal masuk sampai |
| `per_page` | integer | Jumlah data per halaman, maksimal 100 |

**Contoh Request**

```http
GET /orders?search=Budi&status=menunggu&per_page=10
```

**Status Pesanan**

| Status | Keterangan |
|---|---|
| `menunggu` | Pesanan baru diterima |
| `diproses` | Pesanan sedang diproses |
| `siap_diambil` | Laundry sudah siap diambil |
| `dalam_pengantaran` | Pesanan sedang diantar |
| `selesai` | Pesanan selesai |
| `dibatalkan` | Pesanan dibatalkan |

**Contoh Response**

```json
{
    "data": [
        {
            "id_pesanan": 1,
            "kode_pesanan": "ORD-20261118-ABC12345",
            "pelanggan": {
                "nama": "Budi",
                "no_hp": "081234567890",
                "alamat": "Tuban"
            },
            "tanggal_masuk": "2026-11-18T19:00:00.000000Z",
            "estimasi_selesai": null,
            "tanggal_selesai": null,
            "status_pesanan": "menunggu",
            "subtotal": "21000.00",
            "diskon": "0.00",
            "total_harga": "21000.00",
            "total_terbayar": "0.00",
            "sisa_pembayaran": "21000.00",
            "catatan": "Tes pesanan API"
        }
    ],
    "links": {},
    "meta": {}
}
```

`links` dan `meta` merupakan informasi pagination dari Laravel Resource Collection. Nilainya mengikuti hasil pagination.

---

## 6. Membuat Pesanan

Membuat pesanan baru beserta detail layanan dan proses laundry.

**Endpoint**

```http
POST /orders
```

**Permission:** `orders.create`

**Request Body**

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `nama_pelanggan` | string | Ya | Nama pelanggan, maksimal 150 karakter |
| `no_hp` | string | Ya | Nomor HP, maksimal 20 karakter |
| `alamat` | string/null | Tidak | Alamat pelanggan |
| `tanggal_masuk` | datetime | Ya | Tanggal dan waktu pesanan masuk |
| `diskon` | numeric | Tidak | Diskon, minimal 0 |
| `catatan` | string/null | Tidak | Catatan pesanan |
| `detail_pesanan` | array | Ya | Minimal satu detail layanan |
| `detail_pesanan.*.id_layanan` | integer | Ya | ID layanan aktif |
| `detail_pesanan.*.jumlah` | numeric | Ya | Berat atau jumlah layanan |
| `detail_pesanan.*.catatan` | string/null | Tidak | Catatan detail layanan |

**Contoh Request**

```json
{
    "nama_pelanggan": "Budi",
    "no_hp": "081234567890",
    "alamat": "Tuban",
    "tanggal_masuk": "2026-11-18 19:00:00",
    "diskon": 0,
    "catatan": "Pisahkan pakaian putih",
    "detail_pesanan": [
        {
            "id_layanan": 1,
            "jumlah": 3,
            "catatan": "Pakaian sehari-hari"
        },
        {
            "id_layanan": 2,
            "jumlah": 2,
            "catatan": null
        }
    ]
}
```

**Contoh Response — 201 Created**

```json
{
    "data": {
        "id_pesanan": 1,
        "kode_pesanan": "ORD-20261118-ABC12345",
        "pelanggan": {
            "nama": "Budi",
            "no_hp": "081234567890",
            "alamat": "Tuban"
        },
        "tanggal_masuk": "2026-11-18T19:00:00.000000Z",
        "estimasi_selesai": null,
        "tanggal_selesai": null,
        "status_pesanan": "menunggu",
        "subtotal": "31000.00",
        "diskon": "0.00",
        "total_harga": "31000.00",
        "total_terbayar": "0.00",
        "sisa_pembayaran": "31000.00",
        "catatan": "Pisahkan pakaian putih",
        "detail_pesanan": [],
        "riwayat_status": []
    }
}
```

Data `detail_pesanan` dan `riwayat_status` pada response sebenarnya akan berisi data hasil pembuatan pesanan. Struktur lengkapnya dijelaskan pada bagian detail pesanan dan riwayat status.

**Proses otomatis ketika pesanan dibuat:**

1. Backend memvalidasi pelanggan dan detail layanan.
2. Backend mengambil harga layanan dari database.
3. Backend menghitung subtotal, diskon, dan total harga.
4. Backend membuat data pesanan.
5. Backend membuat detail pesanan.
6. Backend membuat proses laundry dengan status awal `menunggu` untuk setiap detail.
7. Backend mencatat riwayat status awal pesanan.

Semua proses penyimpanan dilakukan menggunakan database transaction.

---

## 7. Detail Pesanan

Mengambil informasi lengkap satu pesanan.

**Endpoint**

```http
GET /orders/{order}
```

**Permission:** `orders.view`

Contoh:

```http
GET /orders/1
```

**Informasi yang dikembalikan:**

- Identitas pesanan.
- Data pelanggan.
- Tanggal masuk dan estimasi selesai.
- Status pesanan.
- Rincian harga.
- Total pembayaran dan sisa pembayaran.
- Detail layanan.
- Proses laundry setiap detail.
- Riwayat perubahan status pesanan.

**Struktur Detail Layanan**

| Field | Keterangan |
|---|---|
| `id_detail_pesanan` | ID detail pesanan |
| `id_layanan` | ID layanan |
| `nama_layanan` | Nama layanan saat pesanan dibuat |
| `harga_satuan` | Harga layanan saat pesanan dibuat |
| `jumlah` | Berat atau jumlah item |
| `satuan` | Satuan `kg` atau `pcs` |
| `subtotal` | Total harga detail |
| `catatan` | Catatan detail |
| `proses_laundry` | Informasi proses laundry |

---

## 8. Mengubah Pesanan

Mengubah data pelanggan, catatan, diskon, tanggal masuk, atau detail layanan pesanan.

**Endpoint**

```http
PUT /orders/{order}
```

**Permission:** `orders.update`

**Request Body**

Field yang dikirim bersifat opsional (`sometimes`).

Contoh mengubah data pelanggan:

```json
{
    "nama_pelanggan": "Budi Santoso",
    "no_hp": "081234567899",
    "alamat": "Surabaya",
    "catatan": "Pakaian dipisahkan"
}
```

Contoh mengubah detail layanan:

```json
{
    "detail_pesanan": [
        {
            "id_layanan": 1,
            "jumlah": 4,
            "catatan": "Tambahan pakaian"
        }
    ]
}
```

**Aturan Pengubahan**

- Pesanan dengan status `selesai` atau `dibatalkan` tidak dapat diubah.
- Detail layanan hanya dapat diganti jika seluruh proses laundry pada detail lama masih berstatus `menunggu` dan belum memiliki riwayat proses.
- Harga layanan selalu diambil dari database.
- Total pembayaran yang sudah tercatat tidak boleh melebihi total harga baru.
- Diskon tidak boleh melebihi subtotal.

---

## 9. Mengubah Status Pesanan

Mengubah status keseluruhan pesanan.

**Endpoint**

```http
PATCH /orders/{order}/status
```

**Permission:** `orders.update-status`

**Request Body**

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `status_pesanan` | string | Ya | Status baru |
| `keterangan` | string/null | Tidak | Catatan perubahan status |

**Contoh Request**

```json
{
    "status_pesanan": "diproses",
    "keterangan": "Pesanan mulai dikerjakan"
}
```

Status yang diperbolehkan:

- `menunggu`
- `diproses`
- `siap_diambil`
- `dalam_pengantaran`
- `selesai`
- `dibatalkan`

Setiap perubahan status akan dicatat ke tabel riwayat status pesanan.

Jika status baru adalah `selesai`, sistem juga mengisi `tanggal_selesai`.

Pesanan dengan status `selesai` atau `dibatalkan` tidak dapat diubah statusnya lagi.

---

## 10. Menghapus Pesanan

Menghapus pesanan yang belum memiliki aktivitas operasional.

**Endpoint**

```http
DELETE /orders/{order}
```

**Permission:** `orders.delete`

**Aturan Penghapusan**

Pesanan tidak dapat dihapus apabila:

- Sudah memiliki data pembayaran.
- Salah satu proses laundry sudah tidak berstatus `menunggu`.
- Salah satu proses laundry sudah memiliki riwayat proses.

Jika memenuhi ketentuan, pesanan dapat dihapus beserta detail dan data proses terkait sesuai relasi database.

---

## 11. Business Rules

| No. | Aturan | Keterangan |
|---|---|---|
| 1 | Harga layanan | Selalu diambil dari database |
| 2 | Subtotal | Dihitung dari harga layanan dikali jumlah |
| 3 | Diskon | Tidak boleh melebihi subtotal |
| 4 | Total harga | Subtotal dikurangi diskon |
| 5 | Kiloan | Menggunakan satuan kg |
| 6 | Satuan | Menggunakan satuan pcs |
| 7 | Layanan satuan | Jumlah harus berupa bilangan bulat |
| 8 | Cuci Basah | Minimal 5 kg |
| 9 | Kering Saja | Minimal 5 kg |
| 10 | Pembayaran | Total pembayaran tidak boleh melebihi total tagihan |
| 11 | Status awal | `menunggu` |
| 12 | Proses laundry awal | `menunggu` |

### Status Proses Laundry

Status proses laundry dikelola melalui API Proses Laundry, bukan melalui endpoint perubahan status pesanan.

| Status | Keterangan |
|---|---|
| `menunggu` | Menunggu pengerjaan |
| `pencucian` | Sedang dicuci |
| `pengeringan` | Sedang dikeringkan |
| `finishing` | Tahap finishing |
| `selesai` | Proses laundry selesai |

Status pesanan dan status proses laundry merupakan dua hal yang berbeda.

---

## 12. Error Response

Format error mengikuti standar respons Laravel.

**401 Unauthorized**

Token tidak tersedia atau tidak valid.

```json
{
    "message": "Unauthenticated."
}
```

**403 Forbidden**

Pengguna tidak memiliki permission yang diperlukan.

```json
{
    "message": "User does not have the right permissions."
}
```

**404 Not Found**

Pesanan tidak ditemukan.

```json
{
    "message": "Not Found"
}
```

**422 Unprocessable Entity**

Validasi request gagal atau aturan bisnis tidak terpenuhi.

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "nama_pelanggan": [
            "The nama pelanggan field is required."
        ]
    }
}
```

---

## 13. Catatan Integrasi Frontend

Frontend harus memperhatikan hal-hal berikut:

1. Gunakan token Sanctum pada setiap request.
2. Jangan menghitung harga akhir sebagai sumber data utama; gunakan hasil perhitungan backend.
3. Tampilkan pesan validasi dari response `422`.
4. Tampilkan akses ditolak jika mendapatkan response `403`.
5. Pisahkan status pesanan, status pembayaran, dan status proses laundry.
6. Gunakan `id_pesanan` untuk mengambil detail pesanan.
7. Jangan menganggap pesanan selesai hanya karena proses laundry selesai; status pesanan memiliki alur tersendiri.

---

**Dokumen:** Orders API
**Versi:** 1.0
**Base Path:** `/api/v1/orders`
