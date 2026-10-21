<?php
require_once __DIR__ . '/includes/functions.php';
if (user()) redirect(user()['role'] . '/');
if (admin()) redirect('admin/');
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_SESSION['login_attempts'] ?? 0) >= 8 && time() - ($_SESSION['last_attempt'] ?? 0) < 300) {
        http_response_code(429);
        exit('Too many failed attempts in this session. Try again in five minutes.');
    }
    $email = trim($_POST['email'] ?? '');
    $pw = $_POST['password'] ?? '';
    // 1) members (donor / recipient) live in the main database
    $s = db()->prepare('SELECT * FROM users WHERE email=?');
    $s->execute([$email]);
    $u = $s->fetch();
    // 2) administrators live in the separate admin database
    $s = db('admin')->prepare('SELECT * FROM admins WHERE email=?');
    $s->execute([$email]);
    $a = $s->fetch();
    $selected = $_POST['login_role'] ?? 'donor';
    if ($u && $u['role'] === $selected && password_verify($pw, $u['password'])) {
        if ($u['status'] === 'blocked') $err = 'This account has been blocked. Contact an administrator.';
        else {
            $_SESSION['login_attempts'] = 0;
            session_regenerate_id(true);
            unset($_SESSION['admin']);
            $_SESSION['user'] = ['id' => (int) $u['id'], 'name' => $u['name'], 'role' => $u['role']];
            redirect($u['role'] === 'recipient' && !approved_receiver((int) $u['id']) ? 'recipient/verification.php' : $u['role'] . '/');
        }
    } elseif ($selected === 'admin' && $a && password_verify($pw, $a['password'])) {
        $_SESSION['login_attempts'] = 0;
        session_regenerate_id(true);
        unset($_SESSION['user']);
        $_SESSION['admin'] = ['id' => (int) $a['id'], 'name' => $a['name']];
        admin_log('Logged in');
        redirect('admin/');
    } else {
        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
        $_SESSION['last_attempt'] = time();
        $err = 'Email or password is incorrect for the selected role.';
    }
}
$f = flash();
?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>
            Log in ·
            <?= SITE_NAME ?>
        </title>
        <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
    </head>
    <body>
        <div class="auth">
            <div class="auth-art">
                <!-- BACKGROUND PICTURE: assets/img/auth.jpg -->
                <img class="bgimg" src="<?= url('assets/img/auth.jpg') ?>" alt="" onerror="this.remove()">
                <a class="brand" href="<?= url() ?>">
                    <span class="logo">
                        B
                    </span>
                    <?= SITE_NAME ?>
                </a>
                <h2>
                    Welcome back to the shelf.
                </h2>
                <p>
                    Donate books and education items, track requests and see what's new.
                </p>
            </div>
            <div class="auth-form">
                <form method="post">
                    <h1>
                        Log in
                    </h1>
                    <p class="muted" style="margin-bottom:1.5rem">
                        Recipients and donors sign in here.
                    </p>
                    <?php
                    if ($err) :
                    ?>
                        <div class="alert a-err">
                            <?= e($err) ?>
                        </div>
                    <?php
                    elseif ($f) :
                    ?>
                        <div class="alert a-<?= e($f[1]) ?>">
                            <?= e($f[0]) ?>
                        </div>
                    <?php
                    endif;
                    ?>
                    <?= csrf_field() ?>
                    <div class="field">
                        <label>
                            Login as
                        </label>
                        <select name="login_role">
                            <?php
                            foreach (["donor" => "Donor", "recipient" => "Verified institution", "admin" => "Administrator"] as $val => $label) :
                            ?>
                                <option value="<?= e($val) ?>" <?= ($_POST["login_role"]??"donor")===$val?"selected":"" ?>>
                                    <?= e($label) ?>
                                </option>
                            <?php
                            endforeach;
                            ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="email">
                            Email
                        </label>
                        <input id="email" type="email" name="email" required autofocus value="<?= e($_POST['email'] ?? '') ?>">
                    </div>
                    <div class="field">
                        <label for="pw">
                            Password
                        </label>
                        <input id="pw" type="password" name="password" required>
                    </div>
                    <button class="btn block" type="submit">
                        Log in
                    </button>
                    <p class="small muted" style="margin-top:1.2rem">
                        New here?
                        <a href="<?= url('register.php') ?>">
                            Create an account
                        </a>
                    </p>
                </form>
            </div>
        </div>
    </body>
</html>
