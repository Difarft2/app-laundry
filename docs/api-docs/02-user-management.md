# API Documentation --- User Management

Dokumentasi endpoint manajemen pengguna untuk aplikasi **Pemuda
Laundry**.

-   **API version:** v1
-   **Base path:** `/api/v1`
-   **Authentication:** Laravel Sanctum Personal Access Token
-   **Authorization:** Spatie Laravel Permission
-   **Format data:** JSON
-   **Status:** Dokumentasi berdasarkan implementasi controller dan
    route saat ini.

> Catatan: Nama field request dan aturan validasi mengikuti Form Request
> backend. Dokumen ini tidak mengarang skema request yang belum
> dikonfirmasi.

## 1. Konvensi

### Header

Semua endpoint pada dokumen ini memerlukan:

``` http
Accept: application/json
Authorization: Bearer {{access_token}}
```

Untuk request JSON:

``` http
Content-Type: application/json
```

### Base URL

``` text
{{base_url}}/api/v1
```

Contoh lokal: `http://localhost:8000/api/v1`.

## 2. Permission

  Permission       Kegunaan
  ---------------- -----------------------------------------
  `users.view`     Melihat daftar dan detail pengguna
  `users.create`   Membuat pengguna
  `users.update`   Memperbarui pengguna dan reset password
  `users.delete`   Menghapus pengguna

Permission diperiksa backend melalui middleware. Menyembunyikan tombol
di frontend bukan pengganti pemeriksaan permission di server.

## 3. Daftar endpoint

  --------------------------------------------------------------------------------------
  Method            Endpoint                         Permission        Keterangan
  ----------------- -------------------------------- ----------------- -----------------
  GET               `/users`                         `users.view`      Daftar pengguna

  GET               `/users/{user}`                  `users.view`      Detail pengguna

  POST              `/users`                         `users.create`    Membuat pengguna

  PUT               `/users/{user}`                  `users.update`    Memperbarui
                                                                       pengguna

  PUT               `/users/{user}/reset-password`   `users.update`    Reset password
                                                                       pengguna

  DELETE            `/users/{user}`                  `users.delete`    Menghapus
                                                                       pengguna
  --------------------------------------------------------------------------------------

Semua endpoint di atas juga memerlukan autentikasi `auth:sanctum`.

## 4. Daftar pengguna

**GET** `/users`

Mengambil daftar pengguna dengan pagination. Data diurutkan dari yang
terbaru.

### Query parameters

  Parameter   Tipe      Keterangan
  ----------- --------- -------------------------------------------
  `search`    string    Pencarian pada nama, email, atau nomor HP
  `role`      string    Filter berdasarkan role
  `page`      integer   Nomor halaman pagination

Jumlah data per halaman pada implementasi saat ini adalah 15.

Contoh:

``` http
GET /api/v1/users?search=admin&role=owner&page=1
```

### Success response

Respons menggunakan Laravel resource collection dengan data pengguna dan
metadata pagination. Contoh struktur data item:

``` json
{
  "id": 1,
  "name": "Administrator",
  "email": "admin@admin.com",
  "roles": ["owner"],
  "permissions": [
    "dashboard.view",
    "users.view",
    "users.create"
  ],
  "created_at": "2026-11-16T08:00:00.000000Z",
  "updated_at": "2026-11-16T08:00:00.000000Z"
}
```

Field `roles` dan `permissions` mengikuti `UserResource`. Nomor HP dapat
digunakan untuk pencarian, tetapi tidak termasuk dalam `UserResource`
yang saat ini diimplementasikan.

## 5. Detail pengguna

**GET** `/users/{user}`

Mengambil detail satu pengguna berdasarkan ID.

### Path parameters

  Parameter   Tipe      Keterangan
  ----------- --------- -------------
  `user`      integer   ID pengguna

Contoh:

``` http
GET /api/v1/users/1
```

### Success response

``` json
{
  "data": {
    "id": 1,
    "name": "Administrator",
    "email": "admin@admin.com",
    "roles": ["owner"],
    "permissions": [
      "dashboard.view",
      "users.view",
      "users.create"
    ],
    "created_at": "2026-11-16T08:00:00.000000Z",
    "updated_at": "2026-11-16T08:00:00.000000Z"
  }
}
```

## 6. Membuat pengguna

**POST** `/users`

Membuat akun pengguna baru dan menyinkronkan role yang diberikan.

### Request body

Field dan aturan validasi mengikuti `StoreUserRequest`. Controller
menggunakan data tervalidasi, mengambil `roles` untuk proses
`syncRoles`, lalu menyimpan data pengguna lainnya.

Karena skema `StoreUserRequest` belum dicantumkan dalam sumber
dokumentasi ini, frontend harus mencocokkan nama field,
required/nullable, format, dan aturan password langsung dengan file Form
Request backend sebelum mengirim payload.

### Aturan bisnis

-   Role yang dikirim akan disinkronkan ke pengguna baru.
-   Hanya pengguna dengan role `owner` yang boleh memberikan role
    `owner`.
-   Pembuatan pengguna dijalankan dalam database transaction.
-   Endpoint memerlukan permission `users.create`.

### Success response --- 201

Respons berisi resource pengguna yang baru dibuat.

``` json
{
  "data": {
    "id": 2,
    "name": "Karyawan Laundry",
    "email": "karyawan@example.com",
    "roles": ["karyawan"],
    "permissions": [
      "orders.view",
      "laundry.view"
    ],
    "created_at": "2026-11-16T09:00:00.000000Z",
    "updated_at": "2026-11-16T09:00:00.000000Z"
  }
}
```

