# Sistem Kasir Toko (Point of Sale) — PHP Native + PDO

Proyek Pemrograman Web — Studi Kasus 2. PHP native tanpa framework, MySQL/MariaDB lewat **PDO**.

## Fitur
- Login admin (`password_hash` / `password_verify`, sesi aman, auto-logout 30 menit)
- **Kasir**: pilih produk → pesanan → bayar → struk. Pesanan bisa **diubah jumlahnya, dihapus per item, atau dikosongkan — semuanya bisa diurungkan**. Keranjang tersimpan di browser sehingga tidak hilang saat halaman dimuat ulang.
- Setiap transaksi mencatat **kasir yang melayani** (`transaksi.id_admin` → `admin`), tampil di struk dan riwayat
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
   - Sudah pernah impor versi lama? Jalankan `database/migrasi_admin_transaksi.sql` saja (menambah kolom `transaksi.id_admin` + foreign key).
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
├── database/          kasir_db.sql (struktur + data contoh), migrasi_admin_transaksi.sql (untuk DB lama)
├── docs/              ERD.md
├── index.php  login.php  logout.php
```

## Pemetaan ke rubrik penilaian
| Aspek | Letak di kode |
|---|---|
| Koneksi & PDO (prepared statement, error handling) | `config/database.php` (ERRMODE_EXCEPTION, `EMULATE_PREPARES=false`); semua query memakai placeholder; setiap halaman memakai `try/catch (PDOException)`, detail error hanya ke `error_log` |
| Desain database & relasi | `database/kasir_db.sql`, `docs/ERD.md` (4 tabel saling terhubung lewat FK: admin→transaksi→detail_transaksi←produk; UNIQUE, CHECK) |
| CRUD, pencarian, pagination | `produk/*`, `transaksi/riwayat.php`, `paginate()` di `includes/helpers.php` |
| Transaction & logika bisnis | `includes/transaksi_service.php` — `SELECT … FOR UPDATE` mengunci baris stok, rollback bila stok/uang kurang, retry otomatis saat deadlock |
| Keamanan | SQL injection: prepared statement · XSS: `e()` (= `htmlspecialchars`) di semua output · password: bcrypt · sesi: `session_regenerate_id`, cookie HttpOnly + SameSite, timeout idle · CSRF token di semua POST · harga selalu dibaca dari database (bukan dari browser) · whitelist redirect |
| Kerapian & dokumentasi | struktur di atas, komentar kode, README ini |

Query JOIN: `transaksi/struk.php`, `transaksi/riwayat.php`, `laporan/harian.php`. Agregasi: `COUNT`, `SUM` di beranda dan laporan.

## Catatan
- Ganti password default `admin123` sebelum dipakai sungguhan: buat hash baru dengan `php -r "echo password_hash('PasswordBaru', PASSWORD_DEFAULT);"` lalu `UPDATE admin SET password_hash = '…'`.
- Pintasan kasir: tekan `/` untuk mencari; ketik kode produk lalu `Enter` untuk langsung menambahkannya (cocok untuk pemindai barcode).
