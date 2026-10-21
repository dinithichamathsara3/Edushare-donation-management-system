<?php
$title = 'Recipient dashboard';
require_once __DIR__ . '/../includes/functions.php';
$u = require_receiver();
$q = fn($sql, $p = []) => (function () use($sql, $p) {
    $s = db()->prepare($sql); $s->execute($p); return $s;
})();
$st = ['Available items' => $q("SELECT COUNT(*) FROM items WHERE status='approved'")->fetchColumn(), 'Pending requests' => $q("SELECT COUNT(*) FROM requests WHERE recipient_id=? AND status='pending'", [$u['id']])->fetchColumn(), 'Approved requests' => $q("SELECT COUNT(*) FROM requests WHERE recipient_id=? AND status='approved'", [$u['id']])->fetchColumn(), 'Items received' => $q("SELECT COUNT(*) FROM requests WHERE recipient_id=? AND status='completed'", [$u['id']])->fetchColumn(),];
$st['My requirements'] = $q('SELECT COUNT(*) FROM requirements WHERE recipient_id=?', [$u['id']])->fetchColumn();
$st['Urgent requirements'] = $q("SELECT COUNT(*) FROM requirements WHERE recipient_id=? AND urgency='high' AND status='open'", [$u['id']])->fetchColumn();
$new = $q("SELECT b.*, c.name AS category, d.name AS donor FROM items b JOIN categories c ON c.id=b.category_id JOIN users d ON d.id=b.donor_id WHERE b.status='approved' ORDER BY b.created_at DESC LIMIT 4")->fetchAll();
$recent = $q("SELECT r.*, b.title, d.name AS donor FROM requests r JOIN items b ON b.id=r.item_id JOIN users d ON d.id=b.donor_id WHERE r.recipient_id=? ORDER BY r.created_at DESC LIMIT 5", [$u['id']])->fetchAll();
require __DIR__ . '/../includes/header.php';
?>
<div class="card" style="background:linear-gradient(120deg,var(--ink),var(--teal));color:#fff;border:0">
    <h2 style="font-size:26px;display:block">
        Hello,
        <?= e(explode(' ', $u['name'])[0]) ?>
    </h2>
    <p style="color:#bcd8d3;margin:.4rem 0 1rem">
        Find the books and learning materials you need this term.
    </p>
    <a class="btn amber" href="<?= url('recipient/items.php') ?>">
        Browse items
    </a>
    <a class="btn amber" href="<?= url('recipient/requirements.php') ?>">
        Create requirement
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
        New on the shelf
        <a class="small" href="<?= url('recipient/items.php') ?>">
            See all
        </a>
    </h2>
    <?php
    if (!$new) :
    ?>
        <div class="empty">
            No items listed yet. Check back soon.
        </div>
    <?php
    endif;
    ?>
    <div class="shelf">
        <?php
        foreach ($new as $b) echo item_card($b);
        ?>
    </div>
</div>
<div class="card">
    <h2>
        My latest requests
        <a class="small" href="<?= url('recipient/requests.php') ?>">
            View all
        </a>
    </h2>
    <?php
    if (!$recent) :
    ?>
        <div class="empty">
            You haven't requested anything yet.
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
                        Donor
                    </th>
                    <th>
                        Status
                    </th>
                    <th>
                        Date
                    </th>
                </tr>
                <?php
                foreach ($recent as $r) :
                ?>
                    <tr>
                        <td>
                            <?= e($r['title']) ?>
                        </td>
                        <td>
                            <?= e($r['donor']) ?>
                        </td>
                        <td>
                            <?= badge($r['status']) ?>
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
