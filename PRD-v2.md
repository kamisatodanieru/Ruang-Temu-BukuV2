# PRODUCT REQUIREMENTS DOCUMENT (PRD)
# Ruang Temu Buku — Sistem Informasi Perpustakaan Berbasis Web

| Informasi | Nilai |
|---|---|
| Versi | 2.0 |
| Status | Baseline kebutuhan MVP |
| Tanggal | 6 November 2026 |
| Backend | Laravel |
| Database | MySQL / MariaDB |
| UI | Blade + satu framework CSS (Tailwind CSS atau Bootstrap) |
| Target | MVP perpustakaan fisik |

> Dokumen ini menjadi sumber kebenaran kebutuhan produk. Perubahan fitur atau aturan bisnis harus memperbarui PRD dan disepakati sebelum implementasi.

---

## 1. Ringkasan Produk

Ruang Temu Buku adalah aplikasi perpustakaan berbasis web untuk mengelola katalog, anggota, permintaan peminjaman, persetujuan petugas, pengembalian, dashboard, laporan, dan audit aktivitas.

Sistem memiliki tiga role: Admin, Petugas, dan Anggota. Guest dapat melihat katalog, tetapi harus login sebagai Anggota untuk meminjam.

MVP berfokus pada perpustakaan fisik. Upload e-book, PDF/EPUB reader, reading progress, denda uang, OTP, verifikasi email, dan payment gateway ditunda ke tahap berikutnya.

## 2. Tujuan Produk

1. Memudahkan pengunjung menemukan koleksi buku.
2. Memudahkan anggota mengajukan dan memantau peminjaman.
3. Memudahkan petugas memproses peminjaman dan pengembalian.
4. Memudahkan admin mengelola katalog, kategori, pengguna, dan laporan.
5. Menjaga konsistensi stok dan status transaksi.
6. Melindungi akun, data anggota, dan transaksi.
7. Menyediakan jejak audit aktivitas penting.

## 3. Pengguna dan Hak Akses

### 3.1 Guest
Boleh:
- Melihat katalog dan detail buku.
- Mencari berdasarkan judul/pengarang.
- Memfilter kategori dan ketersediaan.
- Membuka login/register saat memilih Ajukan Pinjam.

Tidak boleh:
- Mengajukan peminjaman.
- Melihat dashboard, profil, atau transaksi.
- Mengakses endpoint internal.

### 3.2 Admin
Boleh:
- Melihat dashboard Admin.
- CRUD buku dan kategori.
- Melihat/mengelola akun pengguna.
- Membuat akun Petugas.
- Mengaktifkan/menonaktifkan akun (Admin tidak dapat menonaktifkan atau mengubah role akunnya sendiri yang sedang login untuk mencegah self-lockout).
- Melihat seluruh transaksi.
- Menyetujui/menolak request dan mengonfirmasi pengembalian.
- Melihat laporan dan audit log.

Admin tidak boleh mengubah atau menghapus histori transaksi sehingga merusak jejak audit.

### 3.3 Petugas
Boleh:
- Melihat dashboard Petugas.
- Melihat request Pending.
- Menyetujui atau menolak request.
- Memberikan alasan penolakan.
- Mengonfirmasi pengembalian.
- Mencari anggota dan transaksi.

Petugas tidak boleh mengelola role Admin, akun Admin, konfigurasi sistem, atau katalog kecuali kewenangan tersebut ditambahkan secara eksplisit.

### 3.4 Anggota
Boleh:
- Register, login, logout.
- Melihat/mengubah profil yang diizinkan.
- Melihat katalog dan detail buku.
- Mengajukan peminjaman satu buku per request.
- Membatalkan request miliknya yang masih Pending.
- Melihat buku yang sedang dipinjam, tanggal jatuh tempo, overdue, dan riwayat miliknya sendiri.

Semua pemeriksaan kepemilikan transaksi wajib dilakukan di server.

## 4. Ruang Lingkup MVP

