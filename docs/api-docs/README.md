# Dokumentasi API — Pemuda Laundry

Kumpulan dokumentasi REST API Pemuda Laundry.

- **API Version:** v1
- **Base URL:** `http://localhost:8000/api/v1`
- **Authentication:** Laravel Sanctum (`Authorization: Bearer {token}`)
- **Format:** JSON

## Daftar Dokumen

| No. | Dokumen | Cakupan |
|---|---|---|
| 01 | [Authentication](./01-authentication.md) | Login, logout, profil, password |
| 02 | [User Management](./02-user-management.md) | Pengelolaan pengguna |
| 03 | [Service Management](./03-service-management.md) | Layanan dan waktu layanan |
| 04 | [Payment Methods](./04-payment-methods.md) | Metode pembayaran + ringkasan 30 hari |
| 05 | [Orders API](./05-orders-api.md) | Pesanan, estimasi selesai, pembayaran saat create, lookup QR, cetak PDF, alur status laundry |
| 06 | [Antar-Jemput API](./06-antar-jemput-api.md) | Tugas antar-jemput dan penugasan kurir |
| 07 | [Payments API](./07-payments.md) | Riwayat & pencatatan pembayaran (DP/angsuran/pelunasan) |

## Matriks Hak Akses Ringkas

| Modul | Owner | Admin | Karyawan | Kurir |
|---|---|---|---|---|
| Pesanan (lihat) | ✓ | ✓ | ✓ | - |
| Pesanan (buat/ubah data) | - | - | ✓ | - |
| Pesanan (ubah status/hapus) | ✓ | - | ✓ | - |
| Metode pembayaran (lihat) | ✓ | ✓ | ✓ | - |
| Metode pembayaran (kelola) | - | ✓ | - | - |
| Riwayat pembayaran (lihat) | ✓ | ✓ | ✓ | - |
| Catat pembayaran | - | - | ✓ | - |

Hanya karyawan yang dapat membuat pesanan dan mencatat pembayaran. Admin
read-only untuk pesanan dan pembayaran; owner dapat mengubah status/menghapus
pesanan tetapi tidak membuat/mengubah data maupun mencatat pembayaran.

## Catatan Umum

1. Semua endpoint selain `POST /auth/login` membutuhkan token Sanctum.
2. Backend adalah sumber kebenaran untuk harga, `estimasi_selesai`, serta
   `total_terbayar`/`sisa_pembayaran`.
3. Data pembayaran hanya disimpan pada tabel `pembayaran`, bukan `pesanan`.
4. Status pesanan (`status_pesanan`), status pembayaran
   (`status_pembayaran`), dan status proses laundry (`status_proses`) adalah
   tiga hal yang berbeda.
5. Gunakan respons `403` untuk menampilkan pesan akses ditolak dan `422` untuk
   menampilkan error validasi pada field terkait.
