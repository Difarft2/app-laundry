# API Documentation --- Authentication

Dokumentasi endpoint autentikasi untuk aplikasi **Pemuda Laundry**.

-   **API version:** v1
-   **Base path:** `/api/v1`
-   **Authentication:** Laravel Sanctum Personal Access Token
-   **Format data:** JSON
-   **Status:** Dokumentasi berdasarkan implementasi controller dan
    route saat ini.

> Catatan: Detail field dan aturan validasi request mengikuti Form
> Request yang digunakan backend. Bagian yang belum dapat dipastikan
> dari implementasi controller tidak diasumsikan di dokumen ini.

## 1. Konvensi

### Base URL

Ganti `{{base_url}}` dengan alamat backend yang digunakan.

Contoh lokal:

``` text
http://localhost:8000/api/v1
```

### Header untuk endpoint terproteksi

``` http
Accept: application/json
Authorization: Bearer {{access_token}}
```

Untuk request yang mengirim JSON, tambahkan:

``` http
Content-Type: application/json
```

### Format respons

Respons sukses autentikasi menggunakan properti `success`, `message`,
dan `data` sesuai endpoint. Respons validasi dan error dapat memiliki
struktur berbeda bergantung pada exception handler Laravel yang aktif.

## 2. Daftar endpoint

  Method   Endpoint           Authentication   Keterangan
  -------- ------------------ ---------------- -----------------------------------
  POST     `/auth/login`      Tidak            Login dan memperoleh token
  POST     `/auth/logout`     Ya               Menghapus token aktif
  GET      `/auth/me`         Ya               Mendapatkan profil pengguna login
  PUT      `/auth/profile`    Ya               Memperbarui profil pengguna
  PUT      `/auth/password`   Ya               Memperbarui password

Login dibatasi dengan middleware `throttle:5,1` (maksimal 5 percobaan
dalam satu menit).

## 3. Login

**POST** `/auth/login`

Endpoint publik untuk melakukan autentikasi dan memperoleh Sanctum
personal access token.

### Request body

Contoh field yang digunakan controller:

``` json
{
  "email": "admin@admin.com",
  "password": "your-password",
  "device_name": "web-dashboard"
}
```

`device_name` digunakan sebagai nama token dan memiliki nilai default
`web-dashboard` jika tidak dikirim. Field wajib dan aturan validasi
mengikuti `LoginRequest`.

### Success response --- 200

``` json
{
  "success": true,
  "message": "Login berhasil.",
  "data": {
    "access_token": "1|example-token",
    "token_type": "Bearer",
    "user": {
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
}
```

Contoh data user dan permission di atas bersifat ilustratif. Bentuk
`user` mengikuti `UserResource`.

### Invalid credentials --- 401

``` json
{
  "success": false,
  "message": "Email atau password tidak valid."
}
```

### Catatan integrasi frontend

1.  Kirim request login ke endpoint publik.
2.  Simpan `access_token` secara aman sesuai arsitektur frontend.
3.  Sertakan token sebagai Bearer token untuk endpoint terproteksi.
4.  Gunakan data `user.roles` dan `user.permissions` untuk menyesuaikan
    tampilan akses; backend tetap menjadi penentu otorisasi.

## 4. Logout

**POST** `/auth/logout`

Menghapus token Sanctum yang sedang digunakan untuk request. Token lain
milik pengguna tidak ikut dihapus.

### Header

``` http
Authorization: Bearer {{access_token}}
Accept: application/json
```

### Success response --- 200

``` json
{
  "success": true,
  "message": "Logout berhasil."
}
```

Setelah logout, frontend sebaiknya menghapus token lokal dan mengarahkan
pengguna ke halaman login.

## 5. Current user

**GET** `/auth/me`

Mengambil data pengguna berdasarkan token yang sedang digunakan.

### Header

``` http
Authorization: Bearer {{access_token}}
Accept: application/json
```

### Success response

Respons berupa `UserResource` dengan struktur:

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

