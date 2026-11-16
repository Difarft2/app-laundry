# Service Management API

Dokumentasi endpoint pengelolaan layanan laundry pada Pemuda Laundry API.

## 1. Informasi Umum

| Informasi | Detail |
|---|---|
| API Version | v1 |
| Base URL | `/api/v1` |
| Resource | Services |
| Authentication | Bearer Token (Sanctum) |
| Format | JSON |

Semua endpoint Service Management memerlukan autentikasi menggunakan Laravel Sanctum dan permission sesuai endpoint.

### Authentication Header

Sertakan token pada setiap request:

```http
Authorization: Bearer YOUR_ACCESS_TOKEN
Accept: application/json
Content-Type: application/json
```

## 2. Permission

| Permission | Keterangan |
|---|---|
| `services.view` | Melihat daftar dan detail layanan |
| `services.create` | Menambahkan layanan |
| `services.update` | Memperbarui layanan |
| `services.delete` | Menghapus layanan |

## 3. Daftar Endpoint

| Method | Endpoint | Permission | Keterangan |
|---|---|---|---|
| GET | `/services` | `services.view` | Daftar layanan |
| GET | `/services/{service}` | `services.view` | Detail layanan |
| POST | `/services` | `services.create` | Tambah layanan |
| PUT | `/services/{service}` | `services.update` | Perbarui layanan |
| DELETE | `/services/{service}` | `services.delete` | Hapus layanan |

---

## 4. Get All Services

Menampilkan daftar layanan dengan dukungan pencarian, filter, dan pagination.

**Endpoint**

```http
GET /api/v1/services
```

**Query Parameters**

| Parameter | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `search` | string | Tidak | Mencari berdasarkan nama layanan |
| `jenis_layanan` | string | Tidak | Filter `kiloan` atau `satuan` |
| `is_active` | boolean | Tidak | Filter status aktif |
| `per_page` | integer | Tidak | Jumlah data per halaman, default 15, minimum 1, maksimum 100 |
| `page` | integer | Tidak | Nomor halaman |

**Contoh Request**

```http
GET /api/v1/services?search=cuci&jenis_layanan=kiloan&is_active=1&per_page=10
```

**Contoh Response — 200 OK**

```json
{
    "data": [
        {
            "id": 1,
            "nama_layanan": "Cuci Setrika Reguler",
            "jenis_layanan": "kiloan",
            "jenis_finishing": "setrika",
            "harga": "7000.00",
            "deskripsi": "Layanan cuci dan setrika reguler.",
            "is_active": true,
            "waktu_layanan": {
                "id": 1,
                "nama_waktu": "Reguler",
                "estimasi_min_jam": 24,
                "estimasi_max_jam": 48
            },
            "created_at": "2026-11-16T10:00:00.000000Z",
            "updated_at": "2026-11-16T10:00:00.000000Z"
        }
    ],
    "links": {},
    "meta": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 10,
        "total": 1
    }
}
```

*Catatan: Struktur `links` dan `meta` mengikuti pagination Laravel. Nilai di atas hanya ilustrasi.*

## 5. Get Service Detail

Menampilkan detail satu layanan berdasarkan ID.

**Endpoint**

```http
GET /api/v1/services/{service}
```

**Path Parameters**

| Parameter | Tipe | Keterangan |
|---|---|---|
| `service` | integer | ID layanan (`id_layanan`) |

**Contoh Request**

```http
GET /api/v1/services/1
```

**Contoh Response — 200 OK**

```json
{
    "data": {
        "id": 1,
        "nama_layanan": "Cuci Setrika Reguler",
        "jenis_layanan": "kiloan",
        "jenis_finishing": "setrika",
        "harga": "7000.00",
        "deskripsi": "Layanan cuci dan setrika reguler.",
        "is_active": true,
        "waktu_layanan": {
            "id": 1,
            "nama_waktu": "Reguler",
            "estimasi_min_jam": 24,
            "estimasi_max_jam": 48
        },
        "created_at": "2026-11-16T10:00:00.000000Z",
        "updated_at": "2026-11-16T10:00:00.000000Z"
    }
}
```

Jika layanan tidak ditemukan, API mengembalikan `404 Not Found`.

## 6. Create Service

Menambahkan layanan baru.

**Endpoint**

```http
POST /api/v1/services
```

**Request Body**

| Field | Tipe | Wajib | Validasi |
|---|---|---|---|
| `nama_layanan` | string | Ya | Maksimal 100 karakter |
| `id_waktu_layanan` | integer / null | Tidak | Harus tersedia pada tabel `waktu_layanan` |
| `jenis_layanan` | string | Ya | `kiloan` atau `satuan` |
| `jenis_finishing` | string | Ya | `lipat`, `setrika`, atau `tanpa_finishing` |
| `harga` | numeric | Ya | Minimal 0 |
| `deskripsi` | string / null | Tidak | Teks |
| `is_active` | boolean | Tidak | Boolean |

**Contoh Request**

```json
{
    "nama_layanan": "Cuci Setrika Reguler",
    "id_waktu_layanan": 1,
    "jenis_layanan": "kiloan",
    "jenis_finishing": "setrika",
    "harga": 7000,
    "deskripsi": "Layanan cuci dan setrika reguler.",
    "is_active": true
}
```