### 4.1 Autentikasi dan akun
- Register publik, login, logout, lupa/reset password melalui mekanisme Laravel.
- Ketentuan panjang password (registrasi, ubah password profil, reset password, dan pembuatan akun oleh Admin) wajib minimal 15 karakter dan maksimal 64 karakter sesuai standar NIST Special Publication 800-63B Revision 4.
- Password menggunakan Laravel Hash (Argon2id atau Bcrypt yang kompatibel dan dikalibrasi dengan cryptographic random salt unik per pengguna).
- Email unik, validasi server-side, rate limiting bertingkat (maksimal 3 percobaan gagal per sesi dengan durasi lockout bertingkat: Tier 1 selama 1 menit, Tier 2 selama 5 menit, Tier 3 selama 15 menit, dan Tier 4+ selama 1 jam), serta pesan login gagal generik.
- Session diregenerasi setelah login dan diinvalidasi saat logout.
- Status akun `active` atau `inactive`.
- Akun baru langsung `active`.
- Register publik selalu membuat role `anggota`; role/status ditentukan server, bukan browser.
- Register tidak dianggap sebagai verifikasi kepemilikan email. Verifikasi email bukan bagian MVP.
- Admin/Petugas tidak dapat dibuat melalui register publik.

### 4.2 Manajemen buku dan kategori
Admin dapat CRUD buku dan kategori, mengunggah/mengganti cover, dan melihat total stok, stok tersedia, serta jumlah buku yang sedang dipinjam.

Informasi buku: judul, pengarang, penerbit opsional, ISBN opsional, tahun terbit opsional, kategori, total stok, stok tersedia, cover opsional, deskripsi opsional.

Katalog publik memiliki pencarian judul/pengarang, filter kategori/ketersediaan, detail buku, dan stok tersedia. Buku soft-deleted tidak muncul dan tidak dapat dipinjam.

### 4.3 Aturan peminjaman
- Satu request hanya untuk satu buku.
- Maksimal tiga transaksi aktif per anggota.
- Transaksi aktif adalah `pending` + `borrowed`.
- Anggota tidak boleh memiliki transaksi aktif untuk judul/buku yang sama.
- Anggota yang memiliki pinjaman overdue tidak boleh membuat request baru.
- Saat mengajukan request, buku wajib tidak soft-deleted dan memiliki `available_stock > 0`.
- Request Pending tidak mengurangi stok.
- Anggota dapat membatalkan request yang masih Pending.
- Petugas/Admin berwenang dapat menyetujui atau menolak.
- Saat disetujui, status langsung menjadi `borrowed`.
- Durasi peminjaman tujuh hari sejak approval.
- Stok tersedia berkurang satu saat approval berhasil.

### 4.4 Approval
Saat menyetujui request, sistem wajib:
1. Memeriksa otorisasi aktor.
2. Mengunci baris transaksi dan buku dengan urutan locking konsisten.
3. Memastikan request masih `pending`.
4. Memastikan akun anggota masih `active`.
5. Menghitung ulang kuota aktif dan memastikan belum mencapai tiga.
6. Memastikan tidak ada transaksi aktif untuk buku yang sama.
7. Memastikan anggota tidak memiliki overdue.
8. Memastikan buku tidak soft-deleted dan stok tersedia lebih dari nol.
9. Mengurangi `available_stock` satu.
10. Mengisi `approved_by`, `borrowed_at`, dan `due_at`.
11. Mengubah status menjadi `borrowed`.
12. Mencatat audit log.

Semua perubahan harus atomik dalam database transaction. Jika satu pemeriksaan gagal, tidak boleh ada perubahan parsial.

**Makna Borrowed:** buku dianggap sudah diserahkan kepada anggota saat Petugas/Admin menyetujui request. Approval hanya dilakukan ketika buku benar-benar diserahkan. Tidak ada status Approved terpisah.

### 4.5 Penolakan dan pembatalan
- Reject hanya boleh dilakukan pada status `pending`.
- Alasan penolakan wajib dicatat.
- Anggota hanya dapat membatalkan request miliknya yang masih `pending`.
- Status lain tidak dapat dibatalkan.
- Perubahan status mencatat aktor dan waktu.

