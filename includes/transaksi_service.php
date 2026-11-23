<?php
declare(strict_types=1);

/** Kesalahan yang aman ditampilkan ke pengguna (stok kurang, uang kurang, dsb). */
class BisnisException extends RuntimeException
{
}

/**
 * @param array<string,int> $items   kode produk => jumlah
 * @param int               $idAdmin id_admin kasir yang sedang login (FK ke tabel admin)
 * @return int id_transaksi baru
 * @throws BisnisException|PDOException
 */
function buat_transaksi(PDO $pdo, array $items, int $bayar, int $idAdmin): int
{
    if (!$items) {
        throw new BisnisException('Pesanan masih kosong.');
    }
    ksort($items); // urutan kunci konsisten -> mengurangi risiko deadlock

    $percobaan = 0;
    while (true) {
        try {
            return jalankan_transaksi($pdo, $items, $bayar, $idAdmin);
        } catch (PDOException $e) {
            // 1213 = deadlock, 1205 = lock wait timeout -> coba ulang sekali
            $kode = (int) ($e->errorInfo[1] ?? 0);
            if (in_array($kode, [1213, 1205], true) && ++$percobaan < 2) {
                continue;
            }
            throw $e;
        }
    }
}

function jalankan_transaksi(PDO $pdo, array $items, int $bayar, int $idAdmin): int
{
    try {
        $pdo->beginTransaction();

        // 1. Header transaksi (total diisi setelah semua item valid)
        $pdo->prepare('INSERT INTO transaksi (id_admin, tanggal, total_harga, bayar, kembalian) VALUES (:a, :t, 0, 0, 0)')
            ->execute([':a' => $idAdmin, ':t' => date('Y-m-d H:i:s')]);
        $idTransaksi = (int) $pdo->lastInsertId();

        // Statement disiapkan sekali, dipakai berulang untuk tiap item
        $cek    = $pdo->prepare('SELECT nama, harga, stok FROM produk WHERE kode = :k FOR UPDATE'); // kunci baris
        $detail = $pdo->prepare(
            'INSERT INTO detail_transaksi (id_transaksi, kode_produk, jumlah_beli, harga_satuan, subtotal)
             VALUES (:id, :k, :qty, :harga, :sub)'
        );
        $kurangi = $pdo->prepare('UPDATE produk SET stok = stok - :qty WHERE kode = :k');

        $total = 0;
        foreach ($items as $kode => $qty) {
            $cek->execute([':k' => $kode]);
            $p = $cek->fetch();

            if (!$p) {
                throw new BisnisException("Produk dengan kode $kode tidak ditemukan.");
            }
            if ((int) $p['stok'] < $qty) {
                throw new BisnisException(sprintf(
                    'Stok "%s" tidak cukup (tersisa %d, diminta %d). Transaksi dibatalkan.',
                    $p['nama'], (int) $p['stok'], $qty
                ));
            }

            $harga    = (int) round((float) $p['harga']);  // harga agar SELALU dari database, bukan dari browser
            $subtotal = $harga * $qty;
            $total   += $subtotal;

            $detail->execute([':id' => $idTransaksi, ':k' => $kode, ':qty' => $qty, ':harga' => $harga, ':sub' => $subtotal]);
            $kurangi->execute([':qty' => $qty, ':k' => $kode]);
        }

        if ($bayar < $total) {
            throw new BisnisException('Uang yang diterima kurang dari total belanja.');
        }

        // 2. Lengkapi header: total, bayar, kembalian
        $pdo->prepare('UPDATE transaksi SET total_harga = :tot, bayar = :bayar, kembalian = :kem WHERE id_transaksi = :id')
            ->execute([':tot' => $total, ':bayar' => $bayar, ':kem' => $bayar - $total, ':id' => $idTransaksi]);

        $pdo->commit();
        return $idTransaksi;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();   // batalkan header, detail, dan pengurangan stok
        }
        throw $e;
    }
}
