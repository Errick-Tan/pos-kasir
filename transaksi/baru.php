<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_login();

try {
    $st = db()->prepare('SELECT kode, nama, harga, stok FROM produk ORDER BY nama ASC');
    $st->execute();
    $produk = $st->fetchAll();
} catch (PDOException $e) {
    error_log('[kasir] ' . $e->getMessage());
    http_response_code(500);
    exit('Terjadi kesalahan saat membaca produk.');
}

$json = json_encode(array_map(fn($p) => [
    'kode' => $p['kode'], 'nama' => $p['nama'], 'harga' => (int) round((float) $p['harga']), 'stok' => (int) $p['stok'],
], $produk), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);

layout_start('Kasir', 'kasir', 'wide');
?>
<div class="page-head">
    <div>
        <h1>Transaksi baru</h1>
        <p>Pilih produk untuk menambahkannya ke pesanan. Tekan <kbd>/</kbd> untuk mencari.</p>
    </div>
</div>

<div class="pos">
    <section class="pos-left" aria-label="Daftar produk">
        <div class="searchbar">
            <div class="search big">
                <?= icon('search', 22) ?>
                <input class="input" id="q" type="search" placeholder="Cari nama atau ketik kode lalu Enter" autocomplete="off" aria-label="Cari produk">
            </div>
        </div>

        <?php if (!$produk): ?>
            <div class="empty card">
                <p><strong>Belum ada produk</strong></p>
                <p class="muted">Tambahkan produk dulu agar bisa dijual.</p>
                <a class="btn" href="<?= e(url('produk/form.php')) ?>">Tambah produk</a>
            </div>
        <?php endif; ?>

        <div class="pgrid" id="pgrid">
            <?php foreach ($produk as $p): $habis = (int) $p['stok'] <= 0; ?>
                <button type="button" class="pcard" data-kode="<?= e($p['kode']) ?>" <?= $habis ? 'disabled' : '' ?>>
                    <span class="pcard-qty" aria-hidden="true"></span>
                    <span class="p-kode"><?= e($p['kode']) ?></span>
                    <span class="p-name"><?= e($p['nama']) ?></span>
                    <span class="p-price"><?= e(rupiah($p['harga'])) ?></span>
                    <span class="p-stok <?= $habis ? 'is-out' : ((int) $p['stok'] <= LOW_STOCK ? 'is-low' : '') ?>">
                        <?= $habis ? 'Stok habis' : 'Stok ' . (int) $p['stok'] ?>
                    </span>
                </button>
            <?php endforeach; ?>
        </div>
        <p class="empty" id="noResult" hidden><strong>Produk tidak ditemukan</strong><br><span class="muted">Coba kata kunci lain.</span></p>
    </section>

    <aside class="cart" id="cart" aria-label="Pesanan saat ini">
        <header class="cart-head">
            <h2>Pesanan <span class="count-pill" id="cCount">0</span></h2>
            <div class="cart-head-actions">
                <button type="button" class="btn btn-quiet btn-sm" id="btnClear" hidden><?= icon('trash', 16) ?>Kosongkan</button>
                <button type="button" class="icon-btn sheet-close" id="cartClose" aria-label="Tutup pesanan"><?= icon('x', 20) ?></button>
            </div>
        </header>

        <div class="cart-empty" id="cartEmpty">
            <span class="empty-ic"><?= icon('cart', 30) ?></span>
            <p><strong>Pesanan masih kosong</strong></p>
            <p class="muted">Pilih produk di sebelah kiri untuk memulai.</p>
        </div>
        <ul class="cart-list" id="cartList"></ul>

        <form class="cart-foot" id="formBayar" method="post" action="<?= e(url('transaksi/simpan.php')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="cart" id="cartJson">
            <input type="hidden" name="bayar" id="bayarHidden">

            <div class="total-row">
                <span>Total</span>
                <strong id="cTotal">Rp 0</strong>
            </div>

            <div class="field">
                <label for="bayar">Uang diterima</label>
                <div class="money">
                    <span>Rp</span>
                    <input class="input" id="bayar" inputmode="numeric" autocomplete="off" placeholder="0">
                </div>
                <div class="chips" id="chips"></div>
                <p class="pay-result" id="payResult" aria-live="polite"></p>
            </div>

            <button class="btn btn-block btn-lg" id="btnBayar" disabled><?= icon('check', 20) ?><span>Bayar &amp; cetak struk</span></button>
        </form>
    </aside>
</div>

<button type="button" class="cart-bar" id="cartToggle" aria-controls="cart">
    <span class="cart-bar-l"><?= icon('cart', 20) ?><span id="barCount">0</span> item</span>
    <strong id="barTotal">Rp 0</strong>
    <span class="cart-bar-r">Lihat pesanan</span>
</button>
<div class="sheet-backdrop" id="backdrop"></div>

<script type="application/json" id="produk-data"><?= $json ?></script>
<?php layout_end(['assets/js/kasir.js']); ?>
