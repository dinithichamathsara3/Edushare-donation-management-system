<?php
$title = 'Item approvals';
require_once __DIR__ . '/../includes/functions.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) $_POST['id'];
    $act = $_POST['act'];
    $s = db()->prepare('SELECT * FROM items WHERE id=?');
    $s->execute([$id]);
    $b = $s->fetch();
    if ($b) {
        if ($act === 'approve') {
            db()->prepare("UPDATE items SET status='approved' WHERE id=?")->execute([$id]);
            notify((int) $b['donor_id'], 'Your book "' . $b['title'] . '" is approved and now visible to recipients.', 'donor/my_items.php');
            admin_log('Approved "' . $b['title'] . '"');
            flash('Book approved.');
        }
        if ($act === 'withdraw' && $b['status'] === 'approved') {
            db()->prepare("UPDATE items SET status='rejected' WHERE id=? AND status='approved'")->execute([$id]);
            notify((int) $b['donor_id'], 'Your listing was withdrawn for administrator review.', 'donor/my_items.php');
            admin_log('Withdrew listing #' . $id);
            flash('Listing withdrawn from public browsing.');
        }
        if ($act === 'reject') {
            db()->prepare("UPDATE items SET status='rejected' WHERE id=?")->execute([$id]);
            notify((int) $b['donor_id'], 'Your book "' . $b['title'] . '" was not approved. Please check the details and list it again.', 'donor/my_items.php');
            admin_log('Rejected "' . $b['title'] . '"');
            flash('Book rejected.');
        }
        if ($act === 'delete') {
            $check = db()->prepare('SELECT COUNT(*) FROM requests WHERE item_id=?');
            $check->execute([$id]);
            if ($check->fetchColumn()) {
                flash('Items with donation history cannot be deleted.', 'err');
                redirect('admin/items.php?s=all');
            }
            db()->prepare('DELETE FROM items WHERE id=?')->execute([$id]);
            admin_log('Deleted "' . $b['title'] . '"');
            flash('Book deleted.');
        }
    }
    redirect('admin/items.php' . (isset($_GET['s']) ? '?s=' . urlencode($_GET['s']) : ''));
}
$f = $_GET['s'] ?? 'pending';
$sql = 'SELECT b.*, c.name AS category, u.name AS donor FROM items b JOIN categories c ON c.id=b.category_id JOIN users u ON u.id=b.donor_id' . ($f !== 'all' ? ' WHERE b.status=?' : '') . ' ORDER BY b.created_at DESC';
$s = db()->prepare($sql);
$s->execute($f !== 'all' ? [$f] : []);
$rows = $s->fetchAll();
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="acts" style="margin-bottom:1rem">
    <?php
    foreach (['pending', 'approved', 'reserved', 'donated', 'rejected', 'cancelled', 'all'] as $t) :
    ?>
        <a class="btn sm <?= $f === $t ? '' : 'sec' ?>" href="?s=<?= $t ?>">
            <?= ucfirst($t) ?>
        </a>
    <?php
    endforeach;
    ?>
</div>
<div class="card">
    <h2>
        Item listings
    </h2>
    <?php
    if (!$rows) :
    ?>
        <div class="empty">
            Nothing in "
            <?= e($f) ?>
            " right now.
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
                        Category
                    </th>
                    <th>
                        Type
                    </th>
                    <th>
                        Status
                    </th>
                    <th>
                        Actions
                    </th>
                </tr>
                <?php
                foreach ($rows as $b) :
                ?>
                    <tr>
                        <td>
                            <div class="cell">
                                <?= cover($b['image']) ?>
                                <div>
                                    <strong>
                                        <?= e($b['title']) ?>
                                    </strong>
                                    <div class="small muted">
                                        <?= e($b['brand']) ?>
                                        ·
                                        <?= e($b['condition_status']) ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?= e($b['donor']) ?>
                        </td>
                        <td>
                            <?= e($b['category']) ?>
                            <div class="small muted">
                                <?= $b['item_type'] === 'book' ? 'Book' : 'Education item' ?>
                                · qty
                                <?= (int)$b['quantity'] ?>
                            </div>
                        </td>
                        <td>
                            <?= badge($b['type']) ?>
                        </td>
                        <td>
                            <?= badge($b['status']) ?>
                        </td>
                        <td>
                            <form method="post" class="acts">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                <?php
                                if ($b['status'] === 'pending') :
                                ?>
                                    <button class="btn green sm" name="act" value="approve">
                                        Approve
                                    </button>
                                    <button class="btn sec sm" name="act" value="reject">
                                        Reject
                                    </button>
                                <?php
                                endif;
                                ?>
                                <?php
                                if ($b['status'] === 'approved') :
                                ?>
                                    <button class="btn sec sm" name="act" value="withdraw">
                                        Withdraw for review
                                    </button>
                                <?php
                                endif;
                                ?>
                                <button class="btn red sm" name="act" value="delete" data-confirm="Delete this item permanently?">
                                    Delete
                                </button>
                            </form>
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