Contoh permission di atas ilustratif; nilai aktual berasal dari
konfigurasi role dan permission.

## 7. Memperbarui pengguna

**PUT** `/users/{user}`

Memperbarui data pengguna. Jika field `roles` disertakan, controller
akan memvalidasi aturan pemberian role dan menyinkronkan role tersebut.

### Path parameters

  Parameter   Tipe      Keterangan
  ----------- --------- ----------------------------------
  `user`      integer   ID pengguna yang akan diperbarui

### Request body

Field dan aturan validasi mengikuti `UpdateUserRequest`. `roles`
bersifat opsional pada alur controller: role hanya disinkronkan apabila
field tersebut disertakan dan lolos validasi.

Jangan mengirim payload berdasarkan asumsi. Cocokkan seluruh field
dengan `UpdateUserRequest`.

### Aturan bisnis

-   Hanya owner yang boleh memberikan role `owner`.
-   Owner terakhir tidak boleh diturunkan role-nya.
-   Perubahan data dan role dijalankan dalam database transaction.
-   Endpoint memerlukan permission `users.update`.

### Success response --- 200

Respons berisi resource pengguna terbaru.

## 8. Reset password pengguna

**PUT** `/users/{user}/reset-password`

Mengatur ulang password pengguna tertentu. Setelah password diubah,
seluruh token Sanctum milik pengguna target dihapus sehingga sesi/token
lama tidak dapat digunakan lagi.

### Path parameters

  Parameter   Tipe      Keterangan
  ----------- --------- ---------------------------------------
  `user`      integer   ID pengguna yang password-nya direset

### Request body

Field dan aturan validasi mengikuti `ResetUserPasswordRequest`. Pastikan
frontend mengikuti nama field dan aturan password pada Form Request
tersebut.

### Success response

``` json
{
  "message": "Password pengguna berhasil direset."
}
```

Endpoint memerlukan permission `users.update`.

## 9. Menghapus pengguna

**DELETE** `/users/{user}`

Menghapus pengguna berdasarkan ID.

### Path parameters

  Parameter   Tipe      Keterangan
  ----------- --------- -------------------------------
  `user`      integer   ID pengguna yang akan dihapus

### Aturan bisnis

-   Pengguna tidak dapat menghapus akun sendiri.
-   Owner terakhir tidak dapat dihapus.
-   Token milik pengguna target dihapus sebelum record pengguna dihapus.
-   Proses penghapusan dijalankan dalam database transaction.
-   Endpoint memerlukan permission `users.delete`.

### Success response

``` json
{
  "message": "Pengguna berhasil dihapus."
}
```

### Business rule error

Jika pengguna mencoba menghapus akun sendiri, backend mengembalikan HTTP
`422` dengan pesan:

``` json
{
  "message": "Anda tidak dapat menghapus akun sendiri."
}
```

Jika target adalah owner terakhir, backend mengembalikan HTTP `422`
dengan pesan:

``` json
{
  "message": "Owner terakhir tidak dapat dihapus atau diturunkan rolenya."
}
```

## 10. Aturan khusus role owner

-   Hanya pengguna dengan role `owner` yang boleh memberikan role
    `owner`.
-   Owner terakhir tidak dapat dihapus.
-   Owner terakhir tidak dapat diturunkan role-nya.
-   Pemeriksaan aturan tersebut dilakukan backend, bukan hanya frontend.

## 11. HTTP status umum

  -----------------------------------------------------------------------
  HTTP status             Arti                    Penanganan frontend
  ----------------------- ----------------------- -----------------------
  200                     Request berhasil        Tampilkan data/pesan
                                                  sukses

  201                     Pengguna berhasil       Perbarui daftar
                          dibuat                  pengguna

  401                     Belum terautentikasi    Arahkan ke login

  403                     Tidak memiliki          Tampilkan akses ditolak
                          permission

  404                     Pengguna tidak          Tampilkan data tidak
                          ditemukan               ditemukan

  422                     Validasi atau aturan    Tampilkan pesan
                          bisnis gagal            validasi

  500                     Kesalahan server        Tampilkan pesan umum
                                                  dan catat untuk
                                                  debugging
  -----------------------------------------------------------------------

## 12. Checklist integrasi frontend

-   [ ] Menampilkan daftar pengguna dan pagination.
-   [ ] Mendukung pencarian nama, email, dan nomor HP.
-   [ ] Mendukung filter role.
-   [ ] Menampilkan detail pengguna.
-   [ ] Membuat pengguna sesuai `StoreUserRequest`.
-   [ ] Memperbarui pengguna sesuai `UpdateUserRequest`.
-   [ ] Menampilkan pilihan role sesuai kebijakan aplikasi.
-   [ ] Membatasi UI pemberian role owner kepada owner, sambil tetap
    menangani respons 403.
-   [ ] Reset password sesuai `ResetUserPasswordRequest`.
-   [ ] Menghapus pengguna dengan konfirmasi.
-   [ ] Menangani larangan menghapus akun sendiri dan owner terakhir.
-   [ ] Menangani 401, 403, 404, dan 422.

## 13. Catatan pemeliharaan dokumentasi

Saat backend mengubah route, controller, resource, middleware, atau Form
Request pada modul User Management, dokumentasi ini harus diperbarui
dalam pull request yang sama.

File Form Request yang perlu dijadikan acuan untuk melengkapi kontrak
request:

-   `LoginRequest` (didokumentasikan pada `01-authentication.md`)
-   `StoreUserRequest`
-   `UpdateUserRequest`
-   `ResetUserPasswordRequest`
