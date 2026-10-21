<?php
$title = 'My items';
require_once __DIR__ . '/../includes/functions.php';
$u = require_user('donor');
if (isset($_POST['del'])) {
    csrf_check();
    db()->prepare("UPDATE items SET status='cancelled' WHERE id=? AND donor_id=? AND status IN ('pending','approved','rejected') AND NOT EXISTS (SELECT 1 FROM requests WHERE requests.item_id=items.id AND requests.status IN ('pending','approved','completed'))")->execute([(int) $_POST['del'], $u['id']]);
    flash('Cancellation processed. Donations with active claims or completed history are kept.');
    redirect('donor/my_items.php');
}
$s = db()->prepare('SELECT b.*, c.name AS category FROM items b JOIN categories c ON c.id=b.category_id WHERE donor_id=? ORDER BY created_at DESC');
$s->execute([$u['id']]);
$rows = $s->fetchAll();
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <h2>
        Everything you've listed
        <a class="btn sm" href="<?= url('donor/add_item.php') ?>">
            Add an item
        </a>
    </h2>
    <?php
    if (!$rows) :
    ?>
        <div class="empty">
            You haven't listed an item yet.
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
                        Category
                    </th>
                    <th>
                        Type
                    </th>
                    <th>
                        Status
                    </th>
                    <th>
                        Listed
                    </th>
                    <th>
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
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?= e($b['category']) ?>
                        </td>
                        <td>
                            <?= badge($b['type']) ?>
                        </td>
                        <td>
                            <?= badge($b['status']) ?>
                        </td>
                        <td>
                            <?= ago($b['created_at']) ?>
                        </td>
                        <td>
                            <a class="btn sec sm" href="<?= url('donor/edit_item.php?id='.$b['id']) ?>">
                                Edit
                            </a>
                            <?php
                            if (in_array($b['status'], ['pending', 'approved', 'rejected'])) :
                            ?>
                                <form method="post">
                                    <?= csrf_field() ?>
                                    <button class="btn sec sm" name="del" value="<?= $b['id'] ?>" data-confirm="Remove this item?">
                                        Cancel listing
                                    </button>
                                </form>
                            <?php
                            endif;
                            ?>
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
