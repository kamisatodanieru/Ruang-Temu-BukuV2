# AGENT.md

## Pedoman Kerja AI Agent --- Ruang Temu Buku

Dokumen ini adalah aturan kerja wajib bagi AI coding agent yang
mengembangkan Ruang Temu Buku. Baca dokumen ini dan `PRD.md` sebelum
mengubah atau membuat kode.

------------------------------------------------------------------------

## 1. Misi dan Prioritas

Bangun sistem informasi perpustakaan berbasis Laravel yang aman, mudah
dirawat, dan sesuai PRD.

Urutan prioritas: 1. Keamanan dan kontrol akses. 2. Kebenaran aturan
bisnis dan konsistensi data. 3. Clean Code dan maintainability. 4. UX
yang konsisten dan aksesibel. 5. Performa yang proporsional. 6.
Kecepatan implementasi.

Jangan mengorbankan keamanan atau integritas data demi implementasi
cepat.

## 2. Aturan Sebelum Coding

-   Baca `PRD.md`, `AGENT.md`, `composer.json`, struktur proyek,
    migration, route, model, controller, dan test yang sudah ada.
-   Periksa versi Laravel dan PHP aktual dari proyek. Jangan
    mengasumsikan versi terbaru.
-   Jangan mengganti framework, database, atau dependency utama tanpa
    alasan dan persetujuan.
-   Jangan menimpa pekerjaan yang sudah ada tanpa memeriksa dampaknya.
-   Jika proyek belum dibuat, siapkan Laravel dengan versi stabil yang
    kompatibel dengan versi PHP lingkungan.
-   Buat rencana singkat sebelum perubahan lintas modul.
-   Jika ada persyaratan yang bertentangan, utamakan keamanan, lalu
    klarifikasi; jangan diam-diam menebak aturan bisnis.
-   Implementasikan secara bertahap dan pastikan setiap tahap dapat
    dijalankan.

## 3. Teknologi dan Arsitektur

-   Backend: Laravel.
-   Database: MySQL/MariaDB.
-   UI: Blade dengan satu pendekatan styling yang konsisten, misalnya
    Tailwind CSS atau Bootstrap. Jangan mencampur framework CSS tanpa
    kebutuhan.
-   ORM: Eloquent.
-   Validasi: Form Request untuk validasi yang digunakan controller.
-   Authorization: middleware, Gate, dan Policy.
-   File: Laravel Storage.
-   Testing: PHPUnit atau Pest sesuai setup proyek; jangan memasang
    keduanya tanpa alasan.
-   Ikuti pola MVC Laravel dan gunakan service/action class hanya saat
    logika bisnis memang perlu dipisahkan.

### Batas tanggung jawab

-   **Controller:** menerima request, memanggil validasi/otorisasi dan
    use case, mengembalikan response. Hindari controller gemuk.
-   **Form Request:** validasi dan authorization tingkat request jika
    sesuai.
-   **Policy:** aturan siapa boleh melakukan tindakan terhadap resource.
-   **Model:** relasi, cast, scope, dan perilaku model yang wajar.
-   **Service/Action:** orkestrasi aturan bisnis kompleks atau transaksi
    yang digunakan ulang.
-   **View:** presentasi; jangan menaruh query database atau aturan
    keamanan di Blade.
-   **Middleware:** pemeriksaan lintas request seperti autentikasi dan
    role.
-   **Migration:** sumber kebenaran struktur database.

Jangan membuat abstraksi, repository, interface, trait, atau service
untuk setiap hal secara otomatis. Terapkan abstraksi saat mengurangi
duplikasi atau kompleksitas nyata.

## 4. Clean Code Wajib

-   Ikuti PSR-12 dan konvensi penamaan Laravel.
-   Gunakan nama class, method, variable, route, dan kolom yang
    deskriptif.
-   Satu method sebaiknya memiliki satu tanggung jawab yang jelas.
-   Hindari duplikasi; gunakan helper atau komponen bersama bila memang
    sesuai.
-   Hindari magic number dan string status yang tersebar. Gunakan enum
    PHP atau konstanta terpusat jika kompatibel dengan versi proyek.
-   Hindari nested condition yang terlalu dalam; pecah logika bila
    meningkatkan keterbacaan.
-   Hindari controller/model yang terlalu besar.
-   Jangan menambahkan komentar yang hanya mengulang kode. Komentari
    alasan atau keputusan yang tidak jelas.
-   Hapus kode mati, debug statement, dan dependency yang tidak
    digunakan.
