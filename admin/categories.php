<?php
$title = 'Categories';
require_once __DIR__ . '/../includes/functions.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (!empty($_POST['name'])) {
        db()->prepare('INSERT IGNORE INTO categories (name) VALUES (?)')->execute([trim($_POST['name'])]);
        admin_log('Added category ' . trim($_POST['name']));
        flash('Category added.');
    }
    if (!empty($_POST['del'])) {
        try {
            db()->prepare('DELETE FROM categories WHERE id=?')->execute([(int) $_POST['del']]);
            admin_log('Deleted a category');
            flash('Category deleted.');
        } catch (PDOException $x) {
            flash('That category still has items, so it can\'t be deleted.', 'err');
        }
    }
    redirect('admin/categories.php');
}
$rows = db()->query('SELECT c.*, (SELECT COUNT(*) FROM items b WHERE b.category_id=c.id) AS n FROM categories c ORDER BY name')->fetchAll();
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="grid2" style="align-items:start">
    <form class="card" method="post">
        <h2>
            Add a category
        </h2>
        <?= csrf_field() ?>
        <div class="field">
            <label>
                Name
            </label>
            <input name="name" required placeholder="e.g. Medical Textbooks">
        </div>
        <button class="btn">
            Add category
        </button>
    </form>
    <div class="card">
        <h2>
            Current categories
        </h2>
        <div class="tbl-wrap">
            <table>
                <?php
                foreach ($rows as $c) :
                ?>
                    <tr>
                        <td>
                            <?= e($c['name']) ?>
                        </td>
                        <td class="muted">
                            <?= $c['n'] ?>
                            items
                        </td>
                        <td class="right">
                            <form method="post">
                                <?= csrf_field() ?>
                                <button class="btn sec sm" name="del" value="<?= $c['id'] ?>" data-confirm="Delete this category?">
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
    </div>
</div>
<?php
require __DIR__ . '/../includes/footer.php';
?>
