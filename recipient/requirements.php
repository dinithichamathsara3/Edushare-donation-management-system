<?php
require_once __DIR__ . '/../includes/functions.php';
$u = require_receiver();
$title = 'My requirements';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        if (isset($_POST['cancel'])) {
            $id = (int) $_POST['cancel'];
            db()->beginTransaction();
            $s = db()->prepare('SELECT * FROM requirements WHERE id=? AND recipient_id=? FOR UPDATE');
            $s->execute([$id, $u['id']]);
            $n = $s->fetch();
            $s = db()->prepare("SELECT COUNT(*) FROM requests WHERE requirement_id=? AND status='approved'");
            $s->execute([$id]);
            if (!$n || $n['status'] !== 'open' || $s->fetchColumn()) throw new RuntimeException('Requirements with accepted donations cannot be cancelled.');
            db()->prepare("UPDATE requirements SET status='cancelled' WHERE id=?")->execute([$id]);
            db()->commit();
            flash('Requirement cancelled.');
        } else {
            $qty = (int)($_POST['quantity_required'] ?? 0);
            $date = $_POST['required_date'] ?? '';
            $parsed = DateTime::createFromFormat('!Y-m-d', $date);
            if (trim($_POST['title'] ?? '') === '' || $qty < 1 || !$parsed || $parsed->format('Y-m-d') !== $date || $date < date('Y-m-d') || trim($_POST['reason'] ?? '') === '') throw new RuntimeException('Enter item name, positive quantity, reason and a future required date.');
            $urgent = ($_POST['urgency'] ?? 'normal') === 'high';
            db()->prepare('INSERT INTO requirements(recipient_id,category_id,title,subject,education_level,quantity_required,description,reason,required_date,urgency,urgency_status) VALUES(?,?,?,?,?,?,?,?,?,?,?)')->execute([$u['id'], (int) $_POST['category_id'], trim($_POST['title']), trim($_POST['subject'] ?? ''), trim($_POST['education_level'] ?? ''), $qty, trim($_POST['description'] ?? ''), trim($_POST['reason']), $date, $urgent ? 'high' : 'normal', $urgent ? 'pending' : 'not_required']);
            if ($urgent) admin_notice('Urgent requirement awaiting approval: ' . trim($_POST['title']));
            $list = db()->prepare("SELECT id FROM items WHERE category_id=? AND status='approved'");
            $list->execute([(int) $_POST['category_id']]);
            foreach ($list->fetchAll(PDO::FETCH_COLUMN) as $itemId) matching_notices((int) $itemId);
            flash('Requirement created. Urgent priority appears publicly only after admin approval.');
        }
        redirect('recipient/requirements.php');
    } catch (Throwable $e) {
        if (db()->inTransaction()) db()->rollBack();
        flash($e->getMessage(), 'err');
    }
}
$cats = db()->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$s = db()->prepare('SELECT * FROM requirements WHERE recipient_id=? ORDER BY id DESC');
$s->execute([$u['id']]);
$rows = $s->fetchAll();
require __DIR__ . '/../includes/header.php';
?>
<details class="card" open>
    <summary>
        Create requirement
    </summary>
    <form method="post">
        <?= csrf_field() ?>
        <div class="grid2">
            <?= field('title','Item name','text','',true) ?>
            <?= field('quantity_required','Quantity required','number',1,true) ?>
            <?= field('subject','Subject') ?>
            <?= field('education_level','Education level / grade') ?>
            <?= field('required_date','Required before','date','',true) ?>
        </div>
        <div class="field">
            <label>
                Category
            </label>
            <select name="category_id">
                <?php
                foreach ($cats as $c) :
                ?>
                    <option value="<?= $c['id'] ?>">
                        <?= e($c['name']) ?>
                    </option>
                <?php
                endforeach;
                ?>
            </select>
        </div>
        <div class="field">
            <label>
                Priority
            </label>
            <select name="urgency">
                <option value="normal">
                    Normal
                </option>
                <option value="high">
                    Urgent (requires review)
                </option>
            </select>
        </div>
        <div class="field">
            <label>
                Description
            </label>
            <textarea name="description">
            </textarea>
        </div>
        <div class="field">
            <label>
                Reason for requirement
            </label>
            <textarea name="reason" required>
            </textarea>
        </div>
        <p>
            Location comes from your institution profile.
        </p>
        <button class="btn">
            Create requirement
        </button>
    </form>
</details>
<?php
if (!$rows) echo '<div class="empty">No requirements yet.</div>';
foreach ($rows as $r) :
?>
    <article class="card">
        <h2>
            <?= e($r['title']) ?>
            <?= badge($r['status']) ?>
        </h2>
        <p>
            Required:
            <?= (int)$r['quantity_required'] ?>
            · Received:
            <?= (int)$r['quantity_received'] ?>
            · Remaining to allocate:
            <?= remaining($r) ?>
            · Needed by:
            <?= e($r['required_date']) ?>
        </p>
        <progress max="<?= $r['quantity_required'] ?>" value="<?= min($r['quantity_required'],$r['quantity_received']) ?>">
        </progress>
        <p>
            Urgency review:
            <?= badge($r['urgency_status']) ?>
        </p>
        <?php
        if ($r['status'] === 'open') :
        ?>
            <a class="btn sec" href="<?= url('recipient/edit_requirement.php?id='.$r['id']) ?>">
                Edit
            </a>
            <form method="post">
                <?= csrf_field() ?>
                <button class="btn sec" name="cancel" value="<?= $r['id'] ?>" data-confirm="Cancel this requirement?">
                    Cancel requirement
                </button>
            </form>
        <?php
        endif;
        ?>
    </article>
<?php
endforeach;
require __DIR__ . '/../includes/footer.php';
?>
