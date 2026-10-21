<?php
require_once __DIR__ . '/includes/functions.php';
$isAdmin = (bool) admin();
$u = $isAdmin ? null : require_user();
if ($u && $u['role'] === 'recipient') require_receiver();
$title = $isAdmin ? 'Claims and donation history' : 'Donation offers, claims and tracking';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        $s = db()->prepare('SELECT r.*,b.donor_id,b.title FROM requests r JOIN items b ON b.id=r.item_id WHERE r.id=?');
        $s->execute([(int)($_POST['id'] ?? 0)]);
        $r = $s->fetch();
        if (!$r || (!$isAdmin && $r['recipient_id'] != $u['id'] && $r['donor_id'] != $u['id'])) throw new RuntimeException('Request unavailable.');
        $donor = $isAdmin || ($u['role'] === 'donor' && $r['donor_id'] == $u['id']);
        $receiver = $isAdmin || ($u['role'] === 'recipient' && $r['recipient_id'] == $u['id']);
        $decider = $r['source'] === 'offer' ? $receiver : $donor;
        $act = $_POST['act'] ?? '';
        if ($act === 'accept' && $decider) accept_request((int) $r['id']);
        elseif ($act === 'reject' && $decider && $r['status'] === 'pending') {
            db()->prepare("UPDATE requests SET status='rejected' WHERE id=? AND status='pending'")->execute([$r['id']]);
            record_stage((int) $r['id'], 'rejected');
            notify((int)($r['source'] === 'offer' ? $r['donor_id'] : $r['recipient_id']), 'Donation request declined: ' . $r['title'], 'workflow.php');
        } elseif ($act === 'more_info' && $decider && $r['status'] === 'pending') {
            $note = mb_substr(trim($_POST['note'] ?? ''), 0, 255);
            if ($note === '') throw new RuntimeException('Enter the information you need.');
            db()->prepare("UPDATE requests SET response_note=? WHERE id=? AND status='pending'")->execute([$note, $r['id']]);
            record_stage((int) $r['id'], 'more information', $note);
            notify((int)($r['source'] === 'offer' ? $r['donor_id'] : $r['recipient_id']), 'More information requested: ' . $note, 'workflow.php');
        } elseif ($act === 'reply' && $r['status'] === 'pending' && (!$decider || $isAdmin)) {
            $note = mb_substr(trim($_POST['note'] ?? ''), 0, 255);
            if ($note === '') throw new RuntimeException('Enter your reply.');
            db()->prepare("UPDATE requests SET message=?,response_note=NULL WHERE id=? AND status='pending'")->execute([$note, $r['id']]);
            record_stage((int) $r['id'], 'information supplied', $note);
            notify((int)($r['source'] === 'offer' ? $r['recipient_id'] : $r['donor_id']), 'Information supplied: ' . $r['title'], 'workflow.php');
        } elseif ($act === 'cancel' && $r['status'] === 'pending') {
            db()->prepare("UPDATE requests SET status='cancelled' WHERE id=? AND status='pending'")->execute([$r['id']]);
            record_stage((int) $r['id'], 'cancelled');
        } elseif ($act === 'completed' && $receiver) complete_request((int) $r['id']);
        elseif (in_array($act, ['preparing', 'ready', 'delivered']) && $donor && $r['status'] === 'approved') {
            $next = ['accepted' => 'preparing', 'preparing' => 'ready', 'ready' => 'delivered'];
            if (($next[$r['stage']] ?? '') !== $act) throw new RuntimeException('Follow the collection / delivery steps in order.');
            $s = db()->prepare("UPDATE requests SET stage=? WHERE id=? AND status='approved' AND stage=?");
            $s->execute([$act, $r['id'], $r['stage']]);
            if (!$s->rowCount()) throw new RuntimeException('Status changed. Refresh and try again.');
            record_stage((int) $r['id'], $act);
            notify((int) $r['recipient_id'], 'Donation ' . $act . ': ' . $r['title'], 'workflow.php');
        } else throw new RuntimeException('This action is unavailable.');
        if ($isAdmin) admin_log('Donation request #' . $r['id'] . ' ' . $act);
        flash('Donation updated.');
    } catch (Throwable $e) {
        flash($e->getMessage(), 'err');
    }
    redirect('workflow.php');
}
$sql = 'SELECT r.*,b.title,b.delivery_method,d.name donor,d.phone donor_phone,d.email donor_email,u.institution,u.phone receiver_phone,u.email receiver_email FROM requests r JOIN items b ON b.id=r.item_id JOIN users d ON d.id=b.donor_id JOIN users u ON u.id=r.recipient_id';
$p = [];
if (!$isAdmin) {
    $sql .= $u['role'] === 'donor' ? ' WHERE b.donor_id=?' : ' WHERE r.recipient_id=?';
    $p[] = $u['id'];
}
$s = db()->prepare($sql . ' ORDER BY r.created_at DESC');
$s->execute($p);
$rows = $s->fetchAll();
if ($isAdmin) require __DIR__ . '/includes/admin_header.php';
else require __DIR__ . '/includes/header.php';
if (!$rows) echo '<div class="empty">No claims or offers yet.</div>';
foreach ($rows as $r) : $donor = $isAdmin || $u['role'] === 'donor';
$receiver = $isAdmin || $u['role'] === 'recipient';
$decider = $r['source'] === 'offer' ? $receiver : $donor;
?>
<article class="card">
    <h2>
        <?= e($r['title']) ?>
        ·
        <?= (int)$r['quantity'] ?>
        items
        <?= badge($r['status']) ?>
    </h2>
    <p>
        <?= e($r['donor'].' → '.$r['institution'].' · '.$r['source']) ?>
    </p>
    <p>
        <?= e($r['message']) ?>
    </p>
    <?php
    if ($r['response_note']) :
    ?>
        <p>
            Information requested:
            <?= e($r['response_note']) ?>
        </p>
    <?php
    endif;
    ?>
    <?php
    if (in_array($r['status'], ['approved', 'completed'])) :
    ?>
        <p>
            Donor:
            <?= e($r['donor_phone'].' · '.$r['donor_email']) ?>
            · Institution:
            <?= e($r['receiver_phone'].' · '.$r['receiver_email']) ?>
            ·
            <?= e($r['delivery_method']) ?>
        </p>
        <ol class="progress-steps">
            <?php
            $steps = ['accepted' => 'Accepted / claimed', 'preparing' => 'Preparing', 'ready' => 'Ready for collection / delivery', 'delivered' => 'Collected / delivered', 'completed' => 'Completed'];
            $keys = array_keys($steps);
            foreach ($steps as $k => $label) :
            ?>
                <li class="<?= array_search($k,$keys)<=array_search($r['stage'],$keys)?'done':'' ?>">
                    <?= e($label) ?>
                </li>
            <?php
            endforeach;
            ?>
        </ol>
    <?php
    endif;
    ?>
    <form method="post" class="acts">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $r['id'] ?>">
        <?php
        if ($r['status'] === 'pending') :
        ?>
            <?php
            if ($decider) :
            ?>
                <button class="btn" name="act" value="accept">
                    Accept
                </button>
                <button class="btn sec" name="act" value="reject">
                    Reject
                </button>
                <input name="note" placeholder="Information needed">
                <button class="btn sec" name="act" value="more_info">
                    Request more information
                </button>
            <?php
            else :
            ?>
                <input name="note" placeholder="Supply requested information">
                <button class="btn sec" name="act" value="reply">
                    Send reply
                </button>
            <?php
            endif;
            ?>
            <button class="btn sec" name="act" value="cancel">
                Cancel pending request
            </button>
        <?php
        elseif ($r['status'] === 'approved') :
        ?>
            <?php
            if ($donor && $r['stage'] !== 'delivered') : $next = ['accepted' => 'preparing', 'preparing' => 'ready', 'ready' => 'delivered'][$r['stage']] ?? null;
            if ($next) :
            ?>
                <button class="btn" name="act" value="<?= $next ?>">
                    Mark
                    <?= e($steps[$next]) ?>
                </button>
            <?php
            endif;
            ?>
        <?php
        elseif ($receiver && $r['stage'] === 'delivered') :
        ?>
            <button class="btn" name="act" value="completed">
                Confirm receipt and complete
            </button>
        <?php
        endif;
        ?>
    <?php
    endif;
    ?>
</form>
<details>
    <summary>
        Status history
    </summary>
    <ul>
        <?php
        $s = db()->prepare('SELECT * FROM tracking WHERE request_id=? ORDER BY id');
        $s->execute([$r['id']]);
        foreach ($s->fetchAll() as $h) :
        ?>
            <li>
                <?= e($h['created_at'].' · '.$h['status'].' · '.$h['actor'].' · '.$h['remarks']) ?>
            </li>
        <?php
        endforeach;
        ?>
    </ul>
</details>
</article>
<?php
endforeach;
require __DIR__ . '/includes/footer.php';
?>