-   Jangan menggunakan `*` pada query produksi tanpa alasan.
-   Gunakan pagination untuk daftar data.
-   Cegah N+1 query dengan eager loading yang tepat.
-   Gunakan database index berdasarkan pola pencarian dan relasi.
-   Jangan mengorbankan keterbacaan dengan optimasi prematur.

## 5. Security by Design

### 5.1 Autentikasi

-   Gunakan mekanisme autentikasi Laravel yang sesuai versi proyek.
-   Gunakan `Hash::make()` dan `Hash::check()` melalui mekanisme
    Laravel; jangan membuat algoritma hash sendiri.
-   Jangan pernah menyimpan password plaintext, mengirimkannya ke log,
    atau mengembalikannya di response.
-   Terapkan rate limiting untuk login dan endpoint sensitif.
-   Gunakan pesan login generik agar tidak mengungkap keberadaan email.
-   Regenerate session setelah login.
-   Saat logout, invalidate session dan regenerate CSRF token.
-   Gunakan POST untuk logout.
-   Terapkan reset password dengan token Laravel dan respons yang tidak
    membocorkan keberadaan akun.
-   Periksa status akun pada proses autentikasi dan tindakan penting.
-   Jangan membuat mekanisme autentikasi buatan sendiri jika fitur
    framework sudah memadai.

### 5.2 Register dan role

-   Register publik hanya boleh membuat akun Anggota.
-   Jangan menerima `role`, `role_id`, `is_admin`, `status`, atau
    atribut privileged dari input publik.
-   Tetapkan role Anggota di server.
-   Pembuatan akun Admin/Petugas hanya melalui proses administratif yang
    terotorisasi atau seeder awal yang aman.
-   Jangan membuat akun admin default dengan password tetap yang
    tertanam di repository.
-   Lindungi assignment role dengan validasi, policy, dan
    mass-assignment safeguards.

### 5.3 Authorization dan IDOR

-   Terapkan prinsip deny by default.
-   Periksa role di server untuk setiap route yang dilindungi.
-   Gunakan Policy untuk akses terhadap resource.
-   Periksa ownership setiap kali anggota melihat, mengubah,
    membatalkan, atau mengakses transaksi.
-   Jangan mempercayai ID dari URL/request sebagai bukti kepemilikan.
-   Jangan hanya menyembunyikan tombol/menu di UI.
-   Jangan mengandalkan UUID atau ID yang sulit ditebak sebagai kontrol
    akses.
-   Gunakan binding/query yang dibatasi ownership bila sesuai, lalu
    tetap lakukan authorization.
-   Buat test negatif untuk akses lintas role dan lintas pengguna.

### 5.4 SQL Injection dan input

-   Utamakan Eloquent dan Query Builder.
-   Jika raw SQL benar-benar diperlukan, gunakan parameter binding.
-   Jangan menggabungkan input pengguna ke string SQL.
-   Validasi input di server dengan Form Request/Validator.
-   Validasi format, panjang, rentang, dan allowlist sesuai konteks.
-   Escape output sesuai konteks; Blade `{{ }}` adalah pilihan default.
-   Hindari `{!! !!}` kecuali konten telah disanitasi dengan benar dan
    kebutuhan HTML memang sah.
-   Validasi bukan pengganti parameterized query.

### 5.5 CSRF, XSS, dan browser

-   Pertahankan CSRF middleware untuk form berbasis session.
-   Jangan menonaktifkan proteksi CSRF untuk mengatasi error tanpa
    analisis.
-   Gunakan escaping output default Blade.
-   Jangan memasukkan data tidak tepercaya ke JavaScript/HTML secara
    mentah.
-   Terapkan security headers yang sesuai dan uji dampaknya terhadap
    aset/aplikasi.
-   Jangan menyimpan token atau rahasia di localStorage tanpa kebutuhan
    arsitektural yang jelas.

### 5.6 Upload file

-   Untuk MVP, upload hanya cover buku.
-   Gunakan allowlist tipe file yang benar-benar diperlukan.
-   Periksa ukuran, MIME/content, ekstensi, dan dimensi bila relevan.
-   Jangan percaya nama file atau Content-Type dari browser saja.
-   Buat nama file acak; jangan gunakan nama file pengguna sebagai path.
-   Simpan di storage yang tidak dapat mengeksekusi file.
-   Jangan menyimpan file upload ke direktori source code atau direktori
    publik yang dapat mengeksekusi script.
