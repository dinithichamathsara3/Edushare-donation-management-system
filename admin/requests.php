<?php
$title = 'Requests';
require_once __DIR__ . '/../includes/admin_header.php';
$rows = db()->query('SELECT r.*, b.title, s.name AS recipient, d.name AS donor FROM requests r JOIN items b ON b.id=r.item_id JOIN users s ON s.id=r.recipient_id JOIN users d ON d.id=b.donor_id ORDER BY r.created_at DESC LIMIT 200')->fetchAll();
?>
<div class="card">
    <h2>
        All requests
    </h2>
    <?php
    if (!$rows) :
    ?>
        <div class="empty">
            No requests have been made yet.
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
                        Recipient
                    </th>
                    <th>
                        Donor
                    </th>
                    <th>
                        Status
                    </th>
                    <th>
                        Requested
                    </th>
                    <th>
                        Last update
                    </th>
                </tr>
                <?php
                foreach ($rows as $r) :
                ?>
                    <tr>
                        <td>
                            <strong>
                                <?= e($r['title']) ?>
                            </strong>
                        </td>
                        <td>
                            <?= e($r['recipient']) ?>
                        </td>
                        <td>
                            <?= e($r['donor']) ?>
                        </td>
                        <td>
                            <?= badge($r['status']) ?>
                        </td>
                        <td>
                            <?= date('d M Y', strtotime($r['created_at'])) ?>
                        </td>
                        <td>
                            <?= ago($r['updated_at']) ?>
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
