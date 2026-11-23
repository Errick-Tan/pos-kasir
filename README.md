# Sistem Kasir Toko (Point of Sale) — PHP Native + PDO

Proyek Pemrograman Web — Studi Kasus 2. PHP native tanpa framework, MySQL/MariaDB lewat **PDO**.

## Fitur
- Login admin (`password_hash` / `password_verify`, sesi aman, auto-logout 30 menit)
- **Kasir**: pilih produk → pesanan → bayar → struk. Pesanan bisa **diubah jumlahnya, dihapus per item, atau dikosongkan — semuanya bisa diurungkan**. Keranjang tersimpan di browser sehingga tidak hilang saat halaman dimuat ulang.
- Penyimpanan transaksi memakai **transaction** (`beginTransaction` / `commit` / `rollBack`); stok kurang ⇒ seluruh proses dibatalkan
- Stok berkurang otomatis setelah transaksi berhasil
- CRUD produk + pencarian + pagination
- Riwayat transaksi (filter tanggal, pagination) dan struk siap cetak
- **Mode terang/gelap**: tombol bulan/matahari di sidebar (atau bilah atas di HP); pilihan tersimpan di browser, dan mengikuti pengaturan sistem jika belum dipilih
- Laporan penjualan harian + total pendapatan (`GROUP BY`), produk terlaris, grafik

## Kebutuhan
PHP 8.1+ (ekstensi `pdo_mysql`, `mbstring` — aktif secara bawaan di XAMPP) dan MySQL 8.0.16+ / MariaDB 10.2+.

## Cara menjalankan (XAMPP)
1. Salin folder ini ke `C:\xampp\htdocs\pos-kasir`.
2. Jalankan **Apache** dan **MySQL** di XAMPP Control Panel.
3. Buka `http://localhost/phpmyadmin` → **Import** → pilih `database/kasir_db.sql`.
4. (Jika perlu) sesuaikan user/password database di `config/database.php`.
5. Buka `http://localhost/pos-kasir/` dan masuk dengan **admin / admin123**.

Tanpa XAMPP: `php -S localhost:8000` dari dalam folder proyek, lalu buka `http://localhost:8000`.

## Struktur folder
```
pos-kasir/
├── config/            database.php (koneksi PDO), app.php (nama toko, konstanta)
├── includes/          bootstrap, helpers (XSS/CSRF/pagination), auth, layout, transaksi_service
├── produk/            index (cari + pagination), form (tambah/ubah), hapus
├── transaksi/         baru (kasir), simpan, struk, riwayat
├── laporan/           harian
├── assets/            css/app.css, js/app.js, js/kasir.js
├── database/          kasir_db.sql (struktur + data contoh)
├── docs/              ERD.md
├── index.php  login.php  logout.php
```

## Pemetaan ke rubrik penilaian
| Aspek | Letak di kode |
|---|---|
| Koneksi & PDO (prepared statement, error handling) | `config/database.php` (ERRMODE_EXCEPTION, `EMULATE_PREPARES=false`); semua query memakai placeholder; setiap halaman memakai `try/catch (PDOException)`, detail error hanya ke `error_log` |
| Desain database & relasi | `database/kasir_db.sql`, `docs/ERD.md` (FK, UNIQUE, CHECK) |
| CRUD, pencarian, pagination | `produk/*`, `transaksi/riwayat.php`, `paginate()` di `includes/helpers.php` |
| Transaction & logika bisnis | `includes/transaksi_service.php` — `SELECT … FOR UPDATE` mengunci baris stok, rollback bila stok/uang kurang, retry otomatis saat deadlock |
| Keamanan | SQL injection: prepared statement · XSS: `e()` (= `htmlspecialchars`) di semua output · password: bcrypt · sesi: `session_regenerate_id`, cookie HttpOnly + SameSite, timeout idle · CSRF token di semua POST · harga selalu dibaca dari database (bukan dari browser) · whitelist redirect |
| Kerapian & dokumentasi | struktur di atas, komentar kode, README ini |

Query JOIN: `transaksi/struk.php`, `transaksi/riwayat.php`, `laporan/harian.php`. Agregasi: `COUNT`, `SUM` di beranda dan laporan.

