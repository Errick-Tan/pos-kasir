<?php
declare(strict_types=1);

function auth_check(): bool
{
    return isset($_SESSION['admin_id']);
}

function current_admin(): array
{
    return ['id' => $_SESSION['admin_id'] ?? 0, 'nama' => $_SESSION['admin_nama'] ?? ''];
}

/** Wajib dipanggil di halaman yang butuh login. Juga menangani sesi idle. */
function require_login(): void
{
    if (!auth_check()) {
        redirect('login.php');
    }
    if (time() - (int) ($_SESSION['last_seen'] ?? 0) > IDLE_TIMEOUT) {
        auth_logout();
        session_start();
        flash('info', 'Sesi berakhir karena tidak ada aktivitas. Silakan masuk lagi.');
        redirect('login.php');
    }
    $_SESSION['last_seen'] = time();
}

function auth_login(string $username, string $password): bool
{
    $stmt = db()->prepare('SELECT id_admin, password_hash, nama_lengkap FROM admin WHERE username = :u LIMIT 1');
    $stmt->execute([':u' => $username]);
    $row = $stmt->fetch();

    // Hash tiruan agar waktu respons tetap sama walau username tidak ada.
    $hash = $row['password_hash'] ?? '$2y$10$usesomesillystringforsalt.invalidhashxxxxxxxxxxxxxxxxxxxxxxx';
    $ok   = password_verify($password, $hash);

    if (!$row || !$ok) {
        return false;
    }

    // Cegah session fixation
    session_regenerate_id(true);
    $_SESSION['admin_id']   = (int) $row['id_admin'];
    $_SESSION['admin_nama'] = $row['nama_lengkap'];
    $_SESSION['last_seen']  = time();

    // Perbarui hash jika algoritma default PHP sudah berganti
    if (password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT)) {
        $up = db()->prepare('UPDATE admin SET password_hash = :h WHERE id_admin = :id');
        $up->execute([':h' => password_hash($password, PASSWORD_DEFAULT), ':id' => $row['id_admin']]);
    }
    return true;
}

function auth_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
