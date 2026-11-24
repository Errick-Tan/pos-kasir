<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_login();
require_post();

$kode = trim((string) ($_POST['kode'] ?? ''));
$back = (string) ($_POST['back'] ?? '');
$aman   = preg_match('#^/(?![/\\\\])#', $back) === 1 && str_contains($back, '/produk/index.php');
$tujuan = $aman ? $back : url('produk/index.php');

try {
    $st = db()->prepare('DELETE FROM produk WHERE kode = :k');
    $st->execute([':k' => $kode]);
    flash($st->rowCount() ? 'ok' : 'error', $st->rowCount() ? 'Produk dihapus.' : 'Produk tidak ditemukan.');
} catch (PDOException $e) {
    if ((int) ($e->errorInfo[1] ?? 0) === 1451) {
        flash('error', 'Produk ini sudah ada di riwayat transaksi, jadi tidak bisa dihapus. Ubah stoknya menjadi 0 jika sudah tidak dijual.');
    } else {
        error_log('[produk.hapus] ' . $e->getMessage());
        flash('error', 'Produk gagal dihapus karena kesalahan database.');
    }
}

header('Location: ' . $tujuan);
exit;
