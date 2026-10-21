<?php
$title = 'Members';
require_once __DIR__ . '/../includes/functions.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) $_POST['id'];
    $act = $_POST['act'];
    $s = db()->prepare('SELECT * FROM users WHERE id=?');
    $s->execute([$id]);
    $m = $s->fetch();
    if ($m) {
        if ($act === 'block' || $act === 'unblock') {
            db()->prepare('UPDATE users SET status=? WHERE id=?')->execute([$act === 'block' ? 'blocked' : 'active', $id]);
            admin_log(ucfirst($act) . 'ed ' . $m['email']);
            flash('Member updated.');
        }
        if ($act === 'delete') {
            $check = db()->prepare('SELECT (SELECT COUNT(*) FROM items WHERE donor_id=?)+(SELECT COUNT(*) FROM requests WHERE recipient_id=?)+(SELECT COUNT(*) FROM requirements WHERE recipient_id=?)');
            $check->execute([$id, $id, $id]);
            if ($check->fetchColumn()) {
                flash('Accounts with donation history cannot be deleted. Use Block instead.', 'err');
                redirect('admin/users.php');
            }
            db()->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
            admin_log('Deleted member ' . $m['email']);
            flash('Member deleted.');
        }
    }
    redirect('admin/users.php');
}
$role = in_array($_GET['role'] ?? '', ['recipient', 'donor']) ? $_GET['role'] : '';
$s = db()->prepare('SELECT * FROM users' . ($role ? ' WHERE role=?' : '') . ' ORDER BY created_at DESC');
$s->execute($role ? [$role] : []);
$rows = $s->fetchAll();
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="acts" style="margin-bottom:1rem">
    <a class="btn sm <?= $role ? 'sec' : '' ?>" href="?">
        All
    </a>
    <a class="btn sm <?= $role === 'recipient' ? '' : 'sec' ?>" href="?role=recipient">
        Recipients
    </a>
    <a class="btn sm <?= $role === 'donor' ? '' : 'sec' ?>" href="?role=donor">
        Donors
    </a>
</div>
<div class="card">
    <h2>
        Registered members (
        <?= count($rows) ?>
        )
    </h2>
    <?php
    if (!$rows) :
    ?>
        <div class="empty">
            No members found.
        </div>
    <?php
    else :
    ?>
        <div class="tbl-wrap">
            <table>
                <tr>
                    <th>
                        Member
                    </th>
                    <th>
                        Contact
                    </th>
                    <th>
                        Institution
                    </th>
                    <th>
                        Role
                    </th>
                    <th>
                        Status
                    </th>
                    <th>
                        Joined
                    </th>
                    <th>
                        Actions
                    </th>
                </tr>
                <?php
                foreach ($rows as $m) :
                ?>
                    <tr>
                        <td>
                            <div class="cell">
                                <span class="av">
                                    <?= e(initials($m['name'])) ?>
                                </span>
                                <strong>
                                    <?= e($m['name']) ?>
                                </strong>
                            </div>
                        </td>
                        <td>
                            <?= e($m['email']) ?>
                            <div class="small muted">
                                <?= e($m['phone']) ?>
                            </div>
                        </td>
                        <td>
                            <?= e($m['institution']) ?>
                        </td>
                        <td>
                            <?= badge($m['role']) ?>
                        </td>
                        <td>
                            <?= badge($m['status']) ?>
                        </td>
                        <td>
                            <?= date('d M Y', strtotime($m['created_at'])) ?>
                        </td>
                        <td>
                            <form method="post" class="acts">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                <?php
                                if ($m['status'] === 'active') :
                                ?>
                                    <button class="btn sec sm" name="act" value="block" data-confirm="Block this member?">
                                        Block
                                    </button>
                                <?php
                                else :
                                ?>
                                    <button class="btn green sm" name="act" value="unblock">
                                        Unblock
                                    </button>
                                <?php
                                endif;
                                ?>
                                <button class="btn red sm" name="act" value="delete" data-confirm="Delete this member and all their data?">
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
