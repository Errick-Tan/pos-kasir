<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_login();

$id   = (int) ($_GET['id'] ?? 0);
$baru = isset($_GET['baru']);

try {
    $st = db()->prepare('SELECT id_transaksi, tanggal, total_harga, bayar, kembalian FROM transaksi WHERE id_transaksi = :id');
    $st->execute([':id' => $id]);
    $trx = $st->fetch();

    if (!$trx) {
        flash('error', 'Transaksi tidak ditemukan.');
        redirect('transaksi/riwayat.php');
    }

    // JOIN detail_transaksi dengan produk untuk mengambil nama barang
    $st = db()->prepare('SELECT d.kode_produk, p.nama, d.jumlah_beli, d.harga_satuan, d.subtotal
                         FROM detail_transaksi d JOIN produk p ON p.kode = d.kode_produk
                         WHERE d.id_transaksi = :id ORDER BY d.id_detail');
    $st->execute([':id' => $id]);
    $items = $st->fetchAll();
} catch (PDOException $e) {
    error_log('[struk] ' . $e->getMessage());
    http_response_code(500);
    exit('Terjadi kesalahan saat membaca transaksi.');
}

$jumlahBarang = array_sum(array_column($items, 'jumlah_beli'));

layout_start('Struk #' . $id, 'riwayat', '', $baru ? 'data-clear-cart' : '');
?>
<div class="page-head no-print">
    <div>
        <?php if ($baru): ?>
            <span class="success-mark" aria-hidden="true"><?= icon('check', 28) ?></span>
            <h1>Pembayaran berhasil</h1>
            <p>Kembalian <strong><?= e(rupiah($trx['kembalian'])) ?></strong>. Stok sudah diperbarui otomatis.</p>
        <?php else: ?>
            <a class="link back" href="<?= e(url('transaksi/riwayat.php')) ?>"><?= icon('arrow-left', 18) ?>Kembali ke riwayat</a>
            <h1>Struk transaksi</h1>
        <?php endif; ?>
    </div>
    <div class="head-actions">
        <button type="button" class="btn btn-ghost" data-print><?= icon('printer', 18) ?>Cetak struk</button>
        <a class="btn" href="<?= e(url('transaksi/baru.php')) ?>"><?= icon('plus', 18) ?>Transaksi baru</a>
    </div>
</div>

<article class="receipt" aria-label="Struk transaksi">
    <header class="receipt-head">
        <strong><?= e(STORE_NAME) ?></strong>
        <span><?= e(STORE_ADDRESS) ?></span>
    </header>
    <dl class="receipt-meta">
        <div><dt>No.</dt><dd>#<?= str_pad((string) $trx['id_transaksi'], 5, '0', STR_PAD_LEFT) ?></dd></div>
        <div><dt>Tanggal</dt><dd><?= e(tgl($trx['tanggal'])) ?></dd></div>
    </dl>
    <hr>
    <ul class="receipt-items">
        <?php foreach ($items as $it): ?>
            <li>
                <span class="ri-name"><?= e($it['nama']) ?></span>
                <span class="ri-calc"><?= (int) $it['jumlah_beli'] ?> × <?= e(number_format((float) $it['harga_satuan'], 0, ',', '.')) ?></span>
                <span class="ri-sub"><?= e(number_format((float) $it['subtotal'], 0, ',', '.')) ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
    <hr>
    <dl class="receipt-sum">
        <div><dt>Jumlah barang</dt><dd><?= (int) $jumlahBarang ?></dd></div>
        <div class="grand"><dt>Total</dt><dd><?= e(rupiah($trx['total_harga'])) ?></dd></div>
        <div><dt>Bayar</dt><dd><?= e(rupiah($trx['bayar'])) ?></dd></div>
        <div><dt>Kembalian</dt><dd><?= e(rupiah($trx['kembalian'])) ?></dd></div>
    </dl>
    <p class="receipt-thanks">Terima kasih sudah berbelanja!</p>
    <span class="receipt-edge" aria-hidden="true"></span>
</article>
<?php layout_end(); ?>
