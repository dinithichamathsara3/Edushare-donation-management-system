<?php
require_once __DIR__ . '/includes/functions.php';
$u = user();
$title = 'Institution needs';
$item = null;
$me = null;
if ($u) {
    $s = db()->prepare('SELECT * FROM users WHERE id=?');
    $s->execute([$u['id']]);
    $me = $s->fetch();
}
if (!empty($_GET['item']) && $u && $u['role'] === 'donor') {
    $s = db()->prepare('SELECT * FROM items WHERE id=? AND donor_id=?');
    $s->execute([(int) $_GET['item'], $u['id']]);
    $item = $s->fetch() ? : null;
}
$sql = "SELECT n.*,u.institution,u.city,u.district,u.latitude,u.longitude,c.name category FROM requirements n JOIN users u ON u.id=n.recipient_id JOIN institutions v ON v.user_id=u.id JOIN categories c ON c.id=n.category_id WHERE n.status='open' AND v.verification_status='approved' AND u.status='active'";
$p = [];
if (!empty($_GET['urgent'])) $sql .= " AND n.urgency='high' AND n.urgency_status='approved'";
foreach (['q' => 'n.title', 'subject' => 'n.subject', 'education_level' => 'n.education_level'] as $key => $col) if (trim($_GET[$key] ?? '') !== '') {
    $sql .= " AND $col LIKE ?";
    $p[] = '%' . trim($_GET[$key]) . '%';
}
if (!empty($_GET['category'])) {
    $sql .= ' AND n.category_id=?';
    $p[] = (int) $_GET['category'];
}
if (!empty($_GET['location'])) {
    $sql .= ' AND (u.city LIKE ? OR u.district LIKE ?)';
    $p[] = '%' . trim($_GET['location']) . '%';
    $p[] = '%' . trim($_GET['location']) . '%';
}
if (!empty($_GET['required_date'])) {
    $sql .= ' AND n.required_date<=?';
    $p[] = $_GET['required_date'];
}
$s = db()->prepare($sql . ' ORDER BY n.created_at DESC');
$s->execute($p);
$rows = [];
$radius = (float)($_GET['radius'] ?? 0);
foreach ($s->fetchAll() as $r) {
    $r['remaining'] = remaining($r);
    if ($r['remaining'] < max(1, (int)($_GET['quantity'] ?? 1))) continue;
    $r['distance'] = distance($item['latitude'] ?? $me['latitude'] ?? null, $item['longitude'] ?? $me['longitude'] ?? null, $r['latitude'], $r['longitude']);
    if ($radius > 0 && ($r['distance'] === null || $r['distance'] > $radius)) continue;
    $r['score'] = 0;
    if ($item) {
        $r['score'] += ($r['category_id'] == $item['category_id'] ? 40 : 0);
        foreach (['subject', 'education_level'] as $key) if (!empty($item[$key]) && strcasecmp(trim($r[$key] ?? ''), trim($item[$key])) === 0) $r['score'] += 15;
        if (stripos($r['title'], $item['title']) !== false || stripos($item['title'], $r['title']) !== false) $r['score'] += 10;
        if ($r['distance'] !== null && $r['distance'] <= 25) $r['score'] += 10;
        elseif ($me && !empty($me['district']) && strcasecmp($me['district'], $r['district'] ?? '') === 0) $r['score'] += 5;
        if (available($item) >= $r['remaining']) $r['score'] += 5;
        if ($r['urgency_status'] === 'approved') $r['score'] += 5;
    }
    $rows[] = $r;
}
if ($item) usort($rows, fn($a, $b) => $b['score'] <=> $a['score']);
elseif ($radius > 0) usort($rows, fn($a, $b) => $a['distance'] <=> $b['distance']);
$cats = db()->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$mine = [];
if ($u && $u['role'] === 'donor') {
    $s = db()->prepare("SELECT * FROM items WHERE donor_id=? AND status='approved'");
    $s->execute([$u['id']]);
    $mine = $s->fetchAll();
}
if ($u) require __DIR__ . '/includes/header.php';
else require __DIR__ . '/includes/public_header.php';
?>
<div class="card">
    <h2>
        <?= !empty($_GET['urgent'])?'Urgent donation needs':'Browse requirements / nearby schools' ?>
    </h2>
    <form method="get" class="grid2">
        <?php
        foreach (['q' => 'Item name', 'subject' => 'Subject', 'education_level' => 'Education level', 'location' => 'City / district', 'quantity' => 'Minimum remaining quantity', 'required_date' => 'Required by'] as $n => $label) echo field($n, $label, $n === 'quantity' ? 'number' : ($n === 'required_date' ? 'date' : 'text'), $_GET[$n] ?? '');
        ?>
        <div class="field">
            <label>
                Category
            </label>
            <select name="category">
                <option value="">
                    Any
                </option>
                <?php
                foreach ($cats as $c) :
                ?>
                    <option value="<?= $c['id'] ?>" <?= ($_GET['category']??'')==$c['id']?'selected':'' ?>>
                        <?= e($c['name']) ?>
                    </option>
                <?php
                endforeach;
                ?>
            </select>
        </div>
        <div class="field">
            <label>
                Distance
            </label>
            <select name="radius">
                <?php
                foreach ([0 => 'Any location', 5 => 'Within 5 km', 10 => 'Within 10 km', 25 => 'Within 25 km'] as $v => $label) :
                ?>
                    <option value="<?= $v ?>" <?= $radius==$v?'selected':'' ?>>
                        <?= e($label) ?>
                    </option>
                <?php
                endforeach;
                ?>
            </select>
        </div>
        <?php
        if ($mine) :
        ?>
            <div class="field">
                <label>
                    Match my donation
                </label>
                <select name="item">
                    <option value="">
                        All needs
                    </option>
                    <?php
                    foreach ($mine as $b) :
                    ?>
                        <option value="<?= $b['id'] ?>" <?= ($item['id']??0)==$b['id']?'selected':'' ?>>
                            <?= e($b['title']) ?>
                        </option>
                    <?php
                    endforeach;
                    ?>
                </select>
            </div>
        <?php
        endif;
        ?>
        <label>
            <input type="checkbox" name="urgent" value="1" <?= !empty($_GET['urgent'])?'checked':'' ?>>
            Approved urgent only
        </label>
        <button class="btn">
            Search / match
        </button>
    </form>
    <p class="small muted">
        Distances require both locations' coordinates. Without coordinates, use city or district filters. Matching scores compare category, name, subject, grade, location, quantity and approved urgency.
    </p>
