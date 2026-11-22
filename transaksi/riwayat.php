<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_login();

$validDate = fn(string $d): bool => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && checkdate((int) substr($d, 5, 2), (int) substr($d, 8, 2), (int) substr($d, 0, 4));

$q      = trim((string) ($_GET['q'] ?? ''));
$dari   = $validDate((string) ($_GET['dari'] ?? '')) ? $_GET['dari'] : '';
$sampai = $validDate((string) ($_GET['sampai'] ?? '')) ? $_GET['sampai'] : '';
$page   = (int) ($_GET['page'] ?? 1);

$where = [];
$params = [];
if ($q !== '' && ctype_digit(ltrim($q, '#'))) {
    $where[] = 't.id_transaksi = :id';
    $params[':id'] = (int) ltrim($q, '#');
}
if ($dari !== '') {
    $where[] = 't.tanggal >= :dari';
    $params[':dari'] = $dari . ' 00:00:00';
}
if ($sampai !== '') {
    $where[] = 't.tanggal < :sampai';
    $params[':sampai'] = date('Y-m-d', strtotime($sampai . ' +1 day')) . ' 00:00:00';
}
$sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    $pdo = db();
    $st = $pdo->prepare("SELECT COUNT(*) FROM transaksi t $sqlWhere");
    $st->execute($params);
    $pg = paginate((int) $st->fetchColumn(), $page, 10);

    // JOIN + GROUP BY: jumlah jenis & jumlah barang tiap transaksi
    $st = $pdo->prepare(
        "SELECT t.id_transaksi, t.tanggal, t.total_harga,
                COUNT(d.id_detail) AS jenis, COALESCE(SUM(d.jumlah_beli), 0) AS qty
         FROM transaksi t LEFT JOIN detail_transaksi d ON d.id_transaksi = t.id_transaksi
         $sqlWhere
         GROUP BY t.id_transaksi, t.tanggal, t.total_harga
         ORDER BY t.tanggal DESC, t.id_transaksi DESC
         LIMIT :lim OFFSET :off"
    );
    foreach ($params as $k => $v) {
        $st->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $st->bindValue(':lim', $pg['perPage'], PDO::PARAM_INT);
    $st->bindValue(':off', $pg['offset'], PDO::PARAM_INT);
    $st->execute();
    $rows = $st->fetchAll();
} catch (PDOException $e) {
    error_log('[riwayat] ' . $e->getMessage());
    http_response_code(500);
    exit('Terjadi kesalahan saat membaca riwayat.');
}

layout_start('Riwayat transaksi', 'riwayat');
?>
<div class="page-head">
    <div>
        <h1>Riwayat transaksi</h1>
        <p><?= (int) $pg['total'] ?> transaksi ditemukan.</p>
    </div>
</div>

<section class="card flush">
    <form class="toolbar" method="get" data-autosubmit>
        <div class="search">
            <?= icon('search', 20) ?>
            <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="Cari nomor transaksi" aria-label="Cari nomor transaksi" data-refocus>
        </div>
        <label class="inline-field">Dari <input class="input" type="date" name="dari" value="<?= e($dari) ?>"></label>
        <label class="inline-field">Sampai <input class="input" type="date" name="sampai" value="<?= e($sampai) ?>"></label>
    </form>

    <?php if (!$rows): ?>
        <div class="empty"><p><strong>Tidak ada transaksi</strong></p><p class="muted">Ubah filter tanggal atau nomor transaksi.</p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>No.</th><th>Waktu</th><th class="num">Barang</th><th class="num">Total</th><th><span class="sr-only">Aksi</span></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td data-label="No."><strong>#<?= str_pad((string) $r['id_transaksi'], 5, '0', STR_PAD_LEFT) ?></strong></td>
                        <td data-label="Waktu"><?= e(tgl($r['tanggal'])) ?></td>
                        <td data-label="Barang" class="num"><?= (int) $r['qty'] ?> (<?= (int) $r['jenis'] ?> jenis)</td>
                        <td data-label="Total" class="num"><strong><?= e(rupiah($r['total_harga'])) ?></strong></td>
                        <td class="actions"><a class="btn btn-ghost btn-sm" href="<?= e(url('transaksi/struk.php?id=' . (int) $r['id_transaksi'])) ?>">Lihat struk</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="table-foot">
            <span class="muted">Halaman <?= $pg['page'] ?> dari <?= $pg['pages'] ?></span>
            <?= pagination_html($pg, ['q' => $q, 'dari' => $dari, 'sampai' => $sampai], 'transaksi/riwayat.php') ?>
        </div>
    <?php endif; ?>
</section>
<?php layout_end(); ?>