### 4.6 Pengembalian
- Hanya Petugas/Admin berwenang yang dapat mengonfirmasi pengembalian.
- Hanya transaksi `borrowed` yang dapat dikembalikan.
- Sistem mengisi `returned_at` dan `returned_confirmed_by`.
- Status menjadi `returned`.
- Stok bertambah satu tepat satu kali.
- Proses memakai database transaction dan locking.
- Request pengembalian berulang tidak boleh menambah stok berulang kali.
- Anggota tidak harus login/aktif agar Petugas dapat memproses pengembalian.

### 4.7 Overdue
Overdue bukan status yang disimpan atau diubah manual. Transaksi dianggap overdue jika:
- `status = borrowed`;
- `returned_at IS NULL`; dan
- `due_at < waktu sekarang`.

Sistem menghitung jumlah hari keterlambatan. Anggota dengan pinjaman overdue tidak dapat mengajukan request baru, tetapi tetap dapat melihat transaksi dan mengembalikan buku melalui Petugas. MVP tidak menghitung nominal denda atau pembayaran uang.

### 4.8 Dashboard
**Admin:** jumlah buku, total/stok tersedia, anggota aktif, request Pending, transaksi Borrowed, transaksi Overdue, aktivitas terbaru.

**Petugas:** request Pending, pengembalian yang perlu dikonfirmasi, transaksi Borrowed/Overdue, aktivitas operasional terbaru.

**Anggota:** jumlah Pending, Borrowed, Overdue, daftar buku aktif, tanggal pinjam/jatuh tempo, sisa waktu atau hari terlambat, riwayat terbaru.

Semua dashboard menggunakan data aktual. Anggota hanya melihat data miliknya sendiri.

### 4.9 Laporan
Admin dapat melihat tabel laporan dengan:
- Filter rentang tanggal.
- Filter status.
- Pencarian anggota/judul buku.
- Pagination.
- Print-friendly view.
- Export CSV.

CSV harus menangani CSV Formula Injection, khususnya nilai yang diawali `=`, `+`, `-`, atau `@`. Laporan tidak boleh memuat password/hash/token atau data sensitif yang tidak diperlukan.

### 4.10 Audit log
Catat aktivitas penting:
- Perubahan buku/kategori.
- Perubahan status akun.
- Pembuatan akun Petugas/Admin.
- Pengajuan request.
- Approval/rejection.
- Pembatalan request.
- Konfirmasi pengembalian.

Data minimal: aktor, action, jenis/ID objek, metadata terbatas, IP jika tersedia, user-agent terbatas, timestamp. Audit log hanya dapat dibaca role berwenang dan tidak dapat diubah pengguna biasa. Jangan mencatat password, reset token, session ID, atau rahasia.

## 5. Aturan Bisnis dan Edge Cases

### 5.1 Kuota
Simpan konfigurasi terpusat:

```php
// config/library.php
return [
    'max_active_loans' => 3,
    'loan_duration_days' => 7,
];
```

`active_loans = count(status IN pending, borrowed)`

Jika sudah tiga, request baru ditolak. Pemeriksaan dilakukan backend dan dilindungi dari request bersamaan. Kunci baris user saat proses membuat request, lalu hitung ulang kuota di dalam transaction.

### 5.2 Buku yang sama
Anggota tidak boleh memiliki transaksi `pending` atau `borrowed` untuk `book_id` yang sama. Periksa di backend dalam transaction; jangan hanya mengandalkan frontend.

### 5.3 Blokir overdue
Sebelum membuat request, periksa transaksi `borrowed` dengan `due_at < now()` dan `returned_at IS NULL`. Jika ada, request ditolak dengan pesan yang jelas.

### 5.4 Pending yang lama
- Pending dihitung dalam kuota.
- Pending tidak mengurangi stok.
- Anggota dapat membatalkan Pending miliknya.
- Approval selalu memeriksa ulang stok, status akun, kuota, overdue, dan status buku.
- Auto-expire tidak termasuk MVP.

### 5.5 Perubahan stok
`borrowed_count` dihitung dari transaksi `borrowed` yang belum dikembalikan.

Rumus:
`available_stock = total_stock - borrowed_count`

