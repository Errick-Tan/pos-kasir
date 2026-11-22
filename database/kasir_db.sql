-- =====================================================================
--  Sistem Kasir Toko (Point of Sale) - struktur dan data contoh
--  Impor lewat phpMyAdmin atau:  mysql -u root -p < database/kasir_db.sql
-- =====================================================================
CREATE DATABASE IF NOT EXISTS kasir_toko
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kasir_toko;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS detail_transaksi;
DROP TABLE IF EXISTS transaksi;
DROP TABLE IF EXISTS produk;
DROP TABLE IF EXISTS admin;
SET FOREIGN_KEY_CHECKS = 1;

-- Akun admin untuk login (password disimpan sebagai hash bcrypt)
CREATE TABLE admin (
  id_admin      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username      VARCHAR(50)  NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  nama_lengkap  VARCHAR(100) NOT NULL,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_admin),
  UNIQUE KEY uq_admin_username (username)
) ENGINE=InnoDB;

CREATE TABLE produk (
  kode  VARCHAR(20)   NOT NULL,
  nama  VARCHAR(100)  NOT NULL,
  harga DECIMAL(12,2) NOT NULL,
  stok  INT           NOT NULL DEFAULT 0,
  PRIMARY KEY (kode),
  KEY idx_produk_nama (nama),
  CONSTRAINT chk_produk_harga CHECK (harga >= 0),
  CONSTRAINT chk_produk_stok  CHECK (stok  >= 0)
) ENGINE=InnoDB;

CREATE TABLE transaksi (
  id_transaksi INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  tanggal      DATETIME      NOT NULL,
  total_harga  DECIMAL(12,2) NOT NULL DEFAULT 0,
  bayar        DECIMAL(12,2) NOT NULL DEFAULT 0,
  kembalian    DECIMAL(12,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (id_transaksi),
  KEY idx_transaksi_tanggal (tanggal)
) ENGINE=InnoDB;

CREATE TABLE detail_transaksi (
  id_detail    INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  id_transaksi INT UNSIGNED  NOT NULL,
  kode_produk  VARCHAR(20)   NOT NULL,
  jumlah_beli  INT           NOT NULL,
  harga_satuan DECIMAL(12,2) NOT NULL,
  subtotal     DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (id_detail),
  UNIQUE KEY uq_detail_item (id_transaksi, kode_produk),
  CONSTRAINT fk_detail_transaksi FOREIGN KEY (id_transaksi)
    REFERENCES transaksi (id_transaksi) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_detail_produk FOREIGN KEY (kode_produk)
    REFERENCES produk (kode) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_detail_jumlah CHECK (jumlah_beli > 0)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
--  Data contoh   (login: admin / admin123)
-- ---------------------------------------------------------------------
INSERT INTO admin (username, password_hash, nama_lengkap) VALUES
('admin', '$2y$10$uSsE3.8voQB5EawfDqQB1.nOwlWPEYVORIowrLwhHWguoD5FMrIFm', 'Admin Toko');

INSERT INTO produk (kode, nama, harga, stok) VALUES
('BRG001', 'Indomie Goreng',            3500, 120),
('BRG002', 'Air Mineral 600 ml',        4000,  96),
('BRG003', 'Teh Botol 350 ml',          5000,  48),
('BRG004', 'Kopi Sachet Mix',           2000, 200),
('BRG005', 'Gula Pasir 1 kg',          17500,  30),
('BRG006', 'Minyak Goreng 1 L',        18000,  24),
('BRG007', 'Beras Premium 5 kg',       78000,  15),
('BRG008', 'Telur Ayam (butir)',        2500, 150),
('BRG009', 'Roti Tawar',               16000,  12),
('BRG010', 'Susu UHT Coklat 200 ml',    6500,  40),
('BRG011', 'Sabun Mandi Batang',        4500,  36),
('BRG012', 'Detergen Bubuk 800 g',     22000,   4),
('BRG013', 'Sabun Cuci Piring 755 ml', 14000,  18),
('BRG014', 'Keripik Kentang 68 g',     11000,   0),
('BRG015', 'Mie Kuah Soto',             3300,   5);

INSERT INTO transaksi (id_transaksi, tanggal, total_harga, bayar, kembalian) VALUES
(1, TIMESTAMP(CURDATE(), '09:15:00'),                      25500,  30000,  4500),
(2, TIMESTAMP(CURDATE(), '11:40:00'),                      96000, 100000,  4000),
(3, TIMESTAMP(CURDATE() - INTERVAL 1 DAY, '10:05:00'),     28000,  30000,  2000),
(4, TIMESTAMP(CURDATE() - INTERVAL 1 DAY, '16:20:00'),     42500,  50000,  7500),
(5, TIMESTAMP(CURDATE() - INTERVAL 2 DAY, '13:30:00'),     52000,  60000,  8000),
(6, TIMESTAMP(CURDATE() - INTERVAL 3 DAY, '08:50:00'),     38500,  40000,  1500),
(7, TIMESTAMP(CURDATE() - INTERVAL 4 DAY, '17:10:00'),     58000,  60000,  2000),
(8, TIMESTAMP(CURDATE() - INTERVAL 5 DAY, '12:00:00'),     27500,  30000,  2500);

INSERT INTO detail_transaksi (id_transaksi, kode_produk, jumlah_beli, harga_satuan, subtotal) VALUES
(1, 'BRG001', 5,  3500, 17500), (1, 'BRG002', 2,  4000,  8000),
(2, 'BRG007', 1, 78000, 78000), (2, 'BRG006', 1, 18000, 18000),
(3, 'BRG003', 3,  5000, 15000), (3, 'BRG010', 2,  6500, 13000),
(4, 'BRG008',10,  2500, 25000), (4, 'BRG005', 1, 17500, 17500),
(5, 'BRG001', 8,  3500, 28000), (5, 'BRG004', 6,  2000, 12000), (5, 'BRG002', 3, 4000, 12000),
(6, 'BRG009', 2, 16000, 32000), (6, 'BRG010', 1,  6500,  6500),
(7, 'BRG012', 2, 22000, 44000), (7, 'BRG013', 1, 14000, 14000),
(8, 'BRG001', 4,  3500, 14000), (8, 'BRG011', 3,  4500, 13500);
