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
| 05 | [Orders API](./05-orders-api.md) | Pesanan, estimasi selesai, pembayaran saat create, lookup QR, cetak struk PNG, alur status laundry |
| 06 | [Antar-Jemput API](./06-antar-jemput-api.md) | Antar dari pesanan, terima tugas kurir, dan penugasan antar-jemput |
| 07 | [Payments API](./07-payments.md) | Riwayat & pencatatan pembayaran (DP/angsuran/pelunasan) |
| 08 | [Dashboard API](./08-dashboard-api.md) | Data dashboard real per role (owner, admin, karyawan, kurir), grafik `7d`/`30d`/`month`, antrean, dan tugas kurir |
| 09 | [Expenses API](./09-expenses-api.md) | Pengeluaran operasional (owner: kelola; admin: lihat) |
| 10 | [Landing Delivery API](./10-landing-delivery-api.md) | Pesan kurir dari landing page dengan kunci API bersama, verifikasi/tolak oleh karyawan |
| 11 | [Settings API](./11-settings-api.md) | Pengaturan global (nama/versi/logo/nomor), publik untuk dibaca, admin-only untuk diedit |

## Matriks Hak Akses Ringkas

| Modul | Owner | Admin | Karyawan | Kurir |
|---|---|---|---|---|
| Dashboard (lihat, `dashboard.view`) | ✓ | ✓ | ✓ | ✓ (scope tugas saja) |
| Pesanan (lihat) | ✓ | ✓ | ✓ | - |
| Pesanan (buat/ubah data) | - | - | ✓ | - |
| Pesanan (ubah status/hapus) | ✓ | - | ✓ | - |
| Metode pembayaran (lihat) | ✓ | ✓ | ✓ | - |
| Metode pembayaran (kelola) | - | ✓ | - | - |
| Riwayat pembayaran (lihat) | ✓ | ✓ | ✓ | - |
| Catat pembayaran | - | - | ✓ | - |
| Pengeluaran (lihat, `finance.view`) | ✓ | ✓ | - | - |
| Pengeluaran (catat/ubah/hapus, `finance.manage`) | ✓ | - | - | - |
| Verifikasi permintaan landing (`delivery.verify`) | ✓ | ✓ | ✓ | - |
| Pengaturan aplikasi (lihat) | ✓ | ✓ | ✓ | ✓ |
| Pengaturan aplikasi (ubah, `settings.manage`) | - | ✓ | - | - |

Hanya karyawan yang dapat membuat pesanan dan mencatat pembayaran. Admin
read-only untuk pesanan dan pembayaran; owner dapat mengubah status/menghapus
pesanan tetapi tidak membuat/mengubah data maupun mencatat pembayaran. Owner
adalah satu-satunya role yang dapat mencatat pengeluaran (`finance.manage`).

Endpoint dashboard bersifat role-aware: backend yang menentukan scope data.
Kurir tidak menerima data keuangan maupun daftar pesanan laundry.

## Catatan Umum

1. Endpoint yang tidak membutuhkan token Sanctum adalah `POST /auth/login`,
   `POST /landing/delivery-requests`, dan `GET /settings` (branding publik).
   Endpoint landing memakai header `X-Landing-Api-Key` yang nilainya hanya
   diketahui backend dan server landing page. Perubahan pengaturan tetap
   membutuhkan token dan permission `settings.manage` (admin).
2. Backend adalah sumber kebenaran untuk harga, `estimasi_selesai`, serta
   `total_terbayar`/`sisa_pembayaran`.
3. Data pembayaran hanya disimpan pada tabel `pembayaran`, bukan `pesanan`.
4. Status pesanan (`status_pesanan`), status pembayaran
   (`status_pembayaran`), dan status proses laundry (`status_proses`) adalah
   tiga hal yang berbeda.
5. Gunakan respons `403` untuk menampilkan pesan akses ditolak dan `422` untuk
   menampilkan error validasi pada field terkait.