- `total_stock` baru tidak boleh lebih kecil dari `borrowed_count`.
- `available_stock` dihitung ulang server.
- Admin tidak dapat mengirim `available_stock` sebagai nilai otoritatif.
- Perubahan memakai database transaction dan locking.
- Stok tidak boleh negatif.

### 5.6 Penghapusan kategori
Kategori tidak dapat dihapus jika masih direferensikan buku. Gunakan foreign key `RESTRICT`/`restrictOnDelete()`. Admin harus memindahkan buku ke kategori lain terlebih dahulu.

### 5.7 Penghapusan buku
- Buku menggunakan soft delete.
- Buku tidak dapat di-soft-delete jika memiliki transaksi `pending` atau `borrowed`.
- Histori transaksi tetap disimpan.
- Buku soft-deleted tidak muncul di katalog dan tidak dapat menerima request baru.
- Penghapusan permanen bukan bagian UI MVP.

### 5.8 Akun dinonaktifkan saat masih meminjam
Jika akun Anggota menjadi `inactive`:
- Tidak dapat login.
- Request terproteksi berikutnya memeriksa status akun.
- Session aktif harus diakhiri dan akses ditolak.
- Histori transaksi tidak dihapus.
- Petugas tetap dapat melihat dan memproses pengembalian.
- Penonaktifan tidak mengubah/menghapus transaksi aktif.

### 5.9 Concurrency dan idempotensi
Pembuatan request, pemeriksaan kuota, approval, perubahan stok, dan pengembalian wajib dilindungi dari request bersamaan.

Gunakan `DB::transaction()` dan `lockForUpdate()` pada baris relevan. Tentukan urutan locking konsisten, periksa ulang status setelah lock, rollback seluruh perubahan jika gagal, dan gunakan retry deadlock yang aman bila diperlukan.

Pengembalian hanya dapat mengubah `borrowed` menjadi `returned` satu kali.

## 6. Status dan Transisi Transaksi

Status yang disimpan:
- `pending`
- `borrowed`
- `returned`
- `rejected`
- `cancelled`

`overdue` adalah kondisi turunan, bukan status tersimpan.

| Status awal | Aksi | Status akhir | Aktor |
|---|---|---|---|
| — | Ajukan | pending | Anggota |
| pending | Approve/serah buku | borrowed | Petugas/Admin |
| pending | Reject | rejected | Petugas/Admin |
| pending | Cancel | cancelled | Anggota pemilik |
| borrowed | Konfirmasi kembali | returned | Petugas/Admin |

Transisi lain harus ditolak. Status akhir tidak boleh diterima bebas dari input browser.

## 7. Arsitektur UI dan Navigasi

### 7.1 Layout
- Sidebar desktop; menjadi drawer pada layar kecil.
- Topbar berisi nama aplikasi, breadcrumb/judul, tombol toggle Mode Gelap/Terang (Dark/Light Mode), dan menu akun.
- Area konten memakai card, tabel, form, badge, dan modal konsisten.
- Mendukung Mode Terang (Light) dan Mode Gelap (Dark) dengan kontras aksesibel serta otomatis kembali ke mode terang saat mencetak (print view).
- Responsif desktop/tablet/ponsel.
- Sediakan empty, loading, validation, success, dan error state.
- Gunakan komponen Blade bersama.
- Seluruh kolom input password (Login, Register, Ubah Password Profil, Reset Password, dan Tambah Akun Admin) dilengkapi fitur toggle opsi mata (Show/Hide Password) untuk kenyamanan dan akurasi pengguna saat menginput passphrase.
- Pilih satu CSS framework: Tailwind atau Bootstrap.

### 7.2 Navigasi
**Guest:** Katalog Buku, Login, Register.

**Admin:** Dashboard, Manajemen Buku, Kategori, Pengguna, Peminjaman, Pengembalian, Laporan, Audit Log, Profil, Logout.

**Petugas:** Dashboard, Permintaan Peminjaman, Pengembalian, Pencarian Anggota/Transaksi, Profil, Logout.

**Anggota:** Dashboard, Katalog Buku, Buku Saya, Riwayat Peminjaman, Profil, Logout.

