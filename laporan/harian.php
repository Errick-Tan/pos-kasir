<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_login();

$validDate = fn(string $d): bool => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && checkdate((int) substr($d, 5, 2), (int) substr($d, 8, 2), (int) substr($d, 0, 4));

$sampai = $validDate((string) ($_GET['sampai'] ?? '')) ? $_GET['sampai'] : date('Y-m-d');
$dari   = $validDate((string) ($_GET['dari'] ?? '')) ? $_GET['dari'] : date('Y-m-01');
if ($dari > $sampai) {
    [$dari, $sampai] = [$sampai, $dari];
}
// Batasi rentang maksimal 1 tahun agar halaman tetap ringan
if (strtotime($sampai) - strtotime($dari) > 365 * 86400) {
    $dari = date('Y-m-d', strtotime($sampai . ' -365 days'));
}
$batasAwal  = $dari . ' 00:00:00';
$batasAkhir = date('Y-m-d', strtotime($sampai . ' +1 day')) . ' 00:00:00';

try {
    $pdo = db();

    // Laporan harian: JOIN + GROUP BY tanggal + fungsi agregasi (COUNT, SUM)
    $st = $pdo->prepare(
        'SELECT DATE(t.tanggal) AS hari,
                COUNT(DISTINCT t.id_transaksi) AS jml_transaksi,
                SUM(d.jumlah_beli)             AS item_terjual,
                SUM(d.subtotal)                AS pendapatan
         FROM transaksi t
         JOIN detail_transaksi d ON d.id_transaksi = t.id_transaksi
         WHERE t.tanggal >= :a AND t.tanggal < :b
         GROUP BY DATE(t.tanggal)
         ORDER BY hari DESC'
    );
    $st->execute([':a' => $batasAwal, ':b' => $batasAkhir]);
    $harian = $st->fetchAll();

    // Produk terlaris pada rentang yang sama
    $st = $pdo->prepare(
        'SELECT p.kode, p.nama, SUM(d.jumlah_beli) AS terjual, SUM(d.subtotal) AS pendapatan
         FROM detail_transaksi d
         JOIN produk p    ON p.kode = d.kode_produk
         JOIN transaksi t ON t.id_transaksi = d.id_transaksi
         WHERE t.tanggal >= :a AND t.tanggal < :b
         GROUP BY p.kode, p.nama
         ORDER BY terjual DESC, p.nama
         LIMIT 5'
    );
    $st->execute([':a' => $batasAwal, ':b' => $batasAkhir]);
    $terlaris = $st->fetchAll();
} catch (PDOException $e) {
    error_log('[laporan] ' . $e->getMessage());
    http_response_code(500);
    exit('Terjadi kesalahan saat membuat laporan.');
}

$totalPendapatan = array_sum(array_map('floatval', array_column($harian, 'pendapatan')));
$totalTransaksi  = array_sum(array_column($harian, 'jml_transaksi'));
$totalItem       = array_sum(array_column($harian, 'item_terjual'));
$rata            = $totalTransaksi ? $totalPendapatan / $totalTransaksi : 0;
$maks            = $harian ? max(array_map('floatval', array_column($harian, 'pendapatan'))) : 0;
$grafik          = array_reverse($harian); // kronologis untuk grafik

$preset = [
    '7 hari terakhir' => [date('Y-m-d', strtotime('-6 days')), date('Y-m-d')],
    'Bulan ini'       => [date('Y-m-01'), date('Y-m-d')],
    'Hari ini'        => [date('Y-m-d'), date('Y-m-d')],
];

layout_start('Laporan penjualan', 'laporan');
?>
<div class="page-head">
    <div>
        <h1>Laporan penjualan Bulanan</h1>
        <p><?= e(tgl($dari, false)) ?> – <?= e(tgl($sampai, false)) ?></p>
    </div>
    <button type="button" class="btn btn-ghost no-print" data-print><?= icon('printer', 18) ?>Cetak laporan</button>
