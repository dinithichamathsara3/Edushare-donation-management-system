<?php
$title = 'Donor dashboard';
require_once __DIR__ . '/../includes/functions.php';
$u = require_user('donor');
$q = function ($sql) use($u) {
    $s = db()->prepare($sql);
    $s->execute([$u['id']]);
    return $s->fetchColumn();
};
$st = ['Items listed' => $q('SELECT COUNT(*) FROM items WHERE donor_id=?'), 'Awaiting admin approval' => $q("SELECT COUNT(*) FROM items WHERE donor_id=? AND status='pending'"), 'Open requests' => $q("SELECT COUNT(*) FROM requests r JOIN items b ON b.id=r.item_id WHERE b.donor_id=? AND r.status='pending'"), 'Items passed on' => $q("SELECT COUNT(*) FROM items WHERE donor_id=? AND status='donated'"),];
$st['Available donations'] = $q("SELECT COUNT(*) FROM items WHERE donor_id=? AND status='approved'");
$st['Accepted donations'] = $q("SELECT COUNT(*) FROM requests r JOIN items b ON b.id=r.item_id WHERE b.donor_id=? AND r.status='approved'");
$st['Completed donations'] = $q("SELECT COUNT(*) FROM requests r JOIN items b ON b.id=r.item_id WHERE b.donor_id=? AND r.status='completed'");
$st['Urgent needs'] = (int) db()->query("SELECT COUNT(*) FROM requirements n JOIN institutions i ON i.user_id=n.recipient_id WHERE n.status='open' AND n.urgency_status='approved' AND i.verification_status='approved'")->fetchColumn();
$s = db()->prepare("SELECT r.*, b.title, s.name AS recipient FROM requests r JOIN items b ON b.id=r.item_id JOIN users s ON s.id=r.recipient_id WHERE b.donor_id=? AND r.status='pending' ORDER BY r.created_at DESC LIMIT 5");
$s->execute([$u['id']]);
$open = $s->fetchAll();
require __DIR__ . '/../includes/header.php';
?>
<div class="card" style="background:linear-gradient(120deg,var(--ink),var(--teal));color:#fff;border:0">
    <h2 style="font-size:26px;display:block">
        Thank you,
        <?= e(explode(' ', $u['name'])[0]) ?>
    </h2>
    <p style="color:#bcd8d3;margin:.4rem 0 1rem">
        Every item you share saves a recipient the cost of buying it.
    </p>
    <a class="btn amber" href="<?= url('donor/add_item.php') ?>">
        Donate an item
    </a>
    <a class="btn amber" href="<?= url('needs.php') ?>">
        Find nearby needs
    </a>
</div>
<div class="stats">
    <?php
    foreach ($st as $k => $v) :
    ?>
        <div class="stat">
            <b>
                <?= (int)$v ?>
            </b>
            <span>
                <?= e($k) ?>
            </span>
        </div>
    <?php
    endforeach;
    ?>
</div>
<div class="card">
    <h2>
        Requests waiting for you
        <a class="small" href="<?= url('donor/requests.php') ?>">
            Manage
        </a>
    </h2>
    <?php
    if (!$open) :
    ?>
        <div class="empty">
            No open requests right now.
        </div>
    <?php
    else :
    ?>
        <div class="tbl-wrap">
            <table>
                <tr>
                    <th>
                        Item
                    </th>
                    <th>
                        Recipient
                    </th>
                    <th>
                        When
                    </th>
                </tr>
                <?php
                foreach ($open as $r) :
                ?>
                    <tr>
                        <td>
                            <?= e($r['title']) ?>
                        </td>
                        <td>
                            <?= e($r['recipient']) ?>
                        </td>
                        <td>
                            <?= ago($r['created_at']) ?>
                        </td>
                    </tr>
                <?php
                endforeach;
                ?>
            </table>
        </div>
    <?php
    endif;
    ?>
</div>
<?php
require __DIR__ . '/../includes/footer.php';
?>