Dashboard anggota menampilkan ringkasan Pending/Borrowed/Overdue dan daftar buku aktif dengan cover, judul, pengarang, tanggal pinjam, jatuh tempo, serta detail transaksi. Hanya data anggota yang login yang boleh tampil.

## 8. Rancangan Database MVP

### 8.1 `roles`
- `id` BIGINT PK
- `name` VARCHAR unik (`admin`, `petugas`, `anggota`)
- timestamps

### 8.2 `users`
- `id` BIGINT PK
- `role_id` BIGINT FK roles
- `name` VARCHAR(150)
- `email` VARCHAR(150) unik
- `password` VARCHAR(255) hash
- `status` (`active`, `inactive`)
- `remember_token` sesuai Laravel
- timestamps

Gunakan struktur reset password bawaan Laravel sesuai versi yang dipilih.

### 8.3 `categories`
- `id` BIGINT PK
- `name` VARCHAR(100) unik
- timestamps

### 8.4 `books`
- `id` BIGINT PK
- `category_id` BIGINT FK categories, restrict delete
- `title` VARCHAR(255)
- `author` VARCHAR(150)
- `publisher` VARCHAR(150) nullable
- `isbn` VARCHAR(30) nullable, unik jika diisi
- `publication_year` YEAR/SMALLINT nullable
- `total_stock` INT UNSIGNED
- `available_stock` INT UNSIGNED
- `cover_path` VARCHAR(255) nullable
- `description` TEXT nullable
- `deleted_at` nullable (soft delete)
- timestamps

`available_stock` hanya dikelola server.

### 8.5 `loans`
Satu request hanya memiliki satu buku:
- `id` BIGINT PK
- `member_id` BIGINT FK users
- `book_id` BIGINT FK books
- `approved_by` BIGINT nullable FK users
- `rejected_by` BIGINT nullable FK users
- `returned_confirmed_by` BIGINT nullable FK users
- `requested_at` DATETIME
- `borrowed_at` DATETIME nullable
- `due_at` DATETIME nullable
- `returned_at` DATETIME nullable
- `rejected_at` DATETIME nullable
- `cancelled_at` DATETIME nullable
- `status`
- `rejection_reason` TEXT nullable
- timestamps

Index yang dipertimbangkan: `(member_id, status)`, `(book_id, status)`, `(due_at, status)`, serta index foreign key. Index bukan pengganti authorization atau validasi aturan bisnis.

### 8.6 `activity_logs`
- `id` BIGINT PK
- `user_id` BIGINT nullable FK users
- `action` VARCHAR(100)
- `subject_type` VARCHAR(100) nullable
- `subject_id` BIGINT nullable
- `description` TEXT nullable
- `ip_address` VARCHAR(45) nullable
- `user_agent` VARCHAR(500) nullable
- `created_at`

Metadata harus dibatasi dan tidak berisi rahasia.

### 8.7 Relasi
- roles 1:N users.
- users 1:N loans melalui member_id.
- users 1:N loans melalui approved_by.
- users 1:N loans melalui rejected_by.
- users 1:N loans melalui returned_confirmed_by.
- categories 1:N books.
- books 1:N loans.
- users 1:N activity_logs.

Tidak ada `loan_items` karena satu request hanya satu buku.

## 9. Persyaratan Keamanan

### Authentication
- Gunakan autentikasi Laravel sesuai versi proyek.
- Gunakan Laravel Hash dengan Argon2id atau Bcrypt yang kompatibel dan dikalibrasi.
- Jangan menggunakan SHA-256 biasa untuk password.
- Jangan menyimpan password plaintext.
- Rate limit login dan endpoint sensitif.
- Pesan login generik.
- Regenerate session setelah login; invalidate saat logout.
- Logout menggunakan POST.
- Periksa status akun.
- Reset password menggunakan mekanisme Laravel.

### Authorization
- Deny by default.
- Middleware untuk authentication dan role.
- Policy untuk resource.
- Ownership check untuk setiap transaksi anggota.
- Menyembunyikan menu bukan kontrol akses.
- ID sulit ditebak bukan pengganti authorization.
- Test akses lintas role dan lintas anggota.

