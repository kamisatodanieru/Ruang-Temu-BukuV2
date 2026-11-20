# Ruang Temu Buku v2 — Sistem Informasi Perpustakaan Berbasis Web

Aplikasi pengelolaan operasional perpustakaan fisik berbasis **Laravel 12** dan **MySQL / MariaDB** yang dibangun dengan prinsip **Security by Design**, **Clean Code**, dan integritas transaksi data.

---

## 1. Fitur Utama (MVP)

- **Autentikasi & Akun Aman:**
  - Registrasi publik khusus Anggota (penetapan role `anggota` dan status `active` secara otoritatif di server).
  - Login dengan *progressive rate limiting* (maks. 3 percobaan gagal per sesi dengan timeout bertingkat: 1 menit, 5 menit, 15 menit, hingga 1 jam), pesan kegagalan generik anti-enumerasi, dan *session regeneration*.
  - Logout wajib menggunakan metode `POST` dengan invalidasi session & regenerasi CSRF token.
  - Lupa & Reset Password menggunakan token broker Laravel dengan respons generik.
  - Pencabutan sesi aktif otomatis (*mid-session revocation*) apabila akun dinonaktifkan oleh Admin.
- **Role-Based Access Control (RBAC) & Anti-IDOR:**
  - Tiga role: `admin`, `petugas`, dan `anggota` (serta pengunjung `guest` untuk Katalog Publik).
  - Otorisasi berlapis menggunakan Middleware (`auth`, `active`, `role`) dan Policy (`UserPolicy`, `CategoryPolicy`, `BookPolicy`, `LoanPolicy`, `ActivityLogPolicy`).
  - Pemeriksaan kepemilikan (*ownership check*) ketat pada setiap transaksi anggota.
- **Manajemen Katalog & Koleksi:**
  - CRUD Kategori dengan proteksi `RESTRICT` jika masih direferensikan buku.
  - CRUD Buku dengan perhitungan `available_stock = total_stock - borrowed_count` secara otoritatif di server, *soft delete* yang diblokir bila ada transaksi `pending`/`borrowed`, dan upload cover aman (allowlist JPG/PNG/WEBP, validasi MIME & dimensi, penamaan UUID acak).
  - Katalog Publik dengan pencarian judul/pengarang, filter kategori, dan filter ketersediaan.
- **Sirkulasi Peminjaman & Pengembalian:**
  - Pengajuan 1 buku per request dengan kuota maksimal 3 transaksi aktif (`pending` + `borrowed`), larangan duplikasi buku aktif yang sama, dan blokir otomatis jika memiliki pinjaman *overdue*.
  - Persetujuan (*approval*), penolakan (*rejection* dengan alasan wajib), pembatalan `Pending`, dan konfirmasi pengembalian yang dilindungi `DB::transaction()` serta `lockForUpdate()` dengan urutan *row locking* konsisten.
- **Dashboard, Laporan, & Audit Log:**
  - Dashboard aktual spesifik untuk Admin, Petugas, dan Anggota.
  - Laporan transaksi dengan filter tanggal/status, tampilan *print-friendly*, serta **Export CSV** dengan sanitasi anti-*CSV Formula Injection*.
  - Jejak audit (*append-only* `activity_logs`) untuk seluruh aktivitas kritis.

---

## 2. Persyaratan Lingkungan

- **PHP:** `>= 8.2`
- **Database:** MySQL 8.0+ / MariaDB 10.6+
- **Composer:** `2.x`

---

## 3. Panduan Instalasi & Konfigurasi

1. **Salin konfigurasi environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
2. **Konfigurasi database di `.env`:**
   Gunakan akun database dengan hak akses minimum (*least privilege*: `SELECT`, `INSERT`, `UPDATE`, `DELETE`, serta `CREATE`/`ALTER`/`INDEX`/`REFERENCES` saat migrasi):
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=ruang_temu_bukuv2
   DB_USERNAME=root
   DB_PASSWORD=
   ```
3. **Jalankan migrasi, seeder awal, dan symlink storage:**
   ```bash
   php artisan migrate --seed
   php artisan storage:link
   ```
   > **Catatan Keamanan Seeder:** `DatabaseSeeder` membuat 3 role (`admin`, `petugas`, `anggota`) dan 1 akun Admin awal dengan password acak sekali tampil di terminal (atau membaca dari variabel environment `INITIAL_ADMIN_EMAIL` dan `INITIAL_ADMIN_PASSWORD`). Tidak ada password tetap yang tertanam di kode sumber.

---

## 4. Konfigurasi Kebijakan Perpustakaan

Kebijakan kuota dan durasi peminjaman dikelola secara terpusat di `config/library.php`:
- `LIBRARY_MAX_ACTIVE_LOANS` (default: `3` transaksi aktif `pending` + `borrowed`)
- `LIBRARY_LOAN_DURATION_DAYS` (default: `7` hari sejak disetujui)

---

## 5. Pengujian Otomatis & Standar Kode

- **Menjalankan seluruh test suite (Unit, Feature, Authorization, IDOR, Concurrency, Security Regression):**
  ```bash
  php artisan test
  ```
- **Memeriksa standar gaya kode PSR-12 / Laravel Pint:**
  ```bash
  php vendor/bin/pint --test
  ```

---

## 6. Panduan Backup & Restore Database

- **Backup Database:**
  ```bash
  mysqldump -u <username> -p --single-transaction --routines --triggers ruang_temu_bukuv2 > backup_ruang_temu_buku_$(date +%F).sql
  ```
- **Restore Database:**
  ```bash
  mysql -u <username> -p ruang_temu_bukuv2 < backup_ruang_temu_buku_YYYY-MM-DD.sql
  ```
  > Jangan pernah melakukan commit file dump database yang berisi data pengguna ke dalam repositori Git.
