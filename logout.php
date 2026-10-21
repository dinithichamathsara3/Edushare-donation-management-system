<?php
require_once __DIR__ . '/includes/functions.php';
unset($_SESSION['user'], $_SESSION['admin']);
flash('You have been logged out.');
redirect('login.php');