### SQL Injection dan input
- Utamakan Eloquent/Query Builder.
- Raw SQL harus memakai parameter binding.
- Validasi semua input di server.
- Gunakan Form Request bila sesuai.
- Jangan menggabungkan input ke SQL.
- Escape output Blade secara default.
- Hindari HTML mentah dari input pengguna.

### CSRF, XSS, session
- Pertahankan CSRF protection.
- Jangan menonaktifkan middleware keamanan untuk mengatasi error.
- Hindari menyimpan token sensitif di browser storage tanpa kebutuhan.
- Terapkan security headers sesuai kebutuhan dan uji kompatibilitas.

### Upload cover
- Validasi MIME, ekstensi, ukuran, dan konten.
- Gunakan allowlist format gambar.
- Nama file acak.
- Jangan percaya nama file dari pengguna.
- Jangan izinkan SVG tanpa sanitasi dan alasan kuat.
- Simpan di lokasi yang tidak dapat mengeksekusi script.
- E-book bukan bagian MVP.

### Database dan deployment
- Foreign key, unique constraint, dan index yang sesuai.
- Transaction dan row locking untuk operasi kritis.
- Database account dengan least privilege.
- Secret hanya di `.env`/secret manager; jangan commit `.env`.
- Matikan debug di production.
- HTTPS dan pembatasan akses database.
- Backup dan uji restore.

### Logging
- Jangan log password, token reset, session ID, atau secret.
- Catat aktivitas minimum yang diperlukan.
- Batasi akses audit log.
- Jangan tampilkan stack trace/query internal ke pengguna.

## 10. Non-Functional Requirements

- Maintainability: PSR-12, konvensi Laravel, SOLID secara proporsional.
- Usability: alur mudah dipahami.
- Responsive: desktop, tablet, ponsel.
- Reliability: stok dan transaksi konsisten.
- Security: akses ditolak secara default.
- Performance: pagination, eager loading, index, query efisien.
- Accessibility: label, keyboard navigation, kontras, pesan error.
- Observability: log tanpa membocorkan rahasia.

## 11. Acceptance Criteria

### Authentication
- Register membuat Anggota Active.
- Manipulasi request tidak dapat membuat Admin/Petugas.
- Email duplikat ditolak.
- Password disimpan sebagai hash.
- Login gagal menggunakan pesan generik.
- Rate limiting berfungsi.
- Akun Inactive tidak dapat login.
- Session diregenerasi saat login dan diinvalidasi saat logout.

### Katalog
- Guest dapat membuka katalog/detail.
- Guest tidak dapat meminjam.
- Pencarian/filter berfungsi.
- Buku soft-deleted tidak terlihat dan tidak dapat dipinjam.

### Peminjaman
- Satu request satu buku.
- Kuota maksimal tiga Pending + Borrowed.
- Buku sama tidak dapat diminta jika masih Pending/Borrowed oleh anggota.
- Anggota overdue tidak dapat request.
- Pending tidak mengurangi stok.
- Anggota dapat membatalkan Pending miliknya.
- Approval mengubah Pending menjadi Borrowed.
- Approval mengurangi stok tepat satu.
- borrowed_at dan due_at tercatat saat approval.
- due_at tujuh hari setelah borrowed_at.
- Request bersamaan tidak melewati kuota atau membuat stok negatif.

### Pengembalian
- Hanya Borrowed dapat dikembalikan.
- Pengembalian mengubah status menjadi Returned.
- Stok bertambah tepat satu kali.
- Pengembalian ganda ditolak/ditangani secara idempotent.
- Petugas dapat memproses pengembalian anggota Inactive.

### Buku dan kategori
- Admin dapat mengelola buku/kategori.
- Total stok tidak boleh di bawah borrowed_count.
- Stok tersedia dihitung server.
- Kategori yang masih direferensikan tidak dapat dihapus.
- Buku dengan Pending/Borrowed tidak dapat di-soft-delete.
- Histori tetap terjaga.

### Dashboard, laporan, audit
- Dashboard sesuai role.
- Anggota hanya melihat transaksi sendiri.
- Overdue dihitung dari due_at dan status pengembalian.
- Admin dapat memfilter laporan.
- CSV aman dari formula injection.
- Audit log mencatat aktivitas kritis.

