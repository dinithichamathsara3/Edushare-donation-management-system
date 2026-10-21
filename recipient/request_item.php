<?php
require_once __DIR__ . '/../includes/functions.php';
$u = require_receiver();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('recipient/items.php');
csrf_check();
try {
    $id = (int)($_POST['item_id'] ?? 0);
    $qty = (int)($_POST['quantity'] ?? 1);
    db()->beginTransaction();
    $s = db()->prepare("SELECT * FROM items WHERE id=? AND status='approved' FOR UPDATE");
    $s->execute([$id]);
    $b = $s->fetch();
    if (!$b || $qty < 1 || $qty > available($b)) throw new RuntimeException('Choose a quantity within the available amount.');
    $s = db()->prepare("SELECT 1 FROM requests WHERE item_id=? AND recipient_id=? AND status IN ('pending','approved')");
    $s->execute([$id, $u['id']]);
    if ($s->fetch()) throw new RuntimeException('You already have an active claim on this item.');
    db()->prepare('INSERT INTO requests(item_id,recipient_id,message,quantity) VALUES(?,?,?,?)')->execute([$id, $u['id'], mb_substr(trim($_POST['message'] ?? ''), 0, 250), $qty]);
    record_stage((int) db()->lastInsertId(), 'requested');
    notify((int) $b['donor_id'], $u['name'] . ' claimed ' . $qty . ' × ' . $b['title'], 'workflow.php');
    db()->commit();
    flash('Claim submitted to donor.');
} catch (Throwable $e) {
    if (db()->inTransaction()) db()->rollBack();
    flash($e->getMessage(), 'err');
}
redirect('workflow.php');