-   Batasi akses file sesuai kebutuhan.
-   Jangan mengizinkan SVG atau format aktif lain tanpa sanitasi dan
    alasan yang kuat.
-   E-book tidak termasuk MVP; jangan mengimplementasikannya sebelum
    diminta.

### 5.7 Database dan transaksi

-   Gunakan foreign key, unique constraint, dan index yang tepat.
-   Gunakan database transaction untuk perubahan multi-tabel yang harus
    atomik.
-   Gunakan locking yang sesuai untuk stok agar tidak terjadi
    overselling/race condition.
-   Jangan mempercayai stok yang dikirim browser.
-   Validasi ulang stok di dalam transaksi database.
-   Pastikan pengembalian tidak dapat diproses dua kali sehingga stok
    bertambah ganda.
-   Gunakan akun database aplikasi dengan privilege minimum.
-   Jangan mengubah schema produksi secara manual tanpa migration.
-   Jangan menaruh credential di source code.

### 5.8 Error, log, dan data pribadi

-   Jangan menampilkan stack trace, SQL, path server, `.env`, atau
    credential kepada pengguna.
-   Jangan mencatat password, token reset, session ID, atau data
    sensitif ke log.
-   Catat aktivitas penting dengan data minimum yang diperlukan.
-   Audit log harus memiliki akses baca terbatas dan tidak dapat diubah
    pengguna biasa.
-   Jangan menyimpan data pribadi yang tidak dibutuhkan.
-   Batasi retensi log sesuai kebutuhan dan kebijakan proyek.

### 5.9 Konfigurasi dan deployment

-   Rahasia hanya di `.env` atau secret manager.
-   `.env` tidak boleh di-commit; `.env.example` berisi placeholder
    tanpa rahasia.
-   Matikan debug pada production.
-   Gunakan HTTPS pada deployment.
-   Batasi akses database dari jaringan.
-   Jangan membuka port database ke internet.
-   Pastikan storage dan permission filesystem aman.
-   Jangan menjalankan migration destruktif pada production tanpa
    backup, rencana, dan persetujuan.

## 6. Aturan UI/UX

-   Ikuti arsitektur UI dalam PRD.
-   Gunakan layout konsisten: sidebar, topbar, area konten.
-   Sidebar desktop berubah menjadi drawer pada layar kecil.
-   Menu harus sesuai role, tetapi penyembunyian menu bukan pengganti
    authorization server-side.
-   Gunakan komponen konsisten untuk button, input, tabel, card, badge
    status, modal, dan alert.
-   Sediakan empty state, loading state, validation state, success
    state, dan error state.
-   Form harus memiliki label dan pesan kesalahan yang jelas.
-   Tampilkan konfirmasi untuk tindakan destruktif atau perubahan status
    penting.
-   Jangan tampilkan informasi internal seperti hash password, ID
    internal yang tidak diperlukan, atau detail keamanan.
-   Pastikan tampilan responsif dan dapat dinavigasi dengan keyboard.
-   Jangan membuat dashboard dengan angka statis/dummy setelah integrasi
    data selesai.

## 7. Aturan Database dan Naming

Gunakan konvensi Laravel: - Tabel plural snake_case: `users`, `books`,
`loans`. - Kolom snake_case: `created_at`, `member_id`. - Model singular
StudlyCase: `Book`, `Loan`. - Foreign key mengikuti konvensi Laravel
bila memungkinkan. - Gunakan migration untuk semua perubahan schema. -
Tambahkan constraint di database, bukan hanya validasi aplikasi. -
Gunakan soft delete hanya jika kebutuhan pemulihan/audit memang ada;
jangan soft-delete transaksi keuangan/operasional tanpa kebijakan
jelas. - Status transaksi harus mengikuti PRD dan tidak boleh berubah
melalui update bebas dari browser.

Jika desain satu request satu buku dipilih, jangan membuat `loan_items`.
Jika satu request banyak buku dipilih, gunakan `loan_items` dan
konsisten pada seluruh relasi, query, dan UI.

## 8. Testing Wajib

Setiap fitur harus disertai test yang relevan. Minimal:

### Authentication

-   Register valid berhasil.
-   Email duplikat ditolak.
-   Role register selalu Anggota.
-   Manipulasi request untuk membuat Admin/Petugas gagal.
-   Password tersimpan sebagai hash.
-   Login valid berhasil.
-   Login invalid gagal dengan pesan generik.
-   Rate limit berfungsi.
-   Akun nonaktif ditolak.
-   Logout mengakhiri session.

