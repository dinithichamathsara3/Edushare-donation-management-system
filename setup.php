<?php
require_once __DIR__ . '/includes/config.php';
try {
    $connection = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $exists = $connection->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='" . DB_ADMIN . "' AND table_name='admins'")->fetchColumn();
    if ($exists && $connection->query('SELECT COUNT(*) FROM ' . DB_ADMIN . '.admins')->fetchColumn()) {
        http_response_code(403);
        exit('Setup is already complete. Use the database upgrade SQL for existing installations.');
    }
} catch (Throwable $e) {
    exit('Cannot connect to MySQL. Check includes/config.php and start MySQL.');
}
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? '-')) {
        http_response_code(419);
        exit('Session expired.');
    }
    $email = trim($_POST['email'] ?? '');
    $pw = $_POST['password'] ?? '';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pw) < 10 || $pw !== ($_POST['confirm'] ?? '')) $err = 'Enter a valid administrator email and matching password of at least 10 characters.';
    else {
        try {
            foreach (['database.sql', 'upgrade.sql'] as $file) {
                $sql = preg_replace('/^--.*$/m', '', file_get_contents(__DIR__ . '/sql/' . $file));
                foreach (array_filter(array_map('trim', explode(';', $sql))) as $q) $connection->exec($q);
            }
            db('admin')->prepare('INSERT INTO admins(name,email,password) VALUES(?,?,?)')->execute([trim($_POST['name'] ?? 'Administrator') ? : 'Administrator', $email, password_hash($pw, PASSWORD_DEFAULT)]);
            echo '<p>Setup complete. Delete setup.php, then <a href="' . BASE_URL . '/login.php">log in as Administrator</a>.</p>';
            exit;
        } catch (Throwable $e) {
            $err = 'Setup failed. Check the database configuration. ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }
}
$_SESSION['csrf'] ??= bin2hex(random_bytes(16));
?>
<!doctype html>
<html lang="en">
    <meta charset="utf-8">
    <title>
        EduShare initial setup
    </title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <div class="sec">
        <form method="post" class="card">
            <h1>
                Initial setup
            </h1>
            <p>
                Create your own administrator. No sample users or donation records are added. Run this locally, then delete this file.
            </p>
            <p>
                <?= $err ?>
            </p>
            <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
            <div class="field">
                <label>
                    Name
                </label>
                <input name="name" required>
            </div>
            <div class="field">
                <label>
                    Administrator email
                </label>
                <input type="email" name="email" required>
            </div>
            <div class="field">
                <label>
                    Password (minimum 10 characters)
                </label>
                <input type="password" name="password" minlength="10" required>
            </div>
            <div class="field">
                <label>
                    Confirm password
                </label>
                <input type="password" name="confirm" required>
            </div>
            <button class="btn">
                Create databases and administrator
            </button>
        </form>
    </div>
</html>