## Catatan
- Ganti password default `admin123` sebelum dipakai sungguhan: buat hash baru dengan `php -r "echo password_hash('PasswordBaru', PASSWORD_DEFAULT);"` lalu `UPDATE admin SET password_hash = '…'`.
- Pintasan kasir: tekan `/` untuk mencari; ketik kode produk lalu `Enter` untuk langsung menambahkannya (cocok untuk pemindai barcode).

## Berkontribusi (cara ikut commit)

Panduan ini untuk anggota kelompok atau siapa pun yang ingin ikut mengubah proyek ini lewat GitHub.

### A. Untuk pemilik repo: beri akses ke teman
1. Buka repo `https://github.com/Mr-DSP/pos-kasir`, lalu klik **Settings → Collaborators → Add people**.
2. Ketik username atau email GitHub teman Anda, pilih akunnya, lalu klik **Add**.
3. Teman akan menerima undangan lewat email atau notifikasi GitHub. Ia harus menekan **Accept invitation** supaya bisa ikut commit.

Untuk repo **Private**, hanya orang yang diundang dengan cara ini yang bisa melihat dan mengubah kode. Agar `main` tetap aman, sebaiknya semua orang bekerja di branch masing-masing lalu mengirim *pull request* (lihat bagian B).

### B. Untuk kontributor yang sudah diundang
1. **Salin repo ke komputer (sekali saja)**
   ```bash
   git clone https://github.com/Mr-DSP/pos-kasir.git
   cd pos-kasir
   ```
2. **Siapkan lokal**: ikuti bagian *Cara menjalankan* di atas (impor `database/kasir_db.sql`, sesuaikan `config/database.php` untuk komputer Anda sendiri).
3. **Ambil perubahan terbaru lalu buat branch baru** (satu branch untuk satu tujuan)
   ```bash
   git checkout main
   git pull origin main
   git checkout -b fitur/nama-singkat-fitur
   ```
4. **Ubah kode, lalu uji** sampai semua halaman terbuka tanpa error.
5. **Commit perubahan**
   ```bash
   git status
   git add .
   git commit -m "tambah: filter produk berdasarkan stok"
   ```
6. **Kirim branch ke GitHub**
   ```bash
   git push -u origin fitur/nama-singkat-fitur
   ```
   Saat diminta login, gunakan akun GitHub Anda. Jika meminta password, pakai *Personal Access Token* (GitHub → Settings → Developer settings), atau gunakan GitHub Desktop.
7. **Buka Pull Request**: di halaman repo, klik tombol **Compare & pull request**, tulis ringkasan perubahan, lalu klik **Create pull request**.
8. **Review dan merge**: pemilik repo memeriksa perubahan lalu menekan **Merge pull request**. Setelah itu, perbarui komputer Anda:
   ```bash
   git checkout main
   git pull origin main
   ```

### C. Belum diundang? Gunakan fork
1. Klik **Fork** di pojok kanan atas halaman repo untuk membuat salinan di akun Anda.
2. Clone fork tersebut (`git clone https://github.com/USERNAME-ANDA/pos-kasir.git`), buat branch, commit, dan push seperti bagian B langkah 3 sampai 6.
3. Di GitHub, klik **Contribute → Open pull request** untuk mengirim perubahan ke repo asli.

### D. Mengatasi konflik
Jika saat `git pull` muncul pesan *conflict*:
1. Buka file yang ditandai. Cari tanda `<<<<<<<`, `=======`, dan `>>>>>>>`, lalu pilih atau gabungkan isi yang benar dan hapus ketiga tanda itu.
2. Jalankan `git add nama-file`, lalu `git commit`.
3. Jalankan `git push` seperti biasa.

### Aturan bersama
- Tulis pesan commit singkat dan jelas dengan awalan seperti `tambah:`, `perbaiki:`, `ubah:`, atau `dokumentasi:`.
- Jangan commit password database, file `.env`, atau data pribadi. File `.gitignore` sudah menyaring sebagian, tetapi tetap periksa dengan `git status` sebelum commit.
- Jangan langsung push ke `main`; gunakan branch dan pull request.
- Selalu jalankan `git pull origin main` sebelum mulai bekerja agar tidak ketinggalan perubahan teman.
.