### Authorization

-   Guest tidak dapat membuka halaman privat.
-   Anggota ditolak dari route Admin/Petugas.
-   Petugas ditolak dari fungsi Admin yang tidak diberikan.
-   Anggota A tidak dapat melihat transaksi Anggota B.
-   Anggota A tidak dapat mengubah atau membatalkan transaksi Anggota B.
-   Mengganti ID resource tidak melewati Policy.

### Business logic

-   Request tanpa stok ditolak.
-   Persetujuan memperbarui status dan stok secara konsisten.
-   Dua request bersamaan tidak membuat stok negatif.
-   Penolakan mencatat alasan dan pelaku.
-   Pembatalan hanya diizinkan saat status Pending.
-   Pengembalian hanya dapat diproses sekali.
-   Stok bertambah tepat satu kali saat pengembalian.

### Input dan file

-   Input invalid ditolak server.
-   Payload SQL injection diperlakukan sebagai data.
-   Output teks di-escape.
-   File tidak diizinkan ditolak.
-   File terlalu besar ditolak.
-   Nama file tidak dapat melakukan path traversal.

Jangan menyatakan fitur aman hanya karena test berhasil. Jelaskan
cakupan test dan batasannya.

## 9. Workflow Implementasi

Untuk setiap milestone: 1. Baca PRD dan kode yang relevan. 2. Nyatakan
rencana file yang akan dibuat/diubah. 3. Buat migration/model terlebih
dahulu jika diperlukan. 4. Implementasikan validasi dan authorization
bersama fitur, bukan belakangan. 5. Buat UI sesuai layout dan role. 6.
Tambahkan test positif dan negatif. 7. Jalankan formatter/linter dan
test suite yang tersedia. 8. Periksa diff untuk secret, debug code,
query berbahaya, dan perubahan tak terkait. 9. Laporkan file yang
berubah, migration, test yang dijalankan, hasil, serta risiko atau
pekerjaan tersisa.

Jangan mengklaim test berhasil jika belum benar-benar dijalankan.

## 10. Git dan Perubahan Kode

-   Buat commit kecil berdasarkan satu fitur/perubahan logis.
-   Gunakan pesan commit deskriptif, misalnya
    `feat(auth): add secure member registration`.
-   Jangan commit `.env`, credential, dump database berisi data pribadi,
    atau file upload pengguna.
-   Jangan melakukan force push atau menghapus branch tanpa persetujuan.
-   Jangan menjalankan perintah destruktif seperti `migrate:fresh`,
    `db:wipe`, atau penghapusan massal tanpa konfirmasi dan backup.
-   Jangan mengubah dependency atau lockfile tanpa kebutuhan yang jelas.

## 11. Larangan

AI agent dilarang: - Menaruh password atau secret di source code. -
Membuat register publik yang menerima role privileged. - Mengandalkan
validasi frontend untuk keamanan. - Menyembunyikan menu sebagai
satu-satunya kontrol akses. - Menggunakan query SQL dengan interpolasi
input. - Menonaktifkan CSRF/auth middleware untuk menghilangkan error. -
Menggunakan `{!! $input !!}` untuk input tidak tepercaya. - Mengabaikan
ownership check. - Menambah fitur di luar PRD tanpa menyebutkan dan
meminta persetujuan. - Membuat dummy data seolah-olah data produksi. -
Menghapus test yang gagal hanya agar pipeline hijau. - Mengklaim
keamanan sempurna atau bebas kerentanan.

## 12. Format Laporan Setelah Bekerja

Setelah setiap tugas, laporkan:

### Ringkasan

Apa yang diimplementasikan dan tujuan perubahannya.

### File berubah

Daftar file baru/diubah dan tanggung jawabnya.

### Database

Migration, tabel, kolom, index, dan constraint yang ditambahkan/diubah.

### Keamanan

Validasi, autentikasi, authorization, ownership check, dan mitigasi yang
diterapkan.

### Pengujian

Perintah test/formatter yang dijalankan dan hasil sebenarnya.

### Catatan

Asumsi, risiko, keterbatasan, dan pekerjaan lanjutan.

------------------------------------------------------------------------

## Prinsip terakhir

**Secure by default. Simple by design. Clean code over clever code.**

Bangun hanya sesuai kebutuhan yang disepakati, tetapi setiap fitur yang
dibangun harus memiliki validasi, authorization, penanganan error, dan
test yang memadai.
