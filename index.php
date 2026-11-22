<?php
require __DIR__ . '/includes/bootstrap.php';
require_login();

$pdo = db();
$awalHari  = date('Y-m-d 00:00:00');
$akhirHari = date('Y-m-d 00:00:00', strtotime('+1 day'));
$awalBulan = date('Y-m-01 00:00:00');

try {
    // Agregasi: transaksi & pendapatan hari ini
    $st = $pdo->prepare('SELECT COUNT(*) AS jml, COALESCE(SUM(total_harga), 0) AS omzet
                         FROM transaksi WHERE tanggal >= :a AND tanggal < :b');
    $st->execute([':a' => $awalHari, ':b' => $akhirHari]);
    $hariIni = $st->fetch();

    // JOIN + agregasi: item terjual hari ini
    $st = $pdo->prepare('SELECT COALESCE(SUM(d.jumlah_beli), 0)
                         FROM detail_transaksi d JOIN transaksi t ON t.id_transaksi = d.id_transaksi
                         WHERE t.tanggal >= :a AND t.tanggal < :b');
    $st->execute([':a' => $awalHari, ':b' => $akhirHari]);
    $itemHariIni = (int) $st->fetchColumn();

    $st = $pdo->prepare('SELECT COALESCE(SUM(total_harga), 0) FROM transaksi WHERE tanggal >= :a');
    $st->execute([':a' => $awalBulan]);
    $omzetBulan = (float) $st->fetchColumn();

    $st = $pdo->prepare('SELECT kode, nama, stok FROM produk WHERE stok <= :batas ORDER BY stok, nama LIMIT 6');
    $st->bindValue(':batas', LOW_STOCK, PDO::PARAM_INT);
    $st->execute();
    $stokTipis = $st->fetchAll();

    $terbaru = $pdo->query('SELECT id_transaksi, tanggal, total_harga FROM transaksi ORDER BY tanggal DESC, id_transaksi DESC LIMIT 5')->fetchAll();
} catch (PDOException $e) {
    error_log('[beranda] ' . $e->getMessage());
    http_response_code(500);
    exit('Terjadi kesalahan saat membaca data.');
}

$jam = (int) date('G');
$sapa = $jam < 11 ? 'Selamat pagi' : ($jam < 15 ? 'Selamat siang' : ($jam < 19 ? 'Selamat sore' : 'Selamat malam'));

layout_start('Beranda', 'beranda');
?>
<div class="page-head">
    <div>
        <h1><?= e($sapa) ?>, <?= e(current_admin()['nama']) ?></h1>
        <p>Ringkasan penjualan <?= e(tgl(date('Y-m-d'), false)) ?>.</p>
    </div>
    <a class="btn" href="<?= e(url('transaksi/baru.php')) ?>"><?= icon('plus', 18) ?>Mulai transaksi</a>
</div>

<section class="stats">
    <div class="stat stat-main">
        <span class="stat-label">Pendapatan hari ini</span>
        <strong class="stat-value"><?= e(rupiah($hariIni['omzet'])) ?></strong>
    </div>
    <div class="stat">
        <span class="stat-label">Transaksi hari ini</span>
        <strong class="stat-value"><?= (int) $hariIni['jml'] ?></strong>
    </div>
    <div class="stat">
        <span class="stat-label">Barang terjual hari ini</span>
        <strong class="stat-value"><?= $itemHariIni ?></strong>
    </div>
    <div class="stat">
        <span class="stat-label">Pendapatan bulan ini</span>
        <strong class="stat-value"><?= e(rupiah($omzetBulan)) ?></strong>
    </div>
</section>

<div class="two-col">
    <section class="card">
        <div class="card-head">
            <h2>Stok perlu diisi</h2>
            <a class="link" href="<?= e(url('produk/index.php')) ?>">Kelola produk</a>
        </div>
        <?php if (!$stokTipis): ?>
            <p class="muted">Semua stok masih aman.</p>
        <?php else: ?>
            <ul class="plain-list">
                <?php foreach ($stokTipis as $p): [$lbl, $cls] = status_stok((int) $p['stok']); ?>
                    <li>
                        <span><strong><?= e($p['nama']) ?></strong><small class="muted"><?= e($p['kode']) ?></small></span>
                        <span class="badge badge-<?= $cls ?>"><?= (int) $p['stok'] === 0 ? 'Habis' : 'Sisa ' . (int) $p['stok'] ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="card">
        <div class="card-head">
            <h2>Transaksi terbaru</h2>
            <a class="link" href="<?= e(url('transaksi/riwayat.php')) ?>">Lihat semua</a>
        </div>
        <?php if (!$terbaru): ?>
            <p class="muted">Belum ada transaksi.</p>
        <?php else: ?>
            <ul class="plain-list">
                <?php foreach ($terbaru as $t): ?>
                    <li>
                        <a class="row-link" href="<?= e(url('transaksi/struk.php?id=' . (int) $t['id_transaksi'])) ?>">
                            <span><strong>#<?= str_pad((string) $t['id_transaksi'], 5, '0', STR_PAD_LEFT) ?></strong>
                                <small class="muted"><?= e(tgl($t['tanggal'])) ?></small></span>
                            <strong><?= e(rupiah($t['total_harga'])) ?></strong>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
<?php layout_end(); ?>
