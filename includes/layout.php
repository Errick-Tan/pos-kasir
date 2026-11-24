<?php
declare(strict_types=1);

function layout_head(string $title, string $bodyClass = '', string $bodyAttr = ''): void
{
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= e($title) ?> · <?= e(STORE_NAME) ?></title>
    <script>
        try {
            var t = localStorage.getItem('pos_theme');
            if (t !== 'dark' && t !== 'light') t = matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            document.documentElement.dataset.theme = t;
        } catch (e) {}
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>">
</head>
<body class="<?= e($bodyClass) ?>" <?= $bodyAttr ?>>
<?php
}

function brand_logo(string $extraClass = ''): string
{
    return '<img class="brand-logo' . ($extraClass !== '' ? ' ' . $extraClass : '') . '" src="' . e(url('assets/img/logo.png')) . '" alt="" width="58" height="38">';
}

function theme_toggle(): string
{
    return '<button type="button" class="icon-btn theme-toggle" data-theme-toggle aria-label="Ganti ke mode gelap" title="Ganti tema">'
        . str_replace('class="ic"', 'class="ic ic-moon"', icon('moon', 20)) . str_replace('class="ic"', 'class="ic ic-sun"', icon('sun', 20)) . '</button>';
}

function layout_toasts(): void
{
    ?>
<div id="toasts" class="toasts" role="status" aria-live="polite"></div>
<div id="flash-data" hidden data-flash="<?= e(json_encode(flash_pull(), JSON_UNESCAPED_UNICODE)) ?>"></div>
<?php
}

/**
 * Membuka halaman dengan sidebar (desktop) / navigasi bawah (mobile).
 * @param string $active  beranda | kasir | produk | riwayat | laporan
 */
function layout_start(string $title, string $active = '', string $mainClass = '', string $bodyAttr = ''): void
{
    $admin = current_admin();
    $nav = [
        'beranda' => ['Beranda', 'home',    'index.php'],
        'kasir'   => ['Kasir',   'cart',    'transaksi/baru.php'],
        'produk'  => ['Produk',  'box',     'produk/index.php'],
        'riwayat' => ['Riwayat', 'receipt', 'transaksi/riwayat.php'],
        'laporan' => ['Laporan', 'chart',   'laporan/harian.php'],
    ];
    layout_head($title, '', $bodyAttr);
    ?>
<div class="shell">
    <header class="topbar no-print">
        <span class="brand"><?= brand_logo() ?><?= e(STORE_NAME) ?></span>
        <div class="topbar-actions">
            <?= theme_toggle() ?>
            <form method="post" action="<?= e(url('logout.php')) ?>">
                <?= csrf_field() ?>
                <button class="icon-btn" aria-label="Keluar"><?= icon('logout', 20) ?></button>
            </form>
        </div>
    </header>

    <aside class="side no-print">
        <a class="brand" href="<?= e(url('index.php')) ?>">
            <?= brand_logo() ?><?= e(STORE_NAME) ?>
        </a>
        <nav class="nav" aria-label="Menu utama">
            <?php foreach ($nav as $key => [$label, $ic, $href]): ?>
                <a href="<?= e(url($href)) ?>" <?= $key === $active ? 'aria-current="page"' : '' ?>>
                    <?= icon($ic, 22) ?><span><?= e($label) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="side-foot">
            <div class="who">
                <span class="avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($admin['nama'], 0, 1))) ?></span>
                <span class="who-name"><?= e($admin['nama']) ?></span>
            </div>
            <div class="foot-actions">
                <?= theme_toggle() ?>
                <form method="post" action="<?= e(url('logout.php')) ?>">
                    <?= csrf_field() ?>
                    <button class="icon-btn" aria-label="Keluar" title="Keluar"><?= icon('logout', 20) ?></button>
                </form>
            </div>
        </div>
    </aside>

    <main class="main <?= e($mainClass) ?>">
<?php
}

function layout_end(array $scripts = []): void
{
    ?>
    </main>
</div>
<?php layout_toasts(); ?>
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
<?php foreach ($scripts as $s): ?>
<script src="<?= e(url($s)) ?>"></script>
<?php endforeach; ?>
</body>
</html>
<?php
}
