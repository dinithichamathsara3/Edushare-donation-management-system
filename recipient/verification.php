<?php
require_once __DIR__ . '/../includes/functions.php';
$u = require_user('recipient');
$i = institution($u['id']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        if (!$i || $i['verification_status'] !== 'more_information') throw new RuntimeException('Document resubmission is not available.');
        $doc = upload_document();
        db()->prepare("UPDATE institutions SET verification_document=?,verification_status='pending' WHERE user_id=?")->execute([$doc, $u['id']]);
        admin_notice('Institution resubmitted verification: ' . $u['name']);
        flash('Updated document submitted.');
        redirect('recipient/verification.php');
    } catch (Throwable $e) {
        flash($e->getMessage(), 'err');
    }
}
$title = 'Institution verification';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <h2>
        Application status
    </h2>
    <?= badge($i['verification_status']??'pending') ?>
    <p>
        <?= e($i['review_note']??'Submit official institution details to an administrator for verification. Existing individual receiver accounts are not automatically approved.') ?>
    </p>
    <?php
    if (!$i) :
    ?>
        <a class="btn" href="<?= url('recipient/application.php') ?>">
            Apply as an institution
        </a>
    <?php
    endif;
    ?>
    <?php
    if ($i && $i['verification_status'] === 'approved') :
    ?>
        <a class="btn" href="<?= url('recipient/') ?>">
            Open dashboard
        </a>
    <?php
    elseif ($i && $i['verification_status'] === 'more_information') :
    ?>
        <form method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="field">
                <label>
                    Updated verification document
                </label>
                <input type="file" name="verification_document" required accept="application/pdf,image/jpeg,image/png">
            </div>
            <button class="btn">
                Resubmit document
            </button>
        </form>
    <?php
    endif;
    ?>
</div>
<?php
require __DIR__ . '/../includes/footer.php';
?>
