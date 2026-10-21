<?php
require_once __DIR__ . '/../includes/functions.php';
$u = require_user('donor');
$id = (int)($_GET['id'] ?? 0);
$s = db()->prepare('SELECT * FROM items WHERE id=? AND donor_id=?');
$s->execute([$id, $u['id']]);
$b = $s->fetch();
if (!$b) {
    http_response_code(404);
    exit('Donation not found.');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        db()->beginTransaction();
        $s = db()->prepare('SELECT * FROM items WHERE id=? FOR UPDATE');
        $s->execute([$id]);
        $b = $s->fetch();
        $s = db()->prepare("SELECT COUNT(*) FROM requests WHERE item_id=? AND status IN ('pending','approved','completed')");
        $s->execute([$id]);
        if ($s->fetchColumn() || !in_array($b['status'], ['pending', 'approved', 'rejected'])) throw new RuntimeException('Only unclaimed donations can be edited.');
        [$lat, $lng] = coordinates($_POST);
        if (trim($_POST['title'] ?? '') === '' || (int)($_POST['quantity'] ?? 0) < 1) throw new RuntimeException('Enter an item name and positive quantity.');
        db()->prepare('UPDATE items SET title=?,quantity=?,description=?,subject=?,education_level=?,location=?,latitude=?,longitude=?,delivery_method=?,availability_date=? WHERE id=?')->execute([trim($_POST['title']), (int) $_POST['quantity'], trim($_POST['description'] ?? ''), trim($_POST['subject'] ?? ''), trim($_POST['education_level'] ?? ''), trim($_POST['location'] ?? ''), $lat, $lng, trim($_POST['delivery_method'] ?? ''), ($_POST['availability_date'] ?? '') ? : null, $id]);
        db()->commit();
        flash('Donation updated.');
        redirect('donor/my_items.php');
    } catch (Throwable $e) {
        if (db()->inTransaction()) db()->rollBack();
        flash($e->getMessage(), 'err');
    }
}
$title = 'Edit donation';
require __DIR__ . '/../includes/header.php';
?>
<form method="post" class="card">
    <?= csrf_field() ?>
    <?php
    foreach (['title' => 'Item name', 'quantity' => 'Quantity', 'description' => 'Description', 'subject' => 'Subject', 'education_level' => 'Education level', 'location' => 'Location', 'latitude' => 'Latitude', 'longitude' => 'Longitude', 'delivery_method' => 'Delivery / collection method', 'availability_date' => 'Available from'] as $n => $label) echo field($n, $label, $n === 'quantity' ? 'number' : ($n === 'availability_date' ? 'date' : 'text'), $b[$n], in_array($n, ['title', 'quantity']));
    ?>
    <button class="btn">
        Save donation
    </button>
</form>
<?php
require __DIR__ . '/../includes/footer.php';
?>
