<?php
$title = 'Notifications';
require_once __DIR__ . '/includes/functions.php';
$u = require_user();
if (isset($_POST['readall'])) {
    csrf_check();
    db()->prepare('UPDATE notifications SET is_read=1 WHERE user_id=?')->execute([$u['id']]);
    redirect('notifications.php');
}
$s = db()->prepare('SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 50');
$s->execute([$u['id']]);
$rows = $s->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<div class="card">
    <h2>
        Your updates
        <form method="post">
            <?= csrf_field() ?>
            <button name="readall" class="btn sec sm">
                Mark all as read
            </button>
        </form>
    </h2>
    <?php
    if (!$rows) :
    ?>
        <div class="empty">
            Nothing yet. You'll be told here when an item is approved or a request changes.
        </div>
    <?php
    endif;
    ?>
    <?php
    foreach ($rows as $n) :
    ?>
        <div class="note <?= $n['is_read'] ? '' : 'new' ?>">
            <i>
            </i>
            <div>
                <?= e($n['message']) ?>
                <?php
                if ($n['link']) :
                ?>
                    <a href="<?= url(e($n['link'])) ?>">
                        View
                    </a>
                <?php
                endif;
                ?>
                <div class="small muted">
                    <?= ago($n['created_at']) ?>
                </div>
            </div>
        </div>
    <?php
    endforeach;
    ?>
</div>
<?php
db()->prepare('UPDATE notifications SET is_read=1 WHERE user_id=?')->execute([$u['id']]);
require __DIR__ . '/includes/footer.php';
