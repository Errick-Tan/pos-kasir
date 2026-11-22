# ERD — Sistem Kasir Toko

```mermaid
erDiagram
    PRODUK ||--o{ DETAIL_TRANSAKSI : "dimasukkan ke"
    TRANSAKSI ||--|{ DETAIL_TRANSAKSI : "memiliki"

    PRODUK {
        varchar kode PK
        varchar nama
        decimal harga
        int stok
    }
    TRANSAKSI {
        int id_transaksi PK
        datetime tanggal
        decimal total_harga
        decimal bayar "tambahan"
        decimal kembalian "tambahan"
    }
    DETAIL_TRANSAKSI {
        int id_detail PK
        int id_transaksi FK
        varchar kode_produk FK
        int jumlah_beli
        decimal harga_satuan
        decimal subtotal
    }
    ADMIN {
        int id_admin PK
        varchar username UK
        varchar password_hash
        varchar nama_lengkap
        timestamp created_at
    }
```

**Perbedaan dari ERD awal (tiga tabel inti tidak berubah):**

| Perubahan | Alasan |
|---|---|
| Tabel `admin` (berdiri sendiri, tanpa relasi) | Dibutuhkan fitur login dengan `password_hash()` / `password_verify()` |
| Kolom `transaksi.bayar` dan `transaksi.kembalian` | Struk perlu menampilkan uang diterima dan kembalian |
| `UNIQUE (id_transaksi, kode_produk)` pada detail | Satu produk hanya satu baris per transaksi; jumlah digabung |
| `CHECK (stok >= 0)` dan `CHECK (jumlah_beli > 0)` | Lapisan pengaman terakhir di level database |

**Aturan relasi**
- `detail_transaksi.id_transaksi → transaksi` : `ON DELETE CASCADE`
- `detail_transaksi.kode_produk → produk` : `ON DELETE RESTRICT` (produk yang sudah terjual tidak bisa dihapus, agar riwayat tetap utuh)
