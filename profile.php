<?php
$title = 'My profile';
require_once __DIR__ . '/includes/functions.php';
$u = require_user();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['info'])) {
        try {
            [$lat, $lng] = coordinates($_POST);
            db()->prepare('UPDATE users SET name=?,phone=?,address=?,province=?,district=?,city=?,latitude=?,longitude=? WHERE id=?')->execute([trim($_POST['name']), trim($_POST['phone']), trim($_POST['address']), trim($_POST['province'] ?? ''), trim($_POST['district'] ?? ''), trim($_POST['city'] ?? ''), $lat, $lng, $u['id']]);
        } catch (Throwable $e) {
            flash($e->getMessage(), 'err');
            redirect('profile.php');
        }
        $_SESSION['user']['name'] = trim($_POST['name']);
        flash('Profile saved.');
    } else {
        $s = db()->prepare('SELECT password FROM users WHERE id=?');
        $s->execute([$u['id']]);
        if (!password_verify($_POST['old'] ?? '', $s->fetchColumn())) flash('Current password is incorrect.', 'err');
        elseif (strlen($_POST['new'] ?? '') < 8) flash('New password needs at least 8 characters.', 'err');
        else {
            db()->prepare('UPDATE users SET password=? WHERE id=?')->execute([password_hash($_POST['new'], PASSWORD_DEFAULT), $u['id']]);
            flash('Password changed.');
        }
    }
    redirect('profile.php');
}
$s = db()->prepare('SELECT * FROM users WHERE id=?');
$s->execute([$u['id']]);
$me = $s->fetch();
require __DIR__ . '/includes/header.php';
?>
<div class="grid2" style="align-items:start">
    <form class="card" method="post">
        <h2>
            Personal details
        </h2>
        <?= csrf_field() ?>
        <input type="hidden" name="info" value="1">
        <div class="field">
            <label>
                Full name
            </label>
            <input name="name" required value="<?= e($me['name']) ?>">
        </div>
        <?php
        if ($me['profile_image']) :
        ?>
            <div>
                <?= cover($me['profile_image']) ?>
            </div>
        <?php
        endif;
        ?>
        <div class="field">
            <label>
                Email
            </label>
            <input value="<?= e($me['email']) ?>" disabled>
        </div>
        <div class="field">
            <label>
                Phone
            </label>
            <input name="phone" value="<?= e($me['phone']) ?>">
        </div>
        <div class="field">
            <label>
                School / university
            </label>
            <input value="<?= e($me['institution']) ?>" readonly>
        </div>
        <div class="field">
            <label>
                Address / city
            </label>
            <input name="address" value="<?= e($me['address']) ?>">
        </div>
        <div class="grid2">
            <?php
            foreach (["province" => "Province", "district" => "District", "city" => "City", "latitude" => "Latitude (optional)", "longitude" => "Longitude (optional)"] as $n => $label) echo field($n, $label, "text", $me[$n]);
            ?>
        </div>
        <button class="btn">
            Save changes
        </button>
    </form>
    <form class="card" method="post">
        <h2>
            Change password
        </h2>
        <?= csrf_field() ?>
        <div class="field">
            <label>
                Current password
            </label>
            <input type="password" name="old" required>
        </div>
        <div class="field">
            <label>
                New password
            </label>
            <input type="password" name="new" minlength="8" required>
        </div>
        <button class="btn sec">
            Update password
        </button>
        <p class="hint" style="margin-top:1rem">
            Member since
            <?= date('d M Y', strtotime($me['created_at'])) ?>
            ·
            <?= e(ucfirst($me['role'])) ?>
        </p>
    </form>
</div>
<?php
require __DIR__ . '/includes/footer.php';
?>