**Contoh Response — 201 Created**

```json
{
    "success": true,
    "message": "Layanan berhasil ditambahkan.",
    "data": {
        "id": 1,
        "nama_layanan": "Cuci Setrika Reguler",
        "jenis_layanan": "kiloan",
        "jenis_finishing": "setrika",
        "harga": "7000.00",
        "deskripsi": "Layanan cuci dan setrika reguler.",
        "is_active": true,
        "waktu_layanan": {
            "id": 1,
            "nama_waktu": "Reguler",
            "estimasi_min_jam": 24,
            "estimasi_max_jam": 48
        },
        "created_at": "2026-11-16T10:00:00.000000Z",
        "updated_at": "2026-11-16T10:00:00.000000Z"
    }
}
```

## 7. Update Service

Memperbarui data layanan berdasarkan ID. Semua field bersifat opsional, tetapi jika dikirim harus memenuhi validasi masing-masing.

**Endpoint**

```http
PUT /api/v1/services/{service}
```

**Contoh Request**

```http
PUT /api/v1/services/1
```

```json
{
    "nama_layanan": "Cuci Setrika Reguler",
    "harga": 7500,
    "is_active": true
}
```

**Contoh Response — 200 OK**

```json
{
    "success": true,
    "message": "Layanan berhasil diperbarui.",
    "data": {
        "id": 1,
        "nama_layanan": "Cuci Setrika Reguler",
        "jenis_layanan": "kiloan",
        "jenis_finishing": "setrika",
        "harga": "7500.00",
        "deskripsi": "Layanan cuci dan setrika reguler.",
        "is_active": true,
        "waktu_layanan": {
            "id": 1,
            "nama_waktu": "Reguler",
            "estimasi_min_jam": 24,
            "estimasi_max_jam": 48
        },
        "created_at": "2026-11-16T10:00:00.000000Z",
        "updated_at": "2026-11-16T10:30:00.000000Z"
    }
}
```

**Catatan:**

- Field yang tidak dikirim tidak akan diperbarui.
- `id_waktu_layanan` dapat diisi `null` untuk melepaskan relasi waktu layanan.
- Jika `id_waktu_layanan` diisi, ID tersebut harus tersedia di tabel `waktu_layanan`.

## 8. Delete Service

Menghapus layanan berdasarkan ID.

**Endpoint**

```http
DELETE /api/v1/services/{service}
```

**Contoh Request**

```http
DELETE /api/v1/services/1
```

**Response — 200 OK**

```json
{
    "success": true,
    "message": "Layanan berhasil dihapus."
}
```

**Response — 422 Unprocessable Entity**

Layanan tidak dapat dihapus apabila sudah digunakan dalam detail pesanan.

```json
{
    "success": false,
    "message": "Layanan tidak dapat dihapus karena sudah digunakan dalam pesanan."
}
```

## 9. Validation Errors

Jika request tidak memenuhi aturan validasi, API mengembalikan `422 Unprocessable Entity`.

**Contoh Response**

```json
{
    "message": "The nama layanan field is required.",
    "errors": {
        "nama_layanan": [
            "Nama layanan wajib diisi."
        ],
        "jenis_layanan": [
            "Jenis layanan wajib dipilih."
        ]
    }
}
```

Beberapa kondisi validasi:

| Kondisi | Keterangan |
|---|---|
| `nama_layanan` kosong | Nama layanan wajib diisi |
| Nama lebih dari 100 karakter | Ditolak |
| `id_waktu_layanan` tidak tersedia | Waktu layanan tidak ditemukan |
| `jenis_layanan` tidak valid | Harus `kiloan` atau `satuan` |
| `jenis_finishing` tidak valid | Harus `lipat`, `setrika`, atau `tanpa_finishing` |
| `harga` negatif | Harga tidak boleh kurang dari 0 |

## 10. HTTP Status Codes

| Status | Keterangan |
|---|---|
| `200 OK` | Request berhasil |
| `201 Created` | Layanan berhasil dibuat |
| `401 Unauthorized` | Token tidak tersedia atau tidak valid |
| `403 Forbidden` | User tidak memiliki permission |
| `404 Not Found` | Layanan tidak ditemukan |
| `422 Unprocessable Entity` | Validasi gagal atau layanan tidak dapat dihapus |

## 11. Data Reference

### Jenis Layanan

| Value | Keterangan |
|---|---|
| `kiloan` | Layanan berdasarkan berat dalam kilogram |
| `satuan` | Layanan berdasarkan jumlah item |

### Jenis Finishing

| Value | Keterangan |
|---|---|
| `lipat` | Finishing dengan dilipat |
| `setrika` | Finishing dengan disetrika |
| `tanpa_finishing` | Tanpa proses finishing |

### Status Layanan

| Value | Keterangan |
|---|---|
| `true` | Layanan aktif |
| `false` | Layanan tidak aktif |

### Waktu Layanan

Layanan dapat memiliki relasi dengan data waktu layanan melalui `id_waktu_layanan`.

Jika tidak menggunakan estimasi waktu tertentu, `id_waktu_layanan` dapat bernilai `null`.

---

**Dokumentasi ini mengikuti implementasi Service Management API saat ini.** Aturan bisnis tambahan yang belum diterapkan sebagai validasi backend tidak dianggap sebagai validasi endpoint.
