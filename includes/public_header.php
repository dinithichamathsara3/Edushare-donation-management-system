<?php
require_once __DIR__ . '/functions.php';
?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>
            <?= e($title??SITE_NAME) ?>
            · EduShare
        </title>
        <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
    </head>
    <body data-base="<?= BASE_URL ?>">
        <header class="pub-nav">
            <a class="brand" href="<?= url() ?>">
                EduShare
            </a>
            <nav>
                <?php
                foreach (['' => 'Home', 'about.php' => 'About', 'how-it-works.php' => 'How it works', 'browse.php' => 'Browse donations', 'needs.php?urgent=1' => 'Urgent needs', 'contact.php' => 'Contact', 'login.php' => 'Login', 'register.php' => 'Register'] as $p => $label) :
                ?>
                    <a href="<?= url($p) ?>">
                        <?= e($label) ?>
                    </a>
                <?php
                endforeach;
                ?>
            </nav>
        </header>
        <main class="sec">
            <?php
            if ($f = flash()) :
            ?>
                <div class="alert a-<?= e($f[1]) ?>">
                    <?= e($f[0]) ?>
                </div>
            <?php
            endif;
            ?>
