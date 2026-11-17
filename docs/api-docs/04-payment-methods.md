# API Documentation — Manajemen Metode Pembayaran

**Project:** Pemuda Laundry
**Module:** Payment Method Management
**API Version:** v1
**Base URL:** `http://127.0.0.1:8000/api/v1`

---

## 1. Deskripsi

API Manajemen Metode Pembayaran digunakan untuk mengelola metode pembayaran yang tersedia pada sistem Pemuda Laundry.

Fitur yang tersedia:

- Menampilkan daftar metode pembayaran.
- Menampilkan detail metode pembayaran.
- Menambahkan metode pembayaran.
- Memperbarui data metode pembayaran.
- Mengaktifkan atau menonaktifkan metode pembayaran.
- Menghapus metode pembayaran.

## 2. Autentikasi

Seluruh endpoint memerlukan autentikasi menggunakan Laravel Sanctum.

Gunakan token hasil login pada header setiap request.

```http
Authorization: Bearer YOUR_ACCESS_TOKEN
Accept: application/json
Content-Type: application/json
```

Jika token tidak valid atau tidak disertakan, API mengembalikan:

**HTTP 401 Unauthorized**

## 3. Hak Akses

| Role | View | Create | Update | Delete |
|---|---|---|---|---|
| Owner | ✓ | ✓ | ✓ | ✓ |
| Admin | ✓ | ✓ | ✓ | ✓ |
| Karyawan | ✓ | - | - | - |
| Kurir | - | - | - | - |

Permission yang digunakan:

- `payment-methods.view`
- `payment-methods.create`
- `payment-methods.update`
- `payment-methods.delete`

Jika pengguna tidak memiliki permission yang dibutuhkan, API mengembalikan **HTTP 403 Forbidden**.

---

## 4. Daftar Endpoint

| Method | Endpoint | Permission | Deskripsi |
|---|---|---|---|
| GET | `/payment-methods` | `payment-methods.view` | Daftar metode |
| POST | `/payment-methods` | `payment-methods.create` | Tambah metode |
| GET | `/payment-methods/{paymentMethod}` | `payment-methods.view` | Detail metode |
| PUT | `/payment-methods/{paymentMethod}` | `payment-methods.update` | Edit metode |
| PATCH | `/payment-methods/{paymentMethod}/status` | `payment-methods.update` | Ubah status |
| DELETE | `/payment-methods/{paymentMethod}` | `payment-methods.delete` | Hapus metode |

---

## 5. Struktur Data

Tabel database: `metode_pembayaran`

| Field | Tipe | Keterangan |
|---|---|---|
| `id_metode_pembayaran` | BIGINT | Primary key |
| `nama_metode` | VARCHAR(100) | Nama metode pembayaran |
| `kode` | VARCHAR(50) | Kode unik metode pembayaran |
| `deskripsi` | TEXT | Deskripsi, nullable |
| `is_active` | BOOLEAN | Status aktif |
| `created_at` | TIMESTAMP | Waktu dibuat |
| `updated_at` | TIMESTAMP | Waktu diperbarui |

Contoh data:

```json
{
    "id": 1,
    "nama_metode": "Tunai",
    "kode": "tunai",
    "deskripsi": "Pembayaran secara langsung menggunakan uang tunai.",
    "is_active": true,
    "created_at": "2026-11-16T13:21:37.000000Z",
    "updated_at": "2026-11-16T13:21:37.000000Z"
}
```

Catatan: Field `id` pada respons API merupakan representasi dari `id_metode_pembayaran` pada database.

---

# 6. Detail Endpoint

## 6.1 Menampilkan Daftar Metode Pembayaran

Mengambil daftar metode pembayaran dengan dukungan pencarian, filter status, dan pagination.

**Request**

```http
GET /payment-methods
```

**Query Parameters**

| Parameter | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `search` | string | Tidak | Pencarian nama atau kode |
| `is_active` | boolean | Tidak | Filter status aktif |
| `per_page` | integer | Tidak | Jumlah data per halaman |
| `page` | integer | Tidak | Nomor halaman |