</div>
<?php
if (!$rows) echo '<div class="empty">No requirements match these filters.</div>';
foreach ($rows as $r) :
?>
    <article class="card">
        <h2>
            <?= e($r['title']) ?>
            <?= $r['urgency_status']==='approved'?badge('urgent'):'' ?>
        </h2>
        <p>
            <?= e($r['institution'].' · '.$r['city'].' / '.$r['district']) ?>
            <?= $r['distance']!==null?' · '.e($r['distance']).' km':'' ?>
        </p>
        <p>
            <?= e($r['category'].' · '.$r['subject'].' · '.$r['education_level']) ?>
        </p>
        <p>
            Required
            <?= $r['quantity_required'] ?>
            · Received
            <?= $r['quantity_received'] ?>
            · Remaining to allocate
            <?= $r['remaining'] ?>
            · Due
            <?= e($r['required_date']) ?>
            <?= $item?' · Match score '.(int)$r['score']:'' ?>
        </p>
        <p>
            <?= e($r['description']) ?>
        </p>
        <p>
            Reason:
            <?= e($r['reason']) ?>
        </p>
        <?php
        if ($mine) :
        ?>
            <form method="post" action="<?= url('donor/offer.php') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="requirement_id" value="<?= $r['id'] ?>">
                <div class="grid2">
                    <div class="field">
                        <label>
                            Your donation
                        </label>
                        <select name="item_id">
                            <?php
                            foreach ($mine as $b) : if (available($b) < 1) continue;
                            ?>
                            <option value="<?= $b['id'] ?>" <?= ($item['id']??0)==$b['id']?'selected':'' ?>>
                                <?= e($b['title']) ?>
                                (
                                <?= available($b) ?>
                                available)
                            </option>
                        <?php
                        endforeach;
                        ?>
                    </select>
                </div>
                <?= field('quantity','Quantity offered','number',min($r['remaining'],$item?available($item):1),true) ?>
            </div>
            <?= field('message','Message to institution') ?>
            <button class="btn">
                Offer donation
            </button>
        </form>
    <?php
    elseif (!$u) :
    ?>
        <a class="btn" href="<?= url('login.php') ?>">
            Login to donate
        </a>
    <?php
    endif;
    ?>
</article>
<?php
endforeach;
if ($u) require __DIR__ . '/includes/footer.php';
else require __DIR__ . '/includes/public_footer.php';
?>
