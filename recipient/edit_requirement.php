<?php
require_once __DIR__ . '/../includes/functions.php';
$u = require_receiver();
$title = 'Edit requirement';
$id = (int)($_GET['id'] ?? 0);
$s = db()->prepare('SELECT * FROM requirements WHERE id=? AND recipient_id=?');
$s->execute([$id, $u['id']]);
$n = $s->fetch();
if (!$n) {
    http_response_code(404);
    exit('Requirement not found.');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        db()->beginTransaction();
        $s = db()->prepare('SELECT * FROM requirements WHERE id=? FOR UPDATE');
        $s->execute([$id]);
        $n = $s->fetch();
        $s = db()->prepare("SELECT COUNT(*) FROM requests WHERE requirement_id=? AND status IN ('pending','approved','completed')");
        $s->execute([$id]);
        if ($s->fetchColumn() || $n['status'] !== 'open' || $n['quantity_received'] > 0) throw new RuntimeException('Only requirements without donation activity can be edited.');
        $qty = (int)($_POST['quantity_required'] ?? 0);
        $date = $_POST['required_date'] ?? '';
        $dt = DateTime::createFromFormat('!Y-m-d', $date);
        if (trim($_POST['title'] ?? '') === '' || $qty < 1 || !$dt || $dt->format('Y-m-d') !== $date || $date < date('Y-m-d') || trim($_POST['reason'] ?? '') === '') throw new RuntimeException('Enter a title, reason, positive quantity and future date.');
        $high = ($_POST['urgency'] ?? '') === 'high';
        db()->prepare('UPDATE requirements SET title=?,subject=?,education_level=?,quantity_required=?,description=?,reason=?,required_date=?,urgency=?,urgency_status=? WHERE id=?')->execute([trim($_POST['title']), trim($_POST['subject'] ?? ''), trim($_POST['education_level'] ?? ''), $qty, trim($_POST['description'] ?? ''), trim($_POST['reason']), $date, $high ? 'high' : 'normal', $high ? 'pending' : 'not_required', $id]);
        if ($high) admin_notice('Edited urgent requirement needs new review: ' . trim($_POST['title']));
        db()->commit();
        flash('Requirement updated. Edited urgent requests must be reviewed again.');
        redirect('recipient/requirements.php');
    } catch (Throwable $e) {
        if (db()->inTransaction()) db()->rollBack();
        flash($e->getMessage(), 'err');
    }
}
require __DIR__ . '/../includes/header.php';
?>
<form method="post" class="card">
    <?= csrf_field() ?>
    <?php
    foreach (['title' => 'Item name', 'subject' => 'Subject', 'education_level' => 'Grade', 'quantity_required' => 'Quantity required', 'description' => 'Description', 'reason' => 'Reason', 'required_date' => 'Required date'] as $key => $label) echo field($key, $label, $key === 'quantity_required' ? 'number' : ($key === 'required_date' ? 'date' : 'text'), $n[$key], in_array($key, ['title', 'reason', 'quantity_required', 'required_date']));
    ?>
    <div class="field">
        <label>
            Urgency
        </label>
        <select name="urgency">
            <option value="normal" <?= $n['urgency']==='normal'?'selected':'' ?>>
                Normal
            </option>
            <option value="high" <?= $n['urgency']==='high'?'selected':'' ?>>
                Urgent (requires review)
            </option>
        </select>
    </div>
    <button class="btn">
        Save requirement
    </button>
</form>
<?php
require __DIR__ . '/../includes/footer.php';
?>
