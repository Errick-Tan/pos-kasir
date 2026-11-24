<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function rupiah(int|float|string $n): string
{
    return 'Rp ' . number_format((float) $n, 0, ',', '.');
}

function tgl(string $datetime, bool $withTime = true): string
{
    static $bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $t = strtotime($datetime);
    if ($t === false) {
        return $datetime;
    }
    $out = date('j', $t) . ' ' . $bulan[(int) date('n', $t)] . ' ' . date('Y', $t);
    return $withTime ? $out . ', ' . date('H:i', $t) : $out;
}

/* ---------- Flash message ---------- */

function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function flash_pull(): array
{
    $items = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $items;
}

/* ---------- CSRF ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        exit('Metode tidak diizinkan.');
    }
    $sent = (string) ($_POST['csrf'] ?? '');
    if (!hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(419);
        exit('Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.');
    }
}

/* ---------- Pagination ---------- */

function paginate(int $total, int $page, int $perPage = PER_PAGE): array
{
    $pages = max(1, (int) ceil($total / $perPage));
    $page  = min(max(1, $page), $pages);
    return [
        'total'   => $total,
        'page'    => $page,
        'pages'   => $pages,
        'perPage' => $perPage,
        'offset'  => ($page - 1) * $perPage,
    ];
}

function pagination_html(array $pg, array $query, string $path): string
{
    if ($pg['pages'] <= 1) {
        return '';
    }
    $link = function (int $p, string $label, bool $active = false, bool $disabled = false, string $aria = '') use ($query, $path): string {
        if ($disabled) {
            return '<span class="pg is-disabled">' . $label . '</span>';
        }
        $q = http_build_query(array_filter($query + ['page' => $p], fn($v) => $v !== '' && $v !== null));
        return '<a class="pg' . ($active ? ' is-active' : '') . '" href="' . e(url($path) . '?' . $q) . '"'
            . ($active ? ' aria-current="page"' : '') . ($aria ? ' aria-label="' . e($aria) . '"' : '') . '>' . $label . '</a>';
    };

    $html = '<nav class="pagination" aria-label="Halaman">';
    $html .= $link($pg['page'] - 1, icon('chevron-left', 18), false, $pg['page'] <= 1, 'Sebelumnya');

    $last = 0;
    for ($i = 1; $i <= $pg['pages']; $i++) {
        if ($i === 1 || $i === $pg['pages'] || abs($i - $pg['page']) <= 1) {
            if ($last && $i - $last > 1) {
                $html .= '<span class="pg is-gap">…</span>';
            }
            $html .= $link($i, (string) $i, $i === $pg['page']);
            $last = $i;
        }
    }

    $html .= $link($pg['page'] + 1, icon('chevron-right', 18), false, $pg['page'] >= $pg['pages'], 'Berikutnya');
    return $html . '</nav>';
}

/* ---------- UI kecil ---------- */

function status_stok(int $stok): array
{
    if ($stok <= 0) {
        return ['Habis', 'out'];
    }
    if ($stok <= LOW_STOCK) {
        return ['Menipis', 'low'];
    }
    return ['Tersedia', 'ok'];
}

function icon(string $name, int $size = 20): string
{
    static $paths = [
        'home'          => '<path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
        'cart'          => '<circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.7 12.4a1 1 0 0 0 1 .8h8.9a1 1 0 0 0 1-.8L20 7H6"/>',
        'box'           => '<path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="m3 8 9 5 9-5M12 13v8"/>',
        'receipt'       => '<path d="M5 3h14v18l-3-2-2 2-2-2-2 2-2-2-3 2z"/><path d="M9 8h6M9 12h6"/>',
        'chart'         => '<path d="M4 20V4M4 20h16M8 16v-5M12 16V8M16 16v-3"/>',
        'logout'        => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
        'plus'          => '<path d="M12 5v14M5 12h14"/>',
        'minus'         => '<path d="M5 12h14"/>',
        'search'        => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'trash'         => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
        'edit'          => '<path d="M4 20h4L19 9a2.8 2.8 0 0 0-4-4L4 16z"/>',
        'x'             => '<path d="M6 6l12 12M18 6 6 18"/>',
        'check'         => '<path d="m5 12 5 5 9-10"/>',
        'printer'       => '<path d="M7 9V3h10v6M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2"/><path d="M7 14h10v7H7z"/>',
        'chevron-left'  => '<path d="m15 6-6 6 6 6"/>',
        'chevron-right' => '<path d="m9 6 6 6-6 6"/>',
        'alert'         => '<path d="M12 4 2.5 20h19z"/><path d="M12 10v4M12 17.5v.01"/>',
        'arrow-left'    => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
        'sun'           => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'moon'          => '<path d="M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5z"/>',
        'store'         => '<path d="M4 9 5.5 4h13L20 9M4 9v11h16V9M4 9c0 1.7 1.3 3 3 3s3-1.3 3-3c0 1.7 1.3 3 3 3s3-1.3 3-3c0 1.7 1.3 3 3 3"/>',
    ];
    return '<svg class="ic" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
        . 'stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? '') . '</svg>';
}
