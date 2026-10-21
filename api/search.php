<?php
/* AJAX endpoint: returns item cards (HTML) for the filters on recipient/items.php */
require_once __DIR__ . '/../includes/functions.php';
$u = user();
if ($u) require_user();
$sql = "SELECT b.*, c.name AS category, d.name AS donor FROM items b JOIN categories c ON c.id=b.category_id JOIN users d ON d.id=b.donor_id WHERE b.status='approved' AND d.status='active'";
$p = [];
if (($q = trim($_GET['q'] ?? '')) !== '') {
    $sql .= ' AND (b.title LIKE ? OR b.brand LIKE ?)';
    $p[] = "%$q%";
    $p[] = "%$q%";
}
if (!empty($_GET['category'])) {
    $sql .= ' AND b.category_id=?';
    $p[] = (int) $_GET['category'];
}
if (in_array($_GET['kind'] ?? '', ['book', 'education_item'])) {
    $sql .= ' AND b.item_type=?';
    $p[] = $_GET['kind'];
}
if (in_array($_GET['type'] ?? '', ['donate', 'exchange'])) {
    $sql .= ' AND b.type=?';
    $p[] = $_GET['type'];
}
if (in_array($_GET['cond'] ?? '', ['New', 'Good', 'Fair'])) {
    $sql .= ' AND b.condition_status=?';
    $p[] = $_GET['cond'];
}
foreach (['subject' => 'b.subject', 'education_level' => 'b.education_level', 'location' => 'b.location'] as $key => $col) if (trim($_GET[$key] ?? '') !== '') {
    $sql .= " AND $col LIKE ?";
    $p[] = '%' . trim($_GET[$key]) . '%';
}
if (!empty($_GET['availability_date'])) {
    $sql .= ' AND (b.availability_date IS NULL OR b.availability_date<=?)';
    $p[] = $_GET['availability_date'];
}
$s = db()->prepare($sql . ' ORDER BY b.created_at DESC LIMIT 60');
$s->execute($p);
$rows = $s->fetchAll();
if (!$rows) {
    echo '<div class="empty" style="grid-column:1/-1">No items match those filters yet. Try clearing a filter.</div>';
    exit;
}
$mine = [];
if (($u['role'] ?? '') === 'recipient') {
    $r = db()->prepare("SELECT item_id FROM requests WHERE recipient_id=? AND status IN ('pending','approved')");
    $r->execute([$u['id']]);
    $mine = $r->fetchAll(PDO::FETCH_COLUMN);
}
foreach ($rows as $b) {
    $radius = (float)($_GET['radius'] ?? 0);
    if ($radius > 0) {
        $me = null;
        if ($u) {
            $s = db()->prepare('SELECT latitude,longitude FROM users WHERE id=?');
            $s->execute([$u['id']]);
            $me = $s->fetch();
        }
        $km = distance($me['latitude'] ?? null, $me['longitude'] ?? null, $b['latitude'], $b['longitude']);
        if ($km === null || $km > $radius) continue;
    }
    $qty = available($b);
    if ($qty < max(1, (int)($_GET['quantity'] ?? 1))) continue;
    $b['quantity'] = $qty;
    $act = ($u['role'] ?? '') !== 'recipient' || !approved_receiver((int)($u['id'] ?? 0)) ? '' : (in_array($b['id'], $mine) ? '<span class="btn sec sm" style="pointer-events:none">Requested</span>' : '<form method="post" action="' . url('recipient/request_item.php') . '">' . csrf_field() . '<input type="hidden" name="item_id" value="' . $b['id'] . '"><label>Quantity <input type="number" name="quantity" min="1" max="' . $qty . '" value="1" required></label><button class="btn sm block">Claim this item</button></form>');
    echo item_card($b, $act);
}
