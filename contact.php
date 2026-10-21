<?php
require_once __DIR__ . '/includes/functions.php';
$title = 'Contact EduShare';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (trim($_POST['name'] ?? '') === '' || !filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL) || trim($_POST['message'] ?? '') === '') flash('Complete your name, valid email and message.', 'err');
    elseif (time() - ($_SESSION['last_contact'] ?? 0) < 60) flash('Please wait one minute before sending another message.', 'err');
    else {
        db()->prepare('INSERT INTO contact_messages(name,email,message) VALUES(?,?,?)')->execute([mb_substr(trim($_POST['name']), 0, 100), mb_substr(trim($_POST['email']), 0, 120), mb_substr(trim($_POST['message']), 0, 5000)]);
        admin_notice('New contact message: ' . mb_substr(trim($_POST['name']), 0, 100));
        $_SESSION['last_contact'] = time();
        flash('Your message was sent to the administrator.');
    }
    redirect('contact.php');
}
require __DIR__ . '/includes/public_header.php';
?>
<form class="card" method="post">
    <h1>
        Contact us
    </h1>
    <?= csrf_field() ?>
    <?= field('name','Your name','text','',true) ?>
    <?= field('email','Email','email','',true) ?>
    <div class="field">
        <label>
            Message
        </label>
        <textarea name="message" required maxlength="5000">
        </textarea>
    </div>
    <button class="btn">
        Send message
    </button>
</form>
<?php
require __DIR__ . '/includes/public_footer.php';
?>
