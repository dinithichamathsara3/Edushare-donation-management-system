<?php
require_once __DIR__ . '/../includes/functions.php';
$u = require_user('recipient');
if (institution($u['id'])) redirect('recipient/verification.php');
$s = db()->prepare('SELECT * FROM users WHERE id=?');
$s->execute([$u['id']]);
$me = $s->fetch();
$title = 'Institution application';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        foreach (['institution', 'registration_number', 'contact_person', 'address', 'phone', 'province', 'district', 'city'] as $n) if (trim($_POST[$n] ?? '') === '') throw new RuntimeException('Complete every required institution field.');
        if (!in_array($_POST['institution_type'] ?? '', ['School', 'Piriven', 'Educational Organization', 'Other'])) throw new RuntimeException('Choose an institution type.');
        [$lat, $lng] = coordinates($_POST);
        $doc = upload_document();
        db()->beginTransaction();
        db()->prepare('INSERT INTO institutions(user_id,institution_type,registration_number,contact_person,description,verification_document) VALUES(?,?,?,?,?,?)')->execute([$u['id'], $_POST['institution_type'], trim($_POST['registration_number']), trim($_POST['contact_person']), trim($_POST['description'] ?? ''), $doc]);
        db()->prepare('UPDATE users SET institution=?,phone=?,address=?,province=?,district=?,city=?,latitude=?,longitude=? WHERE id=?')->execute([trim($_POST['institution']), trim($_POST['phone']), trim($_POST['address']), trim($_POST['province']), trim($_POST['district']), trim($_POST['city']), $lat, $lng, $u['id']]);
        admin_notice('Existing receiver submitted institution verification: ' . trim($_POST['institution']));
        db()->commit();
        flash('Application submitted.');
        redirect('recipient/verification.php');
    } catch (Throwable $e) {
        if (db()->inTransaction()) db()->rollBack();
        flash($e instanceof PDOException ? 'Registration number already exists or details could not be saved.' : $e->getMessage(), 'err');
    }
}
require __DIR__ . '/../includes/header.php';
?>
<form method="post" enctype="multipart/form-data" class="card">
    <h2>
        Apply as an institution
    </h2>
    <p>
        Individual receivers are not eligible. A school or approved educational organization must provide official details.
    </p>
    <?= csrf_field() ?>
    <?php
    foreach (['institution' => 'Institution name', 'registration_number' => 'Registration / identification number', 'contact_person' => 'Contact person', 'phone' => 'Contact number', 'address' => 'Address', 'province' => 'Province', 'district' => 'District', 'city' => 'City'] as $n => $label) echo field($n, $label, 'text', $_POST[$n] ?? $me[$n] ?? '', true);
    ?>
    <div class="field">
        <label>
            Institution type
        </label>
        <select name="institution_type">
            <?php
            foreach (['School', 'Piriven', 'Educational Organization', 'Other'] as $t) :
            ?>
                <option>
                    <?= e($t) ?>
                </option>
            <?php
            endforeach;
            ?>
        </select>
    </div>
    <?= field('latitude','Latitude (optional)') ?>
    <?= field('longitude','Longitude (optional)') ?>
    <div class="field">
        <label>
            Description
        </label>
        <textarea name="description">
        </textarea>
    </div>
    <div class="field">
        <label>
            Supporting document (PDF, JPG or PNG, max 5 MB)
        </label>
        <input type="file" name="verification_document" required accept="application/pdf,image/jpeg,image/png">
    </div>
    <button class="btn">
        Submit for verification
    </button>
</form>
<?php
require __DIR__ . '/../includes/footer.php';
?>
