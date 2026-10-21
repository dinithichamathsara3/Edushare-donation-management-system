<?php
function admin_notice(string $message) : void {
    db()->prepare('INSERT INTO admin_notices(message) VALUES(?)')->execute([mb_substr($message, 0, 255)]);
}
function institution(int $id) : ? array {
    $s = db()->prepare('SELECT * FROM institutions WHERE user_id=?');
    $s->execute([$id]);
    return $s->fetch() ? : null;
}
function approved_receiver(int $id) : bool {
    $s = db()->prepare("SELECT 1 FROM institutions i JOIN users u ON u.id=i.user_id WHERE user_id=? AND verification_status='approved' AND u.status='active'");
    $s->execute([$id]);
    return(bool) $s->fetchColumn();
}
function require_receiver() : array {
    $u = require_user('recipient');
    if (!approved_receiver($u['id'])) {
        flash('Your institution must be approved before using this feature.', 'err');
        redirect('recipient/verification.php');
    }
    return $u;
}
function field(string $name, string $label, string $type = 'text', $value = '', bool $required = false) : string {
    return '<div class="field"><label for="f-' . e($name) . '">' . e($label) . '</label><input id="f-' . e($name) . '" name="' . e($name) . '" type="' . e($type) . '" value="' . e($value) . '" ' . ($required ? 'required' : '') . '></div>';
}
function coordinates(array $v) : array {
    $a = trim($v['latitude'] ?? '');
    $b = trim($v['longitude'] ?? '');
    if ($a === '' && $b === '') return[null, null];
    if (!is_numeric($a) || !is_numeric($b) || abs((float) $a) > 90 || abs((float) $b) > 180) throw new RuntimeException('Enter a valid latitude and longitude, or leave both empty.');
    return[(float) $a, (float) $b];
}
function distance($a, $b, $c, $d) : ? float {
    if ($a === null || $b === null || $c === null || $d === null) return null;
    $v = sin(deg2rad($c - $a) / 2) ** 2 + cos(deg2rad($a)) * cos(deg2rad($c)) * sin(deg2rad($d - $b) / 2) ** 2;
    return round(6371 * 2 * atan2(sqrt($v), sqrt(max(0, 1 - $v))), 1);
}
function available(array $item) : int {
    $s = db()->prepare("SELECT COALESCE(SUM(quantity),0) FROM requests WHERE item_id=? AND status IN ('approved','completed')");
    $s->execute([$item['id']]);
    return max(0, (int) $item['quantity'] - (int) $s->fetchColumn());
}
function remaining(array $need) : int {
    $s = db()->prepare("SELECT COALESCE(SUM(quantity),0) FROM requests WHERE requirement_id=? AND status='approved'");
    $s->execute([$need['id']]);
    return max(0, (int) $need['quantity_required'] - (int) $need['quantity_received'] - (int) $s->fetchColumn());
}
function record_stage(int $id, string $stage, string $remarks = '') : void {
    db()->prepare('INSERT INTO tracking(request_id,status,actor,remarks) VALUES(?,?,?,?)')->execute([$id, $stage, (admin() ? 'Admin: ' . admin()['name'] : (user()['name'] ?? 'System')), mb_substr($remarks, 0, 255)]);
}
function upload_document() : string {
    $f = $_FILES['verification_document'] ?? null;
    if (!$f || $f['error'] !== UPLOAD_ERR_OK || $f['size'] > 5 * 1024 * 1024) throw new RuntimeException('Upload a PDF, JPG or PNG verification document (maximum 5 MB).');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $types = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];
    if (!isset($types[$mime])) throw new RuntimeException('Only PDF, JPG and PNG verification documents are accepted.');
    $dir = __DIR__ . '/../private/verification';
    if (!is_dir($dir)) mkdir($dir, 0700, true);
    $name = bin2hex(random_bytes(16)) . '.' . $types[$mime];
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) throw new RuntimeException('Could not save document.');
    return $name;
}
function accept_request(int $id) : void {
    $m = db();
    $m->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
    $m->beginTransaction();
    try {
        // Serialize decisions on the item, and on the requirement for offers from different donors.
        $s = $m->prepare('SELECT item_id FROM requests WHERE id=?');
        $s->execute([$id]);
        $itemId = $s->fetchColumn();
        if (!$itemId) throw new RuntimeException('Request not found.');
        $s = $m->prepare('SELECT * FROM items WHERE id=? FOR UPDATE');
        $s->execute([$itemId]);
        $item = $s->fetch();
        $s = $m->prepare('SELECT * FROM requests WHERE id=? FOR UPDATE');
        $s->execute([$id]);
        $r = $s->fetch();
        if (!$r || $r['status'] !== 'pending' || !approved_receiver((int) $r['recipient_id'])) throw new RuntimeException('This request cannot be accepted.');
        $donor = $m->prepare("SELECT 1 FROM users WHERE id=? AND status='active'");
        $donor->execute([$item['donor_id']]);
        if (!$donor->fetchColumn()) throw new RuntimeException('Donor account is unavailable.');
        if (!in_array($item['status'], ['approved', 'reserved']) || available($item) < $r['quantity']) throw new RuntimeException('Not enough available items.');
        if ($r['requirement_id']) {
            $s = $m->prepare('SELECT * FROM requirements WHERE id=? FOR UPDATE');
            $s->execute([$r['requirement_id']]);
            $n = $s->fetch();
            if (!$n || $n['status'] !== 'open' || remaining($n) < $r['quantity']) throw new RuntimeException('The requirement no longer needs this quantity.');
        }
        $m->prepare("UPDATE requests SET status='approved', stage='accepted' WHERE id=?")->execute([$id]);
        $m->prepare('UPDATE items SET status=? WHERE id=?')->execute([available($item) > 0 ? 'approved' : 'reserved', $item['id']]);
        record_stage($id, 'accepted');
        notify((int) $r['recipient_id'], 'Donation accepted: ' . $item['title'], 'workflow.php');
        notify((int) $item['donor_id'], 'Donation accepted: ' . $item['title'], 'workflow.php');
        $m->commit();
    } catch (Throwable $e) {
        if ($m->inTransaction()) $m->rollBack();
        throw $e;
    }
}
function complete_request(int $id) : void {
    $m = db();
    $m->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
    $m->beginTransaction();
    try {
        $s = $m->prepare('SELECT item_id FROM requests WHERE id=?');
        $s->execute([$id]);
        $itemId = $s->fetchColumn();
        $s = $m->prepare('SELECT * FROM items WHERE id=? FOR UPDATE');
        $s->execute([$itemId]);
        $item = $s->fetch();
        $s = $m->prepare('SELECT * FROM requests WHERE id=? FOR UPDATE');
        $s->execute([$id]);
        $r = $s->fetch();
        if (!$r || $r['status'] !== 'approved' || $r['stage'] !== 'delivered') throw new RuntimeException('Confirm delivery before completing.');
        if ($r['requirement_id']) {
            $s = $m->prepare('SELECT * FROM requirements WHERE id=? FOR UPDATE');
            $s->execute([$r['requirement_id']]);
            $s->fetch();
        }
        $m->prepare("UPDATE requests SET status='completed',stage='completed' WHERE id=?")->execute([$id]);
        if ($r['requirement_id']) $m->prepare("UPDATE requirements SET status=IF(quantity_received+?>=quantity_required,'completed','open'),quantity_received=quantity_received+? WHERE id=?")->execute([$r['quantity'], $r['quantity'], $r['requirement_id']]);
        $s = $m->prepare("SELECT COUNT(*) FROM requests WHERE item_id=? AND status='approved'");
        $s->execute([$itemId]);
        $active = (int) $s->fetchColumn();
        $m->prepare('UPDATE items SET status=? WHERE id=?')->execute([available($item) > 0 ? 'approved' : ($active ? 'reserved' : 'donated'), $itemId]);
        record_stage($id, 'completed');
        notify((int) $r['recipient_id'], 'Donation completed: ' . $item['title'], 'workflow.php');
        notify((int) $item['donor_id'], 'Donation completed: ' . $item['title'], 'donor/impact.php');
        $m->commit();
    } catch (Throwable $e) {
        if ($m->inTransaction()) $m->rollBack();
        throw $e;
    }
}
function matching_notices(int $itemId) : void {
    $s = db()->prepare("SELECT * FROM items WHERE id=? AND status='approved'");
    $s->execute([$itemId]);
    $b = $s->fetch();
    if (!$b) return;
    $s = db()->prepare("SELECT n.*,u.latitude,u.longitude FROM requirements n JOIN institutions i ON i.user_id=n.recipient_id JOIN users u ON u.id=n.recipient_id WHERE n.status='open' AND n.category_id=? AND i.verification_status='approved' AND u.status='active'");
    $s->execute([$b['category_id']]);
    $matches = [];
    foreach ($s->fetchAll() as $n) {
        if (remaining($n) < 1) continue;
        if ($b['subject'] && $n['subject'] && strcasecmp($b['subject'], $n['subject']) !== 0) continue;
        if ($b['education_level'] && $n['education_level'] && strcasecmp($b['education_level'], $n['education_level']) !== 0) continue;
        $km = distance($b['latitude'], $b['longitude'], $n['latitude'], $n['longitude']);
        notify((int) $n['recipient_id'], 'Matching donation available: ' . $b['title'] . ($km !== null ? ' (' . $km . ' km away)' : ''), 'recipient/items.php');
        $matches[$n['recipient_id']] = true;
    }
    if ($matches) notify((int) $b['donor_id'], count($matches) . ' institutions have matching requirements for ' . $b['title'], 'needs.php?item=' . $b['id']);
}
