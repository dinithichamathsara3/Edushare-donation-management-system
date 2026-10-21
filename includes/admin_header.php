<?php
/* Administrator layout — different look, different session, different database. */
require_once __DIR__ . '/functions.php';
$a = require_admin();
$links = ['admin/' => 'Overview', 'admin/items.php' => 'Item approvals', 'admin/users.php' => 'Members', 'admin/requests.php' => 'Requests', 'admin/categories.php' => 'Categories'];
$links['admin/verification.php'] = 'Institution verification';
$links['admin/requirements.php'] = 'Requirements / urgent review';
$links['workflow.php'] = 'Claims / donation history';
$links['admin/reports.php'] = 'Reports and charts';
$links['admin/monitoring.php'] = 'Activity monitoring';
$links['admin/notifications.php'] = 'Notifications';
$links['admin/settings.php'] = 'System settings';
$cur = basename($_SERVER['SCRIPT_NAME']);
$noticeCount = (int) db()->query('SELECT COUNT(*) FROM admin_notices WHERE is_read=0')->fetchColumn();
$pending = (int) db()->query("SELECT COUNT(*) FROM items WHERE status='pending'")->fetchColumn();
?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>
            <?= e($title ?? 'Admin') ?>
            ·
            <?= SITE_NAME ?>
            Admin
        </title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
    </head>
    <body class="app admin" data-base="<?= BASE_URL ?>">
        <aside class="side" id="side">
            <a class="brand" href="<?= url('admin/') ?>">
                <span class="logo">
                    B
                </span>
                <?= SITE_NAME ?>
                <em>
                    Admin
                </em>
            </a>
            <nav>
                <?php
                foreach ($links as $href => $label) : $on = $href === 'admin/' ? $cur === 'index.php' : basename($href) === $cur;
                ?>
                <a href="<?= url($href) ?>" class="<?= $on ? 'on' : '' ?>">
                    <?= e($label) ?>
                    <?php
                    if ($label === 'Notifications' && $noticeCount) :
                    ?>
                        <b class="dot">
                            <?= $noticeCount ?>
                        </b>
                    <?php
                    endif;
                    ?>
                    <?php
                    if ($label === 'Item approvals' && $pending) :
                    ?>
                        <b class="dot">
                            <?= $pending ?>
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
                    <?= e(initials($a['name'])) ?>
                </span>
                <span>
                    <strong>
                        <?= e($a['name']) ?>
                    </strong>
                    <small>
                        Administrator
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