Daftar permission di atas hanya contoh. Data aktual mengikuti role dan
permission pengguna.

## 6. Update profile

**PUT** `/auth/profile`

Memperbarui profil pengguna yang sedang login.

### Header

``` http
Authorization: Bearer {{access_token}}
Accept: application/json
Content-Type: application/json
```

### Request body

Struktur field dan aturan validasi harus mengikuti
`UpdateProfileRequest`. Jangan menganggap contoh payload dari frontend
sebagai kontrak final sebelum mencocokkannya dengan Form Request
tersebut.

### Success response

Respons sukses berisi:

-   `success`
-   `message`
-   `data.user` berupa `UserResource`

Contoh bentuk respons:

``` json
{
  "success": true,
  "message": "Profil berhasil diperbarui.",
  "data": {
    "user": {
      "id": 1,
      "name": "Administrator",
      "email": "admin@admin.com",
      "roles": ["owner"],
      "permissions": ["dashboard.view"],
      "created_at": "2026-11-16T08:00:00.000000Z",
      "updated_at": "2026-11-16T08:30:00.000000Z"
    }
  }
}
```

> Pesan sukses di atas merupakan ilustrasi format. Gunakan pesan aktual
> dari implementasi controller.

## 7. Update password

**PUT** `/auth/password`

Memperbarui password akun yang sedang login. Setelah password berhasil
diperbarui, seluruh token milik pengguna dihapus. Pengguna harus login
kembali.

### Header

``` http
Authorization: Bearer {{access_token}}
Accept: application/json
Content-Type: application/json
```

### Request body

Field dan aturan validasi mengikuti `UpdatePasswordRequest`. Cocokkan
nama field dan ketentuan password langsung dengan Form Request backend
sebelum integrasi.

### Success response

``` json
{
  "success": true,
  "message": "Password berhasil diperbarui. Silakan login kembali."
}
```

Frontend harus menghapus token yang tersimpan dan meminta pengguna login
kembali.

## 8. Authentication dan authorization

-   Semua endpoint selain `POST /auth/login` dilindungi `auth:sanctum`.
-   Kirim token dengan skema `Bearer`.
-   Token dibuat saat login.
-   Logout menghapus token yang sedang aktif.
-   Update password menghapus seluruh token pengguna.
-   Hak akses fitur ditentukan melalui role dan permission; autentikasi
    saja tidak otomatis memberikan izin ke semua endpoint.

## 9. Error umum

  -----------------------------------------------------------------------
  HTTP status             Arti                    Penanganan frontend
  ----------------------- ----------------------- -----------------------
  401                     Belum terautentikasi    Minta login kembali
                          atau kredensial login   atau tampilkan pesan
                          tidak valid             kredensial

  403                     Pengguna tidak memiliki Tampilkan pesan akses
                          izin                    ditolak

  422                     Data request tidak      Tampilkan error pada
                          lolos validasi atau     field terkait
                          aturan bisnis

  429                     Batas percobaan login   Beri jeda sebelum
                          terlampaui              mencoba kembali

  500                     Kesalahan server        Tampilkan pesan umum
                                                  dan catat untuk
                                                  debugging
  -----------------------------------------------------------------------

Struktur detail error validasi mengikuti exception response backend.

## 10. Checklist integrasi frontend

-   [ ] Login dan menangani kredensial tidak valid.
-   [ ] Menyimpan token dan mengirim Bearer token.
-   [ ] Memuat data pengguna melalui `/auth/me`.
-   [ ] Menangani token tidak valid atau kedaluwarsa dengan mengarahkan
    ke login.
-   [ ] Logout dan menghapus token lokal.
-   [ ] Memperbarui profil sesuai field `UpdateProfileRequest`.
-   [ ] Memperbarui password sesuai field `UpdatePasswordRequest`.
-   [ ] Menghapus token lokal setelah update password.
-   [ ] Menggunakan role/permission untuk mengatur tampilan, bukan
    sebagai pengganti otorisasi backend.
