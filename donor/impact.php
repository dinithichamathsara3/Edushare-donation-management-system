<?php
require_once __DIR__ . '/../includes/functions.php';
$u = require_user('donor');
$title = 'My donation impact';
$s = db()->prepare("SELECT COUNT(*) donations,COALESCE(SUM(r.quantity),0) items,COUNT(DISTINCT r.recipient_id) institutions FROM requests r JOIN items b ON b.id=r.item_id WHERE b.donor_id=? AND r.status='completed'");
$s->execute([$u['id']]);
$stats = $s->fetch();
$s = db()->prepare("SELECT c.name, SUM(r.quantity) quantity FROM requests r JOIN items b ON b.id=r.item_id JOIN categories c ON c.id=b.category_id WHERE b.donor_id=? AND r.status='completed' GROUP BY c.id,c.name");
$s->execute([$u['id']]);
$rows = $s->fetchAll();
require __DIR__ . '/../includes/header.php';
?>
<div class="stats">
    <?php
    foreach ($stats as $k => $v) :
    ?>
        <div class="stat">
            <b>
                <?= (int)$v ?>
            </b>
            <span>
                <?= e(ucfirst($k)) ?>
            </span>
        </div>
    <?php
    endforeach;
    ?>
</div>
<div class="card">
    <h2>
        Items donated by category
    </h2>
    <?php
    if (!$rows) :
    ?>
        <p>
            Complete your first donation to see its impact here.
        </p>
    <?php
    else :
    ?>
        <table>
            <tr>
                <th>
                    Category
                </th>
                <th>
                    Items delivered
                </th>
            </tr>
            <?php
            foreach ($rows as $r) :
            ?>
                <tr>
                    <td>
                        <?= e($r['name']) ?>
                    </td>
                    <td>
                        <?= (int)$r['quantity'] ?>
                    </td>
                </tr>
            <?php
            endforeach;
            ?>
        </table>
        <p>
            Your donations help institutions provide better learning resources to students.
        </p>
    <?php
    endif;
    ?>
</div>
<?php
require __DIR__ . '/../includes/footer.php';
?>
