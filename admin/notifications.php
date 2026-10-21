<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$title = 'Admin notifications and contact messages';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    db()->exec('UPDATE admin_notices SET is_read=1');
    redirect('admin/notifications.php');
}
$rows = db()->query('SELECT * FROM admin_notices ORDER BY id DESC LIMIT 200')->fetchAll();
$messages = db()->query('SELECT * FROM contact_messages ORDER BY id DESC LIMIT 100')->fetchAll();
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="card">
    <h2>
        Notifications
    </h2>
    <form method="post">
        <?= csrf_field() ?>
        <button class="btn">
            Mark all read
        </button>
    </form>
    <ul>
        <?php
        foreach ($rows as $r) :
        ?>
            <li>
                <?= !$r['is_read']?'● ':'' ?>
                <?= e($r['message'].' · '.$r['created_at']) ?>
            </li>
        <?php
        endforeach;
        ?>
    </ul>
    <?php
    if (!$rows) echo '<p>No notifications yet.</p>';
    ?>
</div>
<div class="card">
    <h2>
        Contact inbox
    </h2>
    <?php
    foreach ($messages as $r) :
    ?>
        <p>
            <strong>
                <?= e($r['name']) ?>
            </strong>
            ·
            <?= e($r['email'].' · '.$r['created_at']) ?>
            <br>
            <?= nl2br(e($r['message'])) ?>
        </p>
    <?php
    endforeach;
    ?>
</div>
<?php
require __DIR__ . '/../includes/footer.php';
?>
