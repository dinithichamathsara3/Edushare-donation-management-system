<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$title = 'Requirements and urgent review';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) $_POST['id'];
    $act = $_POST['act'] ?? '';
    if (in_array($act, ['approved', 'rejected'])) {
        db()->prepare("UPDATE requirements SET urgency_status=? WHERE id=? AND urgency='high' AND urgency_status='pending'")->execute([$act, $id]);
        $s = db()->prepare('SELECT recipient_id,title FROM requirements WHERE id=?');
        $s->execute([$id]);
        if ($r = $s->fetch()) notify((int) $r['recipient_id'], 'Urgent priority ' . $act . ': ' . $r['title'], 'recipient/requirements.php');
        admin_log('Urgent requirement #' . $id . ' ' . $act);
    }
    redirect('admin/requirements.php');
}
$rows = db()->query('SELECT n.*,u.institution,c.name category FROM requirements n JOIN users u ON u.id=n.recipient_id JOIN categories c ON c.id=n.category_id ORDER BY n.urgency_status="pending" DESC,n.created_at DESC')->fetchAll();
require __DIR__ . '/../includes/admin_header.php';
if (!$rows) echo '<div class="empty">No requirements yet.</div>';
foreach ($rows as $r) :
?>
    <article class="card">
        <h2>
            <?= e($r['title'].' · '.$r['institution']) ?>
        </h2>
        <p>
            <?= e($r['category'].' · '.$r['subject'].' · '.$r['education_level']) ?>
        </p>
        <p>
            Required
            <?= $r['quantity_required'] ?>
            / received
            <?= $r['quantity_received'] ?>
            · Due
            <?= e($r['required_date']) ?>
            ·
            <?= badge($r['status']) ?>
            <?= badge($r['urgency_status']) ?>
        </p>
        <p>
            <?= e($r['reason']) ?>
        </p>
        <?php
        if ($r['urgency_status'] === 'pending') :
        ?>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                <button class="btn" name="act" value="approved">
                    Approve urgent priority
                </button>
                <button class="btn sec" name="act" value="rejected">
                    Reject urgent priority
                </button>
            </form>
        <?php
        endif;
        ?>
    </article>
<?php
endforeach;
require __DIR__ . '/../includes/footer.php';
?>