**Contoh Request**

```http
GET /payment-methods?search=bank&is_active=1&per_page=10&page=1
```

**Contoh Response — HTTP 200**

```json
{
    "data": [
        {
            "id": 2,
            "nama_metode": "Transfer Bank",
            "kode": "transfer_bank",
            "deskripsi": "Pembayaran melalui transfer rekening bank.",
            "is_active": true,
            "created_at": "2026-11-16T13:21:37.000000Z",
            "updated_at": "2026-11-16T13:21:37.000000Z"
        }
    ],
    "links": {
        "first": "http://127.0.0.1:8001/api/v1/payment-methods?page=1",
        "last": "http://127.0.0.1:8001/api/v1/payment-methods?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 1,
        "per_page": 10,
        "to": 1,
        "total": 1
    }
}
```

Catatan: Contoh di atas disederhanakan. Respons pagination aktual juga menyertakan informasi `meta.links`.

## 6.2 Menambahkan Metode Pembayaran

Menambahkan metode pembayaran baru.

**Request**

```http
POST /payment-methods
```

**Request Body**

| Field | Tipe | Wajib | Validasi |
|---|---|---|---|
| `nama_metode` | string | Ya | Maksimal 100 karakter |
| `kode` | string | Ya | Maksimal 50 karakter, unik |
| `deskripsi` | string | Tidak | Boleh null |
| `is_active` | boolean | Tidak | Boolean |

**Contoh Request**

```json
{
    "nama_metode": "E-Wallet",
    "kode": "ewallet",
    "deskripsi": "Pembayaran menggunakan dompet digital.",
    "is_active": true
}
```

**Contoh Response — HTTP 201**

```json
{
    "message": "Metode pembayaran berhasil ditambahkan.",
    "data": {
        "id": 4,
        "nama_metode": "E-Wallet",
        "kode": "ewallet",
        "deskripsi": "Pembayaran menggunakan dompet digital.",
        "is_active": true,
        "created_at": "2026-11-17T13:00:00.000000Z",
        "updated_at": "2026-11-17T13:00:00.000000Z"
    }
}
```

## 6.3 Menampilkan Detail Metode Pembayaran

Mengambil detail metode pembayaran berdasarkan ID.

**Request**

```http
GET /payment-methods/{paymentMethod}
```

**Contoh Request**

```http
GET /payment-methods/1
```

**Contoh Response — HTTP 200**

```json
{
    "data": {
        "id": 1,
        "nama_metode": "Tunai",
        "kode": "tunai",
        "deskripsi": "Pembayaran secara langsung menggunakan uang tunai.",
        "is_active": true,
        "created_at": "2026-11-16T13:21:37.000000Z",
        "updated_at": "2026-11-16T13:21:37.000000Z"
    }
}
```

Jika ID tidak ditemukan, Laravel mengembalikan **HTTP 404 Not Found**.

## 6.4 Memperbarui Metode Pembayaran

Memperbarui informasi metode pembayaran berdasarkan ID.

**Request**

```http
PUT /payment-methods/{paymentMethod}
```

**Request Body**

Semua field bersifat opsional, tetapi jika dikirim harus memenuhi validasi.

```json
{
    "nama_metode": "Transfer BCA",
    "kode": "transfer_bca",
    "deskripsi": "Pembayaran melalui transfer rekening BCA.",
    "is_active": true
}
```

**Contoh Response — HTTP 200**

```json
{
    "message": "Metode pembayaran berhasil diperbarui.",
    "data": {
        "id": 2,
        "nama_metode": "Transfer BCA",
        "kode": "transfer_bca",
        "deskripsi": "Pembayaran melalui transfer rekening BCA.",
        "is_active": true,
        "created_at": "2026-11-16T13:21:37.000000Z",
        "updated_at": "2026-11-17T13:00:00.000000Z"
    }
}
```

