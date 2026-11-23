# API Documentation — Manajemen Antar-Jemput
 
**Project:** Pemuda Laundry
**Module:** Courier (Antar-Jemput) Management
**API Version:** v1
**Base URL:** `http://127.0.0.1:8000/api/v1`
 
---
 
## 1. Deskripsi
 
API Manajemen Antar-Jemput digunakan untuk mengelola tugas penjemputan dan pengantaran laundry.
 
Fitur yang tersedia:
- Melihat daftar tugas antar-jemput.
- Membuat permintaan antar-jemput.
- Menugaskan kurir ke tugas antar-jemput.
- Memperbarui status tugas antar-jemput oleh kurir.
- Riwayat status tugas antar-jemput.
 
## 2. Autentikasi
 
Seluruh endpoint memerlukan autentikasi menggunakan Laravel Sanctum.
 
```http
Authorization: Bearer YOUR_ACCESS_TOKEN
Accept: application/json
Content-Type: application/json
```
 
## 3. Hak Akses
 
| Role | View | Assign | Update Status |
|---|---|---|---|
| Owner | ✓ | ✓ | - |
| Admin | ✓ | - | - |
| Karyawan | ✓ | - | - |
| Kurir | ✓ | - | ✓ |

Catatan: endpoint `GET /delivery-tasks` dan `GET /delivery-tasks/{id}` untuk role Kurir hanya
mengembalikan tugas miliknya sendiri; mengakses tugas kurir lain menghasilkan `403`.
 
Permission:
- `delivery.view`
- `delivery.assign`
- `delivery.update`
 
## 4. Daftar Endpoint
 
| Method | Endpoint | Permission | Deskripsi |
|---|---|---|---|
| GET | `/delivery-tasks` | `delivery.view` | Daftar tugas |
| POST | `/delivery-tasks` | `delivery.assign` | Buat permintaan |
| GET | `/delivery-tasks/{id}` | `delivery.view` | Detail tugas |
| PATCH | `/delivery-tasks/{id}/assign` | `delivery.assign` | Tugaskan kurir |
| PATCH | `/delivery-tasks/{id}/status` | `delivery.update` | Update status (kurir) |
| GET | `/delivery-tasks/couriers` | `delivery.assign` | Daftar kurir tersedia |
 
---
 
## 5. Daftar Tugas Antar-Jemput
 
Mengambil daftar tugas antar-jemput. Kurir hanya melihat tugas miliknya.
 
**Endpoint**
 
```http
GET /delivery-tasks?search=...&status=menunggu
```
 
**Query Parameters:** `search`, `status`, `jenis`, `id_kurir`, `unassigned` (boolean), `assigned` (boolean), `tanggal_mulai`, `tanggal_selesai`.
 
---
 
## 6. Update Status Tugas
 
Mengubah status tugas oleh kurir.
 
**Endpoint**
 
```http
PATCH /delivery-tasks/{id}/status
```
 
**Permission:** `delivery.update`
 
**Request Body:** `status_antar_jemput` (enum), `keterangan` (optional string).
 
**Status Valid:** `menunggu`, `ditugaskan`, `menuju_lokasi`, `barang_diambil`, `barang_diantar`, `selesai`, `gagal`.
 
---
 
**Catatan:** Lihat PRD untuk aturan transisi status spesifik.
