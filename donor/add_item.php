<?php
$title = 'Donate an item';
require_once __DIR__ . '/../includes/functions.php';
$u = require_user('donor');
$cats = db()->query('SELECT * FROM categories ORDER BY name')->fetchAll();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $t = trim($_POST['title'] ?? '');
    $a = trim($_POST['brand'] ?? '');
    if ($t === '' || (int)($_POST['quantity'] ?? 0) < 1 || trim($_POST['location'] ?? '') === '' || trim($_POST['delivery_method'] ?? '') === '') {
        flash('Enter item name, a positive quantity, location and delivery / collection method.', 'err');
    } else {
        try {
            [$lat, $lng] = coordinates($_POST);
            if (!empty($_POST['availability_date'])) {
                $dt = DateTime::createFromFormat('!Y-m-d', $_POST['availability_date']);
                if (!$dt || $dt->format('Y-m-d') !== $_POST['availability_date']) throw new RuntimeException('Enter a valid availability date.');
            }
        } catch (Throwable $ex) {
            flash($ex->getMessage(), 'err');
            redirect('donor/add_item.php');
        }
        $img = upload_image('image');
        db()->prepare('INSERT INTO items (donor_id,category_id,item_type,title,brand,quantity,description,condition_status,type,image) VALUES (?,?,?,?,?,?,?,?,?,?)')->execute([$u['id'], (int) $_POST['category_id'], $_POST['item_type'] === 'book' ? 'book' : 'education_item', $t, $a, max(1, (int)($_POST['quantity'] ?? 1)), trim($_POST['description'] ?? ''), in_array($_POST['condition_status'], ['New', 'Good', 'Fair']) ? $_POST['condition_status'] : 'Good', $_POST['type'] === 'exchange' ? 'exchange' : 'donate', $img]);
        $id = (int) db()->lastInsertId();
        db()->prepare("UPDATE items SET status='approved',subject=?,education_level=?,location=?,latitude=?,longitude=?,delivery_method=?,availability_date=? WHERE id=?")->execute([trim($_POST['subject'] ?? ''), trim($_POST['education_level'] ?? ''), trim($_POST['location'] ?? ''), $lat, $lng, trim($_POST['delivery_method'] ?? ''), ($_POST['availability_date'] ?? '') ? : null, $id]);
        matching_notices($id);
        $count = db()->prepare('SELECT COUNT(*) FROM items WHERE donor_id=? AND created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)');
        $count->execute([$u['id']]);
        if ((int) $count->fetchColumn() === 20) admin_notice('Review frequent donor listings: ' . $u['name']);
        flash('Donation is available. Browse requirements to find matching institutions.');
        redirect('donor/my_items.php');
    }
}
require __DIR__ . '/../includes/header.php';
?>
<form class="card" method="post" enctype="multipart/form-data" style="max-width:760px">
    <h2>
        Item details
    </h2>
    <?= csrf_field() ?>
    <!-- ITEM PHOTO: uploaded here, stored in uploads/items/ and shown automatically on every card -->
    <div class="upload field">
        <div class="cover ph" id="cover-prev">
            <small>
                Preview
            </small>
        </div>
        <div>
            <label for="cover-input">
                Cover photo (JPG, PNG or WebP, max 3 MB)
            </label>
            <input id="cover-input" type="file" name="image" accept="image/*">
        </div>
    </div>
    <div class="grid2">
        <div class="field">
            <label>
                This is a
            </label>
            <select name="item_type">
                <option value="book">
                    Book
                </option>
                <option value="education_item">
                    Education item (stationery, uniform, calculator…)
                </option>
            </select>
        </div>
        <div class="field">
            <label>
                Quantity
            </label>
            <input type="number" name="quantity" min="1" value="1">
        </div>
    </div>
    <div class="grid2">
        <div class="field">
            <label>
                Title / item name
            </label>
            <input name="title" required>
        </div>
        <div class="field">
            <label>
                Author / brand
            </label>
            <input name="brand">
        </div>
    </div>
    <div class="grid2">
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
                Condition
            </label>
            <select name="condition_status">
                <option>
                    New
                </option>
                <option selected>
                    Good
                </option>
                <option>
                    Fair
                </option>
            </select>
        </div>
    </div>
    <div class="field">
        <label>
            Offer as
        </label>
        <select name="type">
            <option value="donate">
                Free donation
            </option>
            <option value="exchange">
                Exchange for another book
            </option>
        </select>
    </div>
    <div class="field">
        <label>
            Notes for recipients
        </label>
        <textarea name="description" placeholder="Edition, year, any highlighting or missing pages">
        </textarea>
    </div>
    <div class="grid2">
        <?= field("subject","Subject") ?>
        <?= field("education_level","Education level / grade") ?>
        <?= field("location","Donor city / district","text","",true) ?>
        <?= field("latitude","Latitude (optional)") ?>
        <?= field("longitude","Longitude (optional)") ?>
        <?= field("delivery_method","Preferred delivery / collection method","text","Collection",true) ?>
        <?= field("availability_date","Available from","date") ?>
    </div>
    <button class="btn">
        List donation
    </button>
</form>
<?php
require __DIR__ . '/../includes/footer.php';
?>