## 6.5 Mengubah Status Metode Pembayaran

Mengaktifkan atau menonaktifkan metode pembayaran tanpa menghapus datanya.

**Request**

```http
PATCH /payment-methods/{paymentMethod}/status
```

**Request Body**

```json
{
    "is_active": false
}
```

Untuk mengaktifkan kembali:

```json
{
    "is_active": true
}
```

**Contoh Response — HTTP 200**

```json
{
    "message": "Status metode pembayaran berhasil diperbarui.",
    "data": {
        "id": 1,
        "nama_metode": "Tunai",
        "kode": "tunai",
        "deskripsi": "Pembayaran secara langsung menggunakan uang tunai.",
        "is_active": false,
        "created_at": "2026-11-16T13:21:37.000000Z",
        "updated_at": "2026-11-17T13:00:00.000000Z"
    }
}
```

## 6.6 Menghapus Metode Pembayaran

Menghapus metode pembayaran berdasarkan ID.

**Request**

```http
DELETE /payment-methods/{paymentMethod}
```

**Contoh Request**

```http
DELETE /payment-methods/4
```

**Response Berhasil — HTTP 200**

```json
{
    "message": "Metode pembayaran berhasil dihapus."
}
```

**Response Gagal — HTTP 422**

Jika metode pembayaran sudah pernah digunakan dalam transaksi:

```json
{
    "message": "Metode pembayaran tidak dapat dihapus karena sudah digunakan dalam transaksi."
}
```

Sistem melindungi integritas data transaksi dengan mencegah penghapusan metode pembayaran yang telah digunakan.

---

# 7. Format Error Response

## 7.1 Validasi Gagal — HTTP 422

Contoh ketika kode metode pembayaran sudah digunakan:

```json
{
    "message": "The kode has already been taken.",
    "errors": {
        "kode": [
            "The kode has already been taken."
        ]
    }
}
```

## 7.2 Tidak Terautentikasi — HTTP 401

```json
{
    "message": "Unauthenticated."
}
```

## 7.3 Tidak Memiliki Izin — HTTP 403

```json
{
    "message": "User does not have the right permissions."
}
```

## 7.4 Data Tidak Ditemukan — HTTP 404

```json
{
    "message": "Not Found"
}
```

---

# 8. Aturan Bisnis

1. Setiap metode pembayaran harus memiliki kode unik.
2. Nama metode pembayaran maksimal 100 karakter.
3. Kode metode pembayaran maksimal 50 karakter.
4. Metode pembayaran dapat diaktifkan atau dinonaktifkan.
5. Metode pembayaran yang tidak aktif tidak seharusnya ditawarkan sebagai pilihan pembayaran baru pada proses transaksi.
6. Metode pembayaran yang sudah digunakan dalam transaksi tidak dapat dihapus.
7. Metode pembayaran yang sudah digunakan sebaiknya dinonaktifkan jika tidak lagi digunakan.
8. Karyawan hanya memiliki akses untuk melihat metode pembayaran.
9. Owner dan Admin memiliki akses penuh terhadap manajemen metode pembayaran.

---

# 9. Catatan Integrasi Frontend

Frontend dapat menggunakan endpoint berikut untuk setiap kebutuhan:

| Kebutuhan | Endpoint |
|---|---|
| Menampilkan tabel | `GET /payment-methods` |
| Pencarian | `GET /payment-methods?search=...` |
| Filter status | `GET /payment-methods?is_active=1` |
| Tambah data | `POST /payment-methods` |
| Detail data | `GET /payment-methods/{id}` |
| Edit data | `PUT /payment-methods/{id}` |
| Toggle status | `PATCH /payment-methods/{id}/status` |
| Hapus data | `DELETE /payment-methods/{id}` |

Untuk pilihan metode pembayaran pada form transaksi, frontend sebaiknya hanya menampilkan metode dengan `is_active = true`.

---

**End of Documentation**
Pemuda Laundry — API v1
