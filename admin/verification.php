<?php
require_once __DIR__ . '/../includes/functions.php';
$a = require_admin();
$title = 'Institution verification';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $state = $_POST['state'] ?? '';
    if (in_array($state, ['approved', 'rejected', 'more_information'])) {
        db()->prepare('UPDATE institutions SET verification_status=?,review_note=?,verified_by=?,verified_at=NOW() WHERE user_id=?')->execute([$state, mb_substr(trim($_POST['note'] ?? ''), 0, 1000), $a['id'], (int) $_POST['id']]);
        notify((int) $_POST['id'], 'Institution verification: ' . str_replace('_', ' ', $state), 'recipient/verification.php');
        admin_log('Institution #' . (int) $_POST['id'] . ' ' . $state);
    }
    redirect('admin/verification.php');
}
$rows = db()->query('SELECT i.*,u.name,u.institution,u.email,u.phone,u.address,u.province,u.district,u.city FROM institutions i JOIN users u ON u.id=i.user_id ORDER BY FIELD(verification_status,"pending","more_information","approved","rejected"),u.created_at DESC')->fetchAll();
require __DIR__ . '/../includes/admin_header.php';
if (!$rows) echo '<div class="empty">No institution applications yet.</div>';
foreach ($rows as $r) :
?>
    <article class="card">
        <h2>
            <?= e($r['institution']) ?>
            <?= badge($r['verification_status']) ?>
        </h2>
        <p>
            <?= e($r['institution_type'].' · '.$r['registration_number'].' · '.$r['province'].' / '.$r['district'].' / '.$r['city']) ?>
        </p>
        <p>
            <?= e($r['address'].' · '.$r['contact_person'].' · '.$r['phone'].' · '.$r['email']) ?>
        </p>
        <p>
            <?= e($r['description']) ?>
        </p>
        <a href="<?= url('admin/document.php?id='.$r['user_id']) ?>">
            Download supporting document
        </a>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $r['user_id'] ?>">
            <div class="field">
                <label>
                    Review note / requested information
                </label>
                <textarea name="note">
                    <?= e($r['review_note']) ?>
                </textarea>
            </div>
            <div class="acts">
                <button class="btn" name="state" value="approved">
                    Approve
                </button>
                <button class="btn sec" name="state" value="rejected">
                    Reject
                </button>
                <button class="btn sec" name="state" value="more_information">
                    Request more information
                </button>
            </div>
        </form>
    </article>
<?php
endforeach;
require __DIR__ . '/../includes/footer.php';
?>
