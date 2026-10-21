<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$i = institution((int)($_GET['id'] ?? 0));
if (!$i) {
    http_response_code(404);
    exit;
}
$file = __DIR__ . '/../private/verification/' . basename($i['verification_document']);
if (!is_file($file)) {
    http_response_code(404);
    exit;
}
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($file);
header('X-Content-Type-Options: nosniff');
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="verification.' . pathinfo($file, PATHINFO_EXTENSION) . '"');
readfile($file);
