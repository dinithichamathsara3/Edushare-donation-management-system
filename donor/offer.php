<?php
require_once __DIR__ . '/../includes/functions.php';
$u = require_user('donor');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('needs.php');
csrf_check();
try {
    db()->beginTransaction();
    $s = db()->prepare("SELECT * FROM items WHERE id=? AND donor_id=? AND status='approved' FOR UPDATE");
    $s->execute([(int) $_POST['item_id'], $u['id']]);
    $b = $s->fetch();
    $s = db()->prepare("SELECT * FROM requirements WHERE id=? AND status='open' FOR UPDATE");
    $s->execute([(int) $_POST['requirement_id']]);
    $n = $s->fetch();
    $qty = (int)($_POST['quantity'] ?? 0);
    if (!$b || !$n || !approved_receiver((int) $n['recipient_id']) || $qty < 1 || $qty > available($b) || $qty > remaining($n)) throw new RuntimeException('Offer exceeds available quantity or remaining need.');
    $s = db()->prepare("SELECT 1 FROM requests WHERE item_id=? AND requirement_id=? AND status IN ('pending','approved')");
    $s->execute([$b['id'], $n['id']]);
    if ($s->fetch()) throw new RuntimeException('An active offer already exists for this donation and requirement.');
    db()->prepare("INSERT INTO requests(item_id,recipient_id,requirement_id,source,quantity,message) VALUES(?,?,?,'offer',?,?)")->execute([$b['id'], $n['recipient_id'], $n['id'], $qty, mb_substr(trim($_POST['message'] ?? ''), 0, 255)]);
    record_stage((int) db()->lastInsertId(), 'offered');
    notify((int) $n['recipient_id'], $u['name'] . ' offered ' . $qty . ' × ' . $b['title'], 'workflow.php');
    db()->commit();
    flash('Offer sent to institution.');
} catch (Throwable $e) {
    if (db()->inTransaction()) db()->rollBack();
    flash($e->getMessage(), 'err');
}
redirect('workflow.php');
