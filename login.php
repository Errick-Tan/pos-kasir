<?php
require __DIR__ . '/includes/bootstrap.php';

if (auth_check()) {
    redirect('index.php');
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    try {
        if ($username === '' || $password === '') {
            $error = 'Isi nama pengguna dan kata sandi.';
        } elseif (auth_login($username, $password)) {
            redirect('index.php');
        } else {
            usleep(400000); // memperlambat tebak-tebakan password
            $error = 'Nama pengguna atau kata sandi salah.';
        }
    } catch (PDOException $e) {
        error_log('[login] ' . $e->getMessage());
        $error = 'Terjadi gangguan pada database. Coba lagi sebentar.';
    }
}

layout_head('Masuk', 'login-page');
?>
<div class="login-theme"><?= theme_toggle() ?></div>
<main class="login">
    <section class="login-card">
        <?= brand_logo('big') ?>
        <h1>Selamat datang di <?= e(STORE_NAME) ?></h1>
        <p class="muted">Masuk untuk mulai mencatat penjualan hari ini.</p>

        <?php if ($error): ?>
            <p class="alert" role="alert"><?= icon('alert', 18) ?><span><?= e($error) ?></span></p>
        <?php endif; ?>

        <form method="post" class="stack" autocomplete="on">
            <?= csrf_field() ?>
            <div class="field">
                <label for="username">Nama pengguna</label>
                <input class="input" id="username" name="username" value="<?= e($username) ?>" autocomplete="username" required autofocus>
            </div>
            <div class="field">
                <label for="password">Kata sandi</label>
                <input class="input" id="password" name="password" type="password" autocomplete="current-password" required>
            </div>
            <button class="btn btn-block">Masuk</button>
        </form>
    </section>
</main>
<?php layout_toasts(); ?>
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
</body>
</html>
