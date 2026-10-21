<?php
/* Member portal layout (recipient + donor). Set $title and optionally $nav before including. */
require_once __DIR__ . '/functions.php';
$u = user();
$role = $u['role'] ?? '';
$links = $role === 'donor' ? ['donor/' => 'Dashboard', 'donor/add_item.php' => 'Donate an item', 'donor/my_items.php' => 'My items', 'donor/requests.php' => 'Requests'] : ['recipient/' => 'Dashboard', 'recipient/items.php' => 'Browse items', 'recipient/requests.php' => 'My requests'];
$links['needs.php'] = 'Nearby needs / matching';
$links['needs.php?urgent=1'] = 'Urgent needs';
$links['workflow.php'] = 'Offers, claims and tracking';
if ($role === 'donor') $links['donor/impact.php'] = 'My impact';
else {
    $links['recipient/requirements.php'] = 'My requirements';
    $links['recipient/verification.php'] = 'Verification';
}
$links['notifications.php'] = 'Notifications';
$links['profile.php'] = 'My profile';
$cur = basename($_SERVER['SCRIPT_NAME']);
$unread = $u ? unread_count($u['id']) : 0;
?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>
            <?= e($title ?? 'Dashboard') ?>
            ·
            <?= SITE_NAME ?>
        </title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
    </head>
    <body class="app" data-base="<?= BASE_URL ?>">
        <aside class="side" id="side">
            <a class="brand" href="<?= url() ?>">
                <span class="logo">
                    B
                </span>
                <?= SITE_NAME ?>
            </a>
            <!-- LOGO: replace the <span class="logo"> above with <img src="assets/img/logo.png" height="30"> -->
            <nav>
                <?php
                foreach ($links as $href => $label) : $on = ($href === 'donor/' || $href === 'recipient/') ? ($cur === 'index.php' && str_contains($_SERVER['SCRIPT_NAME'], rtrim($href, '/'))) : basename($href) === $cur;
                ?>
                <a href="<?= url($href) ?>" class="<?= $on ? 'on' : '' ?>">
                    <?= e($label) ?>
                    <?php
                    if ($label === 'Notifications' && $unread) :
                    ?>
                        <b class="dot" id="nbadge">
                            <?= $unread ?>
                        </b>
                    <?php
                    endif;
                    ?>
                </a>
            <?php
            endforeach;
            ?>
        </nav>
        <a class="logout" href="<?= url('logout.php') ?>">
            Log out
        </a>
    </aside>
    <div class="main">
        <header class="top">
            <button class="burger" id="burger" aria-label="Menu">
                ☰
            </button>
            <h1>
                <?= e($title ?? '') ?>
            </h1>
            <div class="who">
                <span class="av">
                    <?= e(initials($u['name'] ?? '?')) ?>
                </span>
                <span>
                    <strong>
                        <?= e($u['name'] ?? '') ?>
                    </strong>
                    <small>
                        <?= e(ucfirst($role)) ?>
                    </small>
                </span>
            </div>
        </header>
        <main class="content">
            <?php
            if ($f = flash()) :
            ?>
                <div class="alert a-<?= e($f[1]) ?>">
                    <?= e($f[0]) ?>
                </div>
            <?php
            endif;
            ?>
