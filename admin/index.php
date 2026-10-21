<?php
$title = 'Overview';
require_once __DIR__ . '/../includes/admin_header.php';
$m = db();
$c = fn($sql) => (int) $m->query($sql)->fetchColumn();
$st = ['Recipients' => $c("SELECT COUNT(*) FROM users WHERE role='recipient'"), 'Donors' => $c("SELECT COUNT(*) FROM users WHERE role='donor'"), 'Live items' => $c("SELECT COUNT(*) FROM items WHERE status='approved'"), 'Awaiting approval' => $c("SELECT COUNT(*) FROM items WHERE status='pending'"), 'Open requests' => $c("SELECT COUNT(*) FROM requests WHERE status='pending'"), 'Items passed on' => $c("SELECT COUNT(*) FROM items WHERE status='donated'"),];
$st['Verified institutions'] = $c("SELECT COUNT(*) FROM institutions WHERE verification_status='approved'");
$st['Pending verifications'] = $c("SELECT COUNT(*) FROM institutions WHERE verification_status IN ('pending','more_information')");
$st['Urgent requirements'] = $c("SELECT COUNT(*) FROM requirements WHERE urgency='high' AND status='open'");
$st['Active claims'] = $c("SELECT COUNT(*) FROM requests WHERE status='approved'");
$st['Completed donations'] = $c("SELECT COUNT(*) FROM requests WHERE status='completed'");
$recent = $m->query("SELECT b.*, u.name AS donor FROM items b JOIN users u ON u.id=b.donor_id ORDER BY b.created_at DESC LIMIT 6")->fetchAll();
$log = db('admin')->query('SELECT l.*, a.name FROM activity_log l JOIN admins a ON a.id=l.admin_id ORDER BY l.id DESC LIMIT 8')->fetchAll();
?>
<div class="stats">
    <?php
    foreach ($st as $k => $v) :
    ?>
        <div class="stat">
            <b>
                <?= $v ?>
            </b>
            <span>
                <?= e($k) ?>
            </span>
        </div>
    <?php
    endforeach;
    ?>
</div>
<p>
    <a class="btn" href="<?= url('admin/reports.php') ?>">
        View charts and reports
    </a>
</p>
<div class="grid2" style="align-items:start">
    <div class="card">
        <h2>
            Latest listings
            <a class="small" href="<?= url('admin/items.php') ?>">
                Review all
            </a>
        </h2>
        <?php
        if (!$recent) :
        ?>
            <div class="empty">
                No items yet.
            </div>
        <?php
        endif;
        ?>
        <div class="tbl-wrap">
            <table>
                <?php
                foreach ($recent as $b) :
                ?>
                    <tr>
                        <td>
                            <strong>
                                <?= e($b['title']) ?>
                            </strong>
                            <div class="small muted">
                                <?= e($b['donor']) ?>
                            </div>
                        </td>
                        <td>
                            <?= badge($b['status']) ?>
                        </td>
                    </tr>
                <?php
                endforeach;
                ?>
            </table>
        </div>
    </div>
    <div class="card">
        <h2>
            Admin activity
        </h2>
        <ul class="act-feed">
            <?php
            foreach ($log as $l) :
            ?>
                <li>
                    <span>
                        <strong>
                            <?= e($l['name']) ?>
                        </strong>
                        ·
                        <?= e($l['action']) ?>
                    </span>
                    <span class="small muted">
                        <?= ago($l['created_at']) ?>
                    </span>
                </li>
            <?php
            endforeach;
            ?>
        </ul>
    </div>
</div>
<?php
require __DIR__ . '/../includes/footer.php';
?>
