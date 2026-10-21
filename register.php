<?php
require_once __DIR__ . '/includes/functions.php';
if (user()) redirect(user()['role'] . '/');
if (admin()) redirect('admin/');
$err = '';
$v = $_POST;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($v['name'] ?? '');
    $email = trim($v['email'] ?? '');
    $role = in_array($v['role'] ?? '', ['recipient', 'donor']) ? $v['role'] : 'recipient';
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'Enter your name and a valid email address.';
    elseif (strlen($v['password'] ?? '') < 8) $err = 'Password needs at least 8 characters.';
    elseif ($v['password'] !== ($v['confirm'] ?? '')) $err = 'Passwords do not match.';
    else {
        try {
            [$lat, $lng] = coordinates($v);
            if ($role === 'recipient' && (empty($v['institution']) || empty($v['registration_number']) || empty($v['contact_person']) || empty($v['phone']) || empty($v['address']) || empty($v['province']) || empty($v['district']) || empty($v['city']) || !in_array($v['institution_type'] ?? '', ['School', 'Piriven', 'Educational Organization', 'Other']))) throw new RuntimeException('Complete all institution fields. Individual people cannot register as receivers.');
            $c = db()->prepare('SELECT 1 FROM users WHERE email=?');
            $c->execute([$email]);
            if ($c->fetch()) throw new RuntimeException('That email is already registered.');
            if ($role === 'recipient') {
                $c = db()->prepare('SELECT 1 FROM institutions WHERE registration_number=?');
                $c->execute([trim($v['registration_number'])]);
                if ($c->fetch()) throw new RuntimeException('That institution registration number is already registered.');
            }
            $doc = $role === 'recipient' ? upload_document() : null;
            db()->beginTransaction();
            db()->prepare('INSERT INTO users(name,email,password,phone,address,institution,role,province,district,city,latitude,longitude,profile_image) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute([$name, $email, password_hash($v['password'], PASSWORD_DEFAULT), trim($v['phone'] ?? ''), trim($v['address'] ?? ''), trim($v['institution'] ?? ''), $role, trim($v['province'] ?? ''), trim($v['district'] ?? ''), trim($v['city'] ?? ''), $lat, $lng, upload_image('profile_image')]);
            $id = (int) db()->lastInsertId();
            if ($role === 'recipient') {
                db()->prepare('INSERT INTO institutions(user_id,institution_type,registration_number,contact_person,description,verification_document) VALUES(?,?,?,?,?,?)')->execute([$id, $v['institution_type'], trim($v['registration_number']), trim($v['contact_person']), trim($v['institution_description'] ?? ''), $doc]);
                admin_notice('New institution verification: ' . trim($v['institution']));
            }
            db()->commit();
            flash($role === 'recipient' ? 'Application submitted for verification. Log in to view its status.' : 'Account created. You can log in now.');
            redirect('login.php');
        } catch (Throwable $ex) {
            if (db()->inTransaction()) db()->rollBack();
            $err = $ex instanceof PDOException ? 'Registration could not be saved. Check for duplicate details.' : $ex->getMessage();
        }
    }
}
$role = $v['role'] ?? 'recipient';
?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>
            Join ·
            <?= SITE_NAME ?>
        </title>
        <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
    </head>
    <body>
        <div class="auth">
            <div class="auth-art">
                <img class="bgimg" src="<?= url('assets/img/auth.jpg') ?>" alt="" onerror="this.remove()">
                <a class="brand" href="<?= url() ?>">
                    <span class="logo">
                        B
                    </span>
                    <?= SITE_NAME ?>
                </a>
                <h2>
                    Give learning a second life.
                </h2>
                <p>
                    Join as a recipient to request books and education items, or as a donor to share them.
                </p>
            </div>
            <div class="auth-form">
                <form method="post" id="regform" enctype="multipart/form-data">
                    <h1>
                        Create account
                    </h1>
                    <p class="muted" style="margin-bottom:1.2rem">
                        Institution accounts require administrator verification.
                    </p>
                    <div class="alert a-err" id="formerr" <?= $err ? '' : 'hidden' ?>>
                        <?= e($err) ?>
                    </div>
                    <?= csrf_field() ?>
                    <input type="hidden" name="role" id="roleinput" value="<?= e($role) ?>">
                    <div class="rolepick" id="rolepick">
                        <button type="button" data-role="recipient" class="<?= $role === 'recipient' ? 'on' : '' ?>">
                            Institution
                            <small>
                                Schools and approved organizations only
                            </small>
                        </button>
                        <button type="button" data-role="donor" class="<?= $role === 'donor' ? 'on' : '' ?>">
                            Donor
                            <small>
                                I have items to share
                            </small>
                        </button>
                    </div>
                    <div class="field">
                        <label for="n">
                            Full name
                        </label>
                        <input id="n" name="name" required value="<?= e($v['name'] ?? '') ?>">
                    </div>
                    <div class="grid2">
                        <div class="field">
                            <label for="e">
                                Email
                            </label>
                            <input id="e" type="email" name="email" required value="<?= e($v['email'] ?? '') ?>">
                        </div>
                        <div class="field">
                            <label for="p">
                                Phone
                            </label>
                            <input id="p" name="phone" value="<?= e($v['phone'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="grid2">
                        <div class="field">
                            <label for="i">
                                School / university
                            </label>
                            <input id="i" name="institution" value="<?= e($v['institution'] ?? '') ?>">
                        </div>
                        <div class="field">
                            <label for="a">
                                Address / city
                            </label>
                            <input id="a" name="address" value="<?= e($v['address'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="grid2">
                        <div class="field">
                            <label for="pw">
                                Password
                            </label>
                            <input id="pw" type="password" name="password" required minlength="8">
                        </div>
                        <div class="field">
                            <label for="cf">
                                Confirm password
                            </label>
                            <input id="cf" type="password" name="confirm" required>
                        </div>
                    </div>
                    <div class="grid2">
                        <?= field('province','Province','text',$v['province']??'') ?>
                        <?= field('district','District','text',$v['district']??'') ?>
                        <?= field('city','City / location','text',$v['city']??'') ?>
                        <?= field('latitude','Latitude (optional)','text',$v['latitude']??'') ?>
                        <?= field('longitude','Longitude (optional)','text',$v['longitude']??'') ?>
                    </div>
                    <div id="institution-fields">
                        <h3>
                            Institution verification
                        </h3>
                        <p>
                            For schools, Piriven and educational organizations only. Complete these fields when registering as a receiver.
                        </p>
                        <div class="field">
                            <label>
                                Institution type
                            </label>
                            <select name="institution_type">
                                <?php
                                foreach (['School', 'Piriven', 'Educational Organization', 'Other'] as $it) :
                                ?>
                                    <option <?= ($v['institution_type']??'')===$it?'selected':'' ?>>
                                        <?= e($it) ?>
                                    </option>
                                <?php
                                endforeach;
                                ?>
                            </select>
                        </div>
                        <?= field('registration_number','Institution registration / identification number','text',$v['registration_number']??'') ?>
                        <?= field('contact_person','Contact person','text',$v['contact_person']??'') ?>
                        <div class="field">
                            <label>
                                Institution description
                            </label>
                            <textarea name="institution_description">
                                <?= e($v['institution_description']??'') ?>
                            </textarea>
                        </div>
                        <div class="field">
                            <label>
                                Supporting document (PDF, JPG or PNG; max 5 MB)
                            </label>
                            <input type="file" name="verification_document" accept="application/pdf,image/jpeg,image/png">
                        </div>
                    </div>
                    <div class="field">
                        <label>
                            Profile image (optional, max 3 MB)
                        </label>
                        <input type="file" name="profile_image" accept="image/jpeg,image/png,image/webp">
                    </div>
                    <button class="btn block" type="submit">
                        Create account
                    </button>
                    <p class="small muted" style="margin-top:1.2rem">
                        Already a member?
                        <a href="<?= url('login.php') ?>">
                            Log in
                        </a>
                    </p>
                </form>
            </div>
        </div>
        <script src="<?= url('assets/js/app.js') ?>"></script>
    </body>
</html>