## 12. Strategi Pengujian

**Authentication:** register valid, email duplikat, manipulasi role/status, password hashing, login valid/invalid, rate limit, akun inactive, logout/session invalidation, reset password.

**Authorization:** Guest ke halaman privat, Anggota ke endpoint Admin/Petugas, Petugas ke fungsi Admin terlarang, Anggota A mengakses transaksi B, manipulasi ID, session aktif akun inactive.

**Business logic:** kuota tiga, request buku sama, request saat overdue, Pending tidak mengurangi stok, approval stok tersedia/habis, approval request yang telah diproses, dua approval bersamaan untuk stok terakhir, dua request bersamaan saat anggota punya dua transaksi aktif, perubahan total stok di bawah borrowed_count, pengembalian ganda, penghapusan kategori/buku dengan relasi aktif.

**Input dan file:** SQL injection diperlakukan sebagai data, XSS escaping, file tidak diizinkan/terlalu besar ditolak, path traversal dicegah, CSV formula injection dinetralisasi.

Test otomatis bukan bukti bahwa sistem bebas kerentanan.

## 13. Roadmap

1. **Milestone 1 — Foundation & Authentication:** setup Laravel, environment, database, roles/users, seeder, register/login/logout/reset, rate limit, status akun, middleware, Policy, test.
2. **Milestone 2 — UI Shell & Katalog:** design system, layout per role, CRUD buku/kategori, upload cover, katalog publik, pencarian/filter/detail.
3. **Milestone 3 — Peminjaman:** request satu buku, kuota, overdue check, approval/rejection, stok locking, pembatalan Pending, concurrency dan ownership test.
4. **Milestone 4 — Pengembalian & Dashboard:** konfirmasi pengembalian, pencegahan pengembalian ganda, dashboard role, buku aktif, riwayat.
5. **Milestone 5 — Laporan & Audit:** audit log, laporan filter, print view, CSV aman.
6. **Milestone 6 — Security Review:** regression test, route/middleware/policy review, mass assignment, upload, query, privilege DB, backup/restore, dokumentasi.

## 14. Di Luar MVP

- Upload e-book.
- PDF/EPUB reader.
- Reading progress.
- Peminjaman digital.
- OTP dan verifikasi email.
- Notifikasi email/SMS/WhatsApp.
- Denda uang dan pembayaran online.
- Payment gateway.
- Multi-cabang.
- Manajemen rak kompleks.
- Aplikasi mobile native.

## 15. Keputusan Produk yang Dikunci

| Keputusan | Nilai MVP |
|---|---|
| Satu request | Satu buku |
| Status saat approval | Langsung `borrowed` |
| Kuota aktif | 3 transaksi (`pending` + `borrowed`) |
| Buku sama | Tidak boleh ada transaksi aktif ganda |
| Durasi pinjam | 7 hari sejak approval |
| Status akun saat register | `active` |
| Role register publik | `anggota` |
| Katalog Guest | Publik |
| Pending mengurangi stok | Tidak |
| Overdue | Dihitung otomatis |
| Blokir request karena overdue | Ya |
| Denda uang | Tidak termasuk MVP |
| Kategori delete | Restrict jika masih direferensikan |
| Buku delete | Soft delete; blokir jika Pending/Borrowed |
| Laporan | Filter, print view, CSV |
| E-book | Tahap berikutnya |

Konfigurasi kebijakan (kuota dan durasi) harus terpusat di `config/library.php`, bukan tersebar di controller/view.

## 16. Definition of Done

Fitur dianggap selesai jika:
1. Sesuai PRD.
2. Validasi server-side tersedia.
3. Authorization dan ownership check tersedia.
4. Error handling tidak membocorkan detail internal.
5. Test positif dan negatif ditambahkan.
6. Migration dan constraint sesuai.
7. UI responsif dengan state yang diperlukan.
8. Formatter/linter dan test dijalankan.
9. Tidak ada secret/debug code ikut di-commit.
10. Dokumentasi diperbarui.

**Prinsip produk: Secure by default, simple by design, data integrity first.**
