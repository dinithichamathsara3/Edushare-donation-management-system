<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');
$u = user();
if ($u) $u = require_user();
echo json_encode(['unread' => $u ? unread_count($u['id']) : 0]);