</div>

<form class="filters no-print" method="get" data-autosubmit>
    <label class="inline-field">Dari <input class="input" type="date" name="dari" value="<?= e($dari) ?>" max="<?= e(date('Y-m-d')) ?>"></label>
    <label class="inline-field">Sampai <input class="input" type="date" name="sampai" value="<?= e($sampai) ?>" max="<?= e(date('Y-m-d')) ?>"></label>
    <span class="presets">
        <?php foreach ($preset as $label => [$a, $b]): ?>
            <a class="chip<?= $a === $dari && $b === $sampai ? ' is-active' : '' ?>" href="?dari=<?= e($a) ?>&amp;sampai=<?= e($b) ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </span>
</form>

<section class="stats">
    <div class="stat stat-main"><span class="stat-label">Total pendapatan</span><strong class="stat-value"><?= e(rupiah($totalPendapatan)) ?></strong></div>
    <div class="stat"><span class="stat-label">Jumlah transaksi</span><strong class="stat-value"><?= (int) $totalTransaksi ?></strong></div>
    <div class="stat"><span class="stat-label">Barang terjual</span><strong class="stat-value"><?= (int) $totalItem ?></strong></div>
    <div class="stat"><span class="stat-label">Rata-rata per transaksi</span><strong class="stat-value"><?= e(rupiah($rata)) ?></strong></div>
</section>

<?php if (!$harian): ?>
    <section class="card empty"><p><strong>Belum ada penjualan di rentang ini</strong></p><p class="muted">Pilih rentang tanggal lain.</p></section>
<?php else: ?>
    <section class="card">
        <div class="card-head"><h2>Pendapatan per hari</h2></div>
        <div class="chart" role="img" aria-label="Grafik batang pendapatan per hari">
            <?php foreach ($grafik as $i => $h): $persen = $maks > 0 ? max(3, round((float) $h['pendapatan'] / $maks * 100)) : 0; ?>
                <div class="bar" title="<?= e(tgl($h['hari'], false) . ': ' . rupiah($h['pendapatan'])) ?>">
                    <div class="bar-track"><div class="bar-fill" style="--h:<?= (int) $persen ?>%; animation-delay:<?= $i * 40 ?>ms"></div></div>
                    <span class="bar-label"><?= e(date('j/n', strtotime($h['hari']))) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="two-col">
        <section class="card flush">
            <div class="card-head pad"><h2>Rincian per hari</h2></div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Tanggal</th><th class="num">Transaksi</th><th class="num">Barang</th><th class="num">Pendapatan</th></tr></thead>
                    <tbody>
                    <?php foreach ($harian as $h): ?>
                        <tr>
                            <td data-label="Tanggal"><?= e(tgl($h['hari'], false)) ?></td>
                            <td data-label="Transaksi" class="num"><?= (int) $h['jml_transaksi'] ?></td>
                            <td data-label="Barang" class="num"><?= (int) $h['item_terjual'] ?></td>
                            <td data-label="Pendapatan" class="num"><strong><?= e(rupiah($h['pendapatan'])) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr><td>Total</td><td class="num"><?= (int) $totalTransaksi ?></td><td class="num"><?= (int) $totalItem ?></td><td class="num"><?= e(rupiah($totalPendapatan)) ?></td></tr>
                    </tfoot>
                </table>
            </div>
        </section>

        <section class="card">
            <div class="card-head"><h2>Produk terlaris</h2></div>
            <ol class="rank">
                <?php foreach ($terlaris as $p): ?>
                    <li>
                        <span><strong><?= e($p['nama']) ?></strong><small class="muted"><?= e($p['kode']) ?></small></span>
                        <span class="rank-val"><strong><?= (int) $p['terjual'] ?> terjual</strong><small class="muted"><?= e(rupiah($p['pendapatan'])) ?></small></span>
                    </li>
                <?php endforeach; ?>
            </ol>
        </section>
    </div>
<?php endif; ?>
<?php layout_end(); ?>
