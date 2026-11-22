<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_login();

$q    = trim((string) ($_GET['q'] ?? ''));
$page = (int) ($_GET['page'] ?? 1);

try {
    $pdo = db();

    // Pencarian: kode ATAU nama. Karakter wildcard dari pengguna di-escape.
    $where  = '';
    $params = [];
    if ($q !== '') {
        $like   = '%' . addcslashes($q, '%_\\') . '%';
        $where  = 'WHERE kode LIKE :q1 OR nama LIKE :q2';  // nama placeholder harus unik (emulate prepares = off)
        $params = [':q1' => $like, ':q2' => $like];
    }

    $st = $pdo->prepare("SELECT COUNT(*) FROM produk $where");
    $st->execute($params);
    $pg = paginate((int) $st->fetchColumn(), $page);

    $st = $pdo->prepare("SELECT kode, nama, harga, stok FROM produk $where ORDER BY nama ASC LIMIT :lim OFFSET :off");
    foreach ($params as $k => $v) {
        $st->bindValue($k, $v);
    }
    $st->bindValue(':lim', $pg['perPage'], PDO::PARAM_INT);
    $st->bindValue(':off', $pg['offset'], PDO::PARAM_INT);
    $st->execute();
    $rows = $st->fetchAll();
} catch (PDOException $e) {
    error_log('[produk.index] ' . $e->getMessage());
    http_response_code(500);
    exit('Terjadi kesalahan saat membaca data produk.');
}

layout_start('Produk', 'produk');
?>
<div class="page-head">
    <div>
        <h1>Produk</h1>
        <p><?= (int) $pg['total'] ?> produk<?= $q !== '' ? ' cocok dengan “' . e($q) . '”' : ' di toko' ?>.</p>
    </div>
    <a class="btn" href="<?= e(url('produk/form.php')) ?>"><?= icon('plus', 18) ?>Tambah produk</a>
</div>

<section class="card flush">
    <form class="toolbar" method="get" data-autosubmit>
        <div class="search">
            <?= icon('search', 20) ?>
            <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="Cari kode atau nama produk" aria-label="Cari produk" data-refocus>
        </div>
    </form>

    <?php if (!$rows): ?>
        <div class="empty">
            <p><strong><?= $q !== '' ? 'Produk tidak ditemukan' : 'Belum ada produk' ?></strong></p>
            <p class="muted"><?= $q !== '' ? 'Coba kata kunci lain atau hapus pencarian.' : 'Tambahkan produk pertama agar bisa dijual di kasir.' ?></p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr><th>Kode</th><th>Nama produk</th><th class="num">Harga</th><th class="num">Stok</th><th><span class="sr-only">Aksi</span></th></tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): [$lbl, $cls] = status_stok((int) $r['stok']); ?>
                    <tr>
                        <td data-label="Kode"><code><?= e($r['kode']) ?></code></td>
                        <td data-label="Nama"><strong><?= e($r['nama']) ?></strong></td>
                        <td data-label="Harga" class="num"><?= e(rupiah($r['harga'])) ?></td>
                        <td data-label="Stok" class="num">
                            <span class="badge badge-<?= $cls ?>"><?= (int) $r['stok'] ?> · <?= $lbl ?></span>
                        </td>
                        <td class="actions">
                            <a class="icon-btn" href="<?= e(url('produk/form.php?kode=' . rawurlencode($r['kode']))) ?>" aria-label="Ubah <?= e($r['nama']) ?>" title="Ubah"><?= icon('edit', 18) ?></a>
                            <button type="button" class="icon-btn danger" data-delete
                                    data-kode="<?= e($r['kode']) ?>" data-nama="<?= e($r['nama']) ?>"
                                    aria-label="Hapus <?= e($r['nama']) ?>" title="Hapus"><?= icon('trash', 18) ?></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="table-foot">
            <span class="muted">Menampilkan <?= $pg['offset'] + 1 ?>–<?= $pg['offset'] + count($rows) ?> dari <?= (int) $pg['total'] ?></span>
            <?= pagination_html($pg, ['q' => $q], 'produk/index.php') ?>
        </div>
    <?php endif; ?>
</section>

<dialog id="dlg-hapus" aria-labelledby="dlg-title">
    <form method="post" action="<?= e(url('produk/hapus.php')) ?>" class="stack">
        <?= csrf_field() ?>
        <input type="hidden" name="kode" id="hapus-kode">
        <input type="hidden" name="back" value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
        <h2 id="dlg-title">Hapus produk ini?</h2>
        <p><strong id="hapus-nama"></strong> akan dihapus dari daftar produk. Tindakan ini tidak bisa dibatalkan.</p>
        <div class="dialog-actions">
            <button type="button" class="btn btn-ghost" data-close>Batal</button>
            <button class="btn btn-danger">Ya, hapus</button>
        </div>
    </form>
</dialog>
<?php layout_end(); ?>
