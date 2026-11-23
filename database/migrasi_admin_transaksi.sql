-- =====================================================================
--  Migrasi: hubungkan tabel admin -> transaksi (transaksi.id_admin)
--  Pakai HANYA jika database kasir_toko sudah diimpor dari versi lama.
--  Database baru cukup mengimpor kasir_db.sql (sudah memuat perubahan ini).
-- =====================================================================
USE kasir_toko;

-- 1. Tambah kolom (nullable dulu agar data lama tidak menolak)
ALTER TABLE transaksi ADD COLUMN id_admin INT UNSIGNED NULL AFTER id_transaksi;

-- 2. Transaksi lama dianggap dilayani admin pertama
UPDATE transaksi SET id_admin = (SELECT MIN(id_admin) FROM admin) WHERE id_admin IS NULL;

-- 3. Wajibkan nilai + pasang indeks dan foreign key
ALTER TABLE transaksi
  MODIFY id_admin INT UNSIGNED NOT NULL,
  ADD KEY idx_transaksi_admin (id_admin),
  ADD CONSTRAINT fk_transaksi_admin FOREIGN KEY (id_admin)
    REFERENCES admin (id_admin) ON DELETE RESTRICT ON UPDATE CASCADE;
