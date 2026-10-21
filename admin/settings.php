<?php
require_once __DIR__ . '/../includes/functions.php';
$a = require_admin();
$title = 'System settings';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $s = db('admin')->prepare('SELECT password FROM admins WHERE id=?');
    $s->execute([$a['id']]);
    if (!password_verify($_POST['current'] ?? '', $s->fetchColumn())) flash('Current password is incorrect.', 'err');
    elseif (strlen($_POST['password'] ?? '') < 10 || ($_POST['password'] ?? '') !== ($_POST['confirm'] ?? '')) flash('Use at least 10 characters and matching confirmation.', 'err');
    else {
        db('admin')->prepare('UPDATE admins SET password=? WHERE id=?')->execute([password_hash($_POST['password'], PASSWORD_DEFAULT), $a['id']]);
        session_regenerate_id(true);
        admin_log('Changed own password');
        flash('Administrator password updated.');
    }
    redirect('admin/settings.php');
}
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="card">
    <h2>
        Administrator password
    </h2>
    <form method="post">
        <?= csrf_field() ?>
        <?= field('current','Current password','password','',true) ?>
        <?= field('password','New password (at least 10 characters)','password','',true) ?>
        <?= field('confirm','Confirm password','password','',true) ?>
        <button class="btn">
            Update password
        </button>
    </form>
</div>
<div class="card">
    <h2>
        Site configuration
    </h2>
    <p>
        Database connection and base URL are configured in includes/config.php. Institution approval and urgent approval are mandatory. Normal donations become available immediately.
    </p>
</div>
<?php
require __DIR__ . '/../includes/footer.php';
?>
