<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$title = 'Reports and analytics';
$reports = ['Donation status' => "SELECT status label,COUNT(*) total FROM items GROUP BY status", 'Institution verification' => "SELECT verification_status label,COUNT(*) total FROM institutions GROUP BY verification_status", 'Most donated categories' => "SELECT c.name label,COALESCE(SUM(r.quantity),0) total FROM categories c LEFT JOIN items b ON b.category_id=c.id LEFT JOIN requests r ON r.item_id=b.id AND r.status='completed' GROUP BY c.id,c.name", 'Donations by district' => "SELECT COALESCE(NULLIF(u.district,''),'Unspecified') label,COUNT(b.id) total FROM users u JOIN items b ON b.donor_id=u.id GROUP BY u.district", 'Donations by city' => "SELECT COALESCE(NULLIF(u.city,''),'Unspecified') label,COUNT(b.id) total FROM users u JOIN items b ON b.donor_id=u.id GROUP BY u.city", 'Requirements by category' => "SELECT c.name label,COUNT(n.id) total FROM categories c LEFT JOIN requirements n ON n.category_id=c.id GROUP BY c.id,c.name", 'Urgent vs normal requirements' => "SELECT urgency label,COUNT(*) total FROM requirements GROUP BY urgency", 'Urgent requirement progress' => "SELECT CONCAT(urgency_status,' / ',status) label,COUNT(*) total FROM requirements WHERE urgency='high' GROUP BY urgency_status,status", 'Donations per month' => "SELECT DATE_FORMAT(created_at,'%Y-%m') label,COUNT(*) total FROM items GROUP BY DATE_FORMAT(created_at,'%Y-%m') ORDER BY label", 'Claims and offers' => "SELECT status label,COUNT(*) total FROM requests GROUP BY status", 'Completed vs pending donations' => "SELECT CASE WHEN status='completed' THEN 'Completed' WHEN status IN ('pending','approved') THEN 'Pending / active' ELSE 'Cancelled / rejected' END label,COUNT(*) total FROM requests GROUP BY label"];
$data = [];
foreach ($reports as $label => $sql) $data[$label] = db()->query($sql)->fetchAll();
if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="edushare-reports.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Report', 'Group', 'Count']);
    foreach ($data as $label => $rows) foreach ($rows as $r) {
        $value = $r['label'];
        if (preg_match('/^[=+@\-]/', $value)) $value = "'" . $value;
        fputcsv($out, [$label, $value, $r['total']]);
    }
    fclose($out);
    exit;
}
require __DIR__ . '/../includes/admin_header.php';
?>
<p>
    Calculated from saved records.
    <a class="btn" href="?export=csv">
        Export reports as CSV
    </a>
</p>
<div class="grid2">
    <?php
    foreach ($data as $label => $rows) : $max = max([1, ... array_column($rows, 'total')]);
    ?>
    <article class="card">
        <h2>
            <?= e($label) ?>
        </h2>
        <?php
        if (!$rows) :
        ?>
            <p>
                No data yet.
            </p>
        <?php
        endif;
        ?>
        <?php
        foreach ($rows as $r) :
        ?>
            <div class="chart-row">
                <span>
                    <?= e($r['label']) ?>
                </span>
                <div class="report-bar" style="width:<?= round(100*$r['total']/$max,2) ?>%" role="img" aria-label="<?= e($r['label'].': '.$r['total']) ?>">
                </div>
                <strong>
                    <?= (int)$r['total'] ?>
                </strong>
            </div>
        <?php
        endforeach;
        ?>
    </article>
<?php
endforeach;
?>
</div>
<?php
require __DIR__ . '/../includes/footer.php';
?>
