<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$title = 'Activity monitoring';
$checks = ['Institutions with many requests (review flags, not proof of fraud)' => "SELECT u.institution label,COUNT(r.id) total FROM users u JOIN requests r ON r.recipient_id=u.id WHERE r.created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY) GROUP BY u.id,u.institution HAVING COUNT(r.id)>=10", 'Repeated cancellations in the last 30 days' => "SELECT u.institution label,COUNT(r.id) total FROM users u JOIN requests r ON r.recipient_id=u.id WHERE r.status='cancelled' AND r.updated_at>=DATE_SUB(NOW(),INTERVAL 30 DAY) GROUP BY u.id,u.institution HAVING COUNT(r.id)>=3", 'Frequent urgent requirements in the last 7 days' => "SELECT u.institution label,COUNT(n.id) total FROM users u JOIN requirements n ON n.recipient_id=u.id WHERE n.urgency='high' AND n.created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY) GROUP BY u.id,u.institution HAVING COUNT(n.id)>=5", 'Possible duplicate institution names' => "SELECT institution label,COUNT(*) total FROM users WHERE role='recipient' AND institution<>'' GROUP BY institution HAVING COUNT(*)>1", 'Frequent donor listings in the last 7 days' => "SELECT u.name label,COUNT(b.id) total FROM users u JOIN items b ON b.donor_id=u.id WHERE b.created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY) GROUP BY u.id,u.name HAVING COUNT(b.id)>=20"];
require __DIR__ . '/../includes/admin_header.php';
foreach ($checks as $label => $sql) : $rows = db()->query($sql)->fetchAll();
?>
<div class="card">
    <h2>
        <?= e($label) ?>
    </h2>
    <?php
    if (!$rows) :
    ?>
        <p>
            No activity flags currently.
        </p>
    <?php
    else :
    ?>
        <table>
            <tr>
                <th>
                    Account / institution
                </th>
                <th>
                    Count
                </th>
            </tr>
            <?php
            foreach ($rows as $r) :
            ?>
                <tr>
                    <td>
                        <?= e($r['label']) ?>
                    </td>
                    <td>
                        <?= (int)$r['total'] ?>
                    </td>
                </tr>
            <?php
            endforeach;
            ?>
        </table>
        <a href="<?= url('admin/users.php') ?>">
            Review accounts
        </a>
    <?php
    endif;
    ?>
</div>
<?php
endforeach;
require __DIR__ . '/../includes/footer.php';
?>
