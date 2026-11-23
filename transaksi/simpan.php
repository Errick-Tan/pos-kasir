<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_login();
require_post();

// 1. Baca & validasi keranjang dari browser (isinya hanya kode + jumlah; harga selalu diambil dari DB)
$cart  = json_decode((string) ($_POST['cart'] ?? ''), true);
$bayar = (int) preg_replace('/\D/', '', (string) ($_POST['bayar'] ?? ''));
$items = [];

if (is_array($cart) && count($cart) <= 200) {
    foreach ($cart as $row) {
        $kode = is_array($row) ? ($row['kode'] ?? null) : null;
        $qty  = is_array($row) ? ($row['qty'] ?? null) : null;
        if (!is_string($kode) || !preg_match('/^[A-Za-z0-9_-]{1,20}$/', $kode) || !is_int($qty) || $qty < 1 || $qty > 9999) {
            $items = [];
            break;
        }
        $kode = strtoupper($kode);
        $items[$kode] = ($items[$kode] ?? 0) + $qty;
    }
}

if (!$items) {
    flash('error', 'Pesanan tidak valid atau masih kosong.');
    redirect('transaksi/baru.php');
}

// 2. Simpan dengan transaction
try {
    $id = buat_transaksi(db(), $items, $bayar, (int) current_admin()['id']);
    flash('ok', 'Transaksi berhasil disimpan.');
    redirect('transaksi/struk.php?id=' . $id . '&baru=1');
} catch (BisnisException $e) {
    flash('error', $e->getMessage());               // pesan aman: stok kurang, uang kurang, dsb.
} catch (PDOException $e) {
    error_log('[simpan] ' . $e->getMessage());
    flash('error', 'Terjadi kesalahan pada database. Transaksi dibatalkan, stok tidak berubah.');
}
redirect('transaksi/baru.php');   // keranjang tetap tersimpan di browser, kasir bisa memperbaikinya
