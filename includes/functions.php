<?php
require_once __DIR__ . '/config.php';
function e($v) : string {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}
function url(string $p = '') : string {
    return BASE_URL . '/' . ltrim($p, '/');
}
function redirect(string $p) : never {
    header('Location: ' . url($p));
    exit;
}
/* ---------- flash messages ---------- */
function flash(string $msg = '', string $type = 'ok') {
    if ($msg !== '') {
        $_SESSION['flash'] = [$msg, $type];
        return;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}
/* ---------- CSRF ---------- */
function csrf_field() : string {
    $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
    return '<input type="hidden" name="csrf" value="' . $_SESSION['csrf'] . '">';
}
function csrf_check() : void {
    if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? '-')) {
        http_response_code(419);
        exit('Session expired. Go back and try again.');
    }
}
/* ---------- auth: members (recipient / donor) ---------- */
function user() : ? array {
    return $_SESSION['user'] ?? null;
}
function require_user( ? string $role = null) : array {
    $u = user();
    if (!$u) {
        flash('Please log in to continue.', 'err');
        redirect('login.php');
    }
    $s = db()->prepare('SELECT status FROM users WHERE id=?');
    $s->execute([$u['id']]);
    if ($s->fetchColumn() !== 'active') {
        unset($_SESSION['user']);
        redirect('login.php');
    }
    if ($role && $u['role'] !== $role) redirect($u['role'] . '/');
    return $u;
}
/* ---------- auth: administrators (separate DB + session key) ---------- */
function admin() : ? array {
    return $_SESSION['admin'] ?? null;
}
function require_admin() : array {
    if (!admin()) redirect('login.php');
    $s = db('admin')->prepare('SELECT id FROM admins WHERE id=?');
    $s->execute([admin()['id']]);
    if (!$s->fetchColumn()) {
        unset($_SESSION['admin']);
        redirect('login.php');
    }
    return admin();
}
function admin_log(string $action) : void {
    db('admin')->prepare('INSERT INTO activity_log (admin_id, action) VALUES (?,?)')->execute([admin()['id'], $action]);
}
/* ---------- notifications ---------- */
function notify(int $userId, string $msg, string $link = '') : void {
    db()->prepare('INSERT INTO notifications (user_id, message, link) VALUES (?,?,?)')->execute([$userId, $msg, $link]);
}
function unread_count(int $userId) : int {
    $s = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0');
    $s->execute([$userId]);
    return(int) $s->fetchColumn();
}
/* ---------- image upload (returns stored file name or null) ---------- */
function upload_image(string $field) : ? string {
    if (empty($_FILES[$field]['name']) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return null;
    if ($_FILES[$field]['size'] > 3 * 1024 * 1024) return null;
    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) || !@ getimagesize($_FILES[$field]['tmp_name'])) return null;
    $name = bin2hex(random_bytes(8)) . '.' . $ext;
    move_uploaded_file($_FILES[$field]['tmp_name'], __DIR__ . '/../uploads/items/' . $name);
    return $name;
}
/* ---------- small view helpers ---------- */
function badge(string $s) : string {
    return '<span class="badge b-' . e($s) . '">' . e(ucfirst($s)) . '</span>';
}
function ago(string $ts) : string {
    $d = time() - strtotime($ts);
    return $d < 60 ? 'just now' : ($d < 3600 ? floor($d / 60) . ' min ago' : ($d < 86400 ? floor($d / 3600) . ' h ago' : date('d M Y', strtotime($ts))));
}
function initials(string $n) : string {
    $p = preg_split('/\s+/', trim($n));
    return strtoupper(substr($p[0], 0, 1) . (isset($p[1]) ? substr($p[1], 0, 1) : ''));
}
/* Item photo: shows uploaded photo, or a tidy placeholder until you add one */
function cover( ? string $img, string $cls = '') : string {
    if ($img) return '<div class="cover ' . $cls . '"><img src="' . url('uploads/items/' . e($img)) . '" alt="" loading="lazy"></div>';
    return '<div class="cover ph ' . $cls . '"><svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 5a2 2 0 0 1 2-2h12v16H6a2 2 0 0 0-2 2V5Z"/><path d="M8 7h6M8 11h4"/></svg></div>';
}
function item_card(array $b, string $action = '') : string {
    $kind = $b['item_type'] === 'book' ? 'Book' : 'Education item';
    return '<article class="bk">' . cover($b['image']) . '<div class="bk-body"><div class="bk-tags">' . badge($b['type']) . '<span class="cond">' . e($b['condition_status']) . ' · qty ' . (int) $b['quantity'] . '</span></div>' . '<span class="kind">' . $kind . '</span>' . '<p class="small muted">' . e(($b['subject'] ?? '') . ' · ' . ($b['education_level'] ?? '') . ' · ' . ($b['location'] ?? '')) . '</p>' . '<h3>' . e($b['title']) . '</h3><p class="muted">' . e($b['brand']) . '</p>' . '<p class="small muted">' . e($b['category'] ?? '') . ' · by ' . e($b['donor'] ?? '') . '</p>' . $action . '</div></article>';
}
require_once __DIR__ . '/extensions.php';
