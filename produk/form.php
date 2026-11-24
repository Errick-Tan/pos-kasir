<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_login();

$kodeAsli = trim((string) ($_GET['kode'] ?? $_POST['kode_asli'] ?? ''));
$edit     = $kodeAsli !== '';
$errors   = [];
$data     = ['kode' => '', 'nama' => '', 'harga' => '', 'stok' => ''];

try {
    $pdo = db();

    if ($edit) {
        $st = $pdo->prepare('SELECT kode, nama, harga, stok FROM produk WHERE kode = :k');
        $st->execute([':k' => $kodeAsli]);
        $row = $st->fetch();
        if (!$row) {
            flash('error', 'Produk tidak ditemukan.');
            redirect('produk/index.php');
        }
        $data = ['kode' => $row['kode'], 'nama' => $row['nama'], 'harga' => (string) (int) $row['harga'], 'stok' => (string) $row['stok']];
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_post();

        $data['kode']  = $edit ? $kodeAsli : strtoupper(trim((string) ($_POST['kode'] ?? '')));
        $data['nama']  = trim((string) ($_POST['nama'] ?? ''));
        $data['harga'] = preg_replace('/\D/', '', (string) ($_POST['harga'] ?? ''));
        $data['stok']  = trim((string) ($_POST['stok'] ?? ''));

        
        if (!preg_match('/^[A-Z0-9_-]{1,20}$/', $data['kode'])) {
            $errors['kode'] = 'Kode 1–20 karakter: huruf, angka, strip (-), atau garis bawah (_).';
        }
        if (mb_strlen($data['nama']) < 2 || mb_strlen($data['nama']) > 100) {
            $errors['nama'] = 'Nama produk 2–100 karakter.';
        }
        if ($data['harga'] === '' || (int) $data['harga'] > 99999999) {
            $errors['harga'] = 'Isi harga dengan angka (maksimal Rp 99.999.999).';
        }
        if (!ctype_digit($data['stok']) || (int) $data['stok'] > 1000000) {
            $errors['stok'] = 'Stok harus bilangan bulat 0 atau lebih.';
        }

        if (!$errors) {
            try {
                if ($edit) {
                    $st = $pdo->prepare('UPDATE produk SET nama = :n, harga = :h, stok = :s WHERE kode = :k');
                } else {
                    $st = $pdo->prepare('INSERT INTO produk (kode, nama, harga, stok) VALUES (:k, :n, :h, :s)');
                }
                $st->execute([':k' => $data['kode'], ':n' => $data['nama'], ':h' => (int) $data['harga'], ':s' => (int) $data['stok']]);

                flash('ok', $edit ? 'Perubahan produk disimpan.' : 'Produk baru ditambahkan.');
                redirect('produk/index.php');
            } catch (PDOException $e) {
                if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                    $errors['kode'] = 'Kode sudah dipakai produk lain.';
                } else {
                    throw $e;
                }
            }
        }
    }
} catch (PDOException $e) {
    error_log('[produk.form] ' . $e->getMessage());
    http_response_code(500);
    exit('Terjadi kesalahan pada database.');
}

layout_start($edit ? 'Ubah produk' : 'Tambah produk', 'produk');
?>
<div class="page-head">
    <div>
        <a class="link back" href="<?= e(url('produk/index.php')) ?>"><?= icon('arrow-left', 18) ?>Kembali ke produk</a>
        <h1><?= $edit ? 'Ubah produk' : 'Tambah produk' ?></h1>
    </div>
</div>

<section class="card form-card">
    <form method="post" class="stack" novalidate>
        <?= csrf_field() ?>
        <?php if ($edit): ?><input type="hidden" name="kode_asli" value="<?= e($kodeAsli) ?>"><?php endif; ?>

        <div class="field">
            <label for="kode">Kode produk</label>
            <input class="input" id="kode" name="kode" value="<?= e($data['kode']) ?>" maxlength="20"
                   placeholder="contoh: BRG016" <?= $edit ? 'readonly' : 'required autofocus' ?>
                   <?= isset($errors['kode']) ? 'aria-invalid="true"' : '' ?>>
            <?php if (isset($errors['kode'])): ?><span class="err"><?= e($errors['kode']) ?></span>
            <?php elseif ($edit): ?><span class="hint">Kode tidak bisa diubah karena sudah menjadi acuan transaksi.</span><?php endif; ?>
        </div>

        <div class="field">
            <label for="nama">Nama produk</label>
            <input class="input" id="nama" name="nama" value="<?= e($data['nama']) ?>" maxlength="100" required
                   <?= $edit ? 'autofocus' : '' ?> <?= isset($errors['nama']) ? 'aria-invalid="true"' : '' ?>>
            <?php if (isset($errors['nama'])): ?><span class="err"><?= e($errors['nama']) ?></span><?php endif; ?>
        </div>

        <div class="grid-2">
            <div class="field">
                <label for="harga">Harga satuan (Rp)</label>
                <input class="input" id="harga" name="harga" inputmode="numeric" value="<?= e($data['harga']) ?>" required
                       <?= isset($errors['harga']) ? 'aria-invalid="true"' : '' ?>>
                <?php if (isset($errors['harga'])): ?><span class="err"><?= e($errors['harga']) ?></span><?php endif; ?>
            </div>
            <div class="field">
                <label for="stok">Stok</label>
                <input class="input" id="stok" name="stok" inputmode="numeric" value="<?= e($data['stok']) ?>" required
                       <?= isset($errors['stok']) ? 'aria-invalid="true"' : '' ?>>
                <?php if (isset($errors['stok'])): ?><span class="err"><?= e($errors['stok']) ?></span><?php endif; ?>
            </div>
        </div>

        <div class="form-actions">
            <a class="btn btn-ghost" href="<?= e(url('produk/index.php')) ?>">Batal</a>
            <button class="btn"><?= $edit ? 'Simpan perubahan' : 'Simpan produk' ?></button>
        </div>
    </form>
</section>
<?php layout_end(); ?>
