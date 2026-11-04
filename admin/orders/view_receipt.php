<?php
require_once dirname(__DIR__) . '/includes/admin_auth.php';

/**
 * ارسال فایل فیش بانکی به مرورگر
 * فایل receipts با .htaccess مستقیم قابل دسترسی نیست، فقط از اینجا
 */

$file = $_GET['file'] ?? '';
if ($file === '') {
    http_response_code(404);
    exit('فایل یافت نشد.');
}

// جلوگیری از path traversal
$file = basename($file);

$path = UPLOAD_PATH . '/receipts/' . $file;

if (!is_file($path)) {
    http_response_code(404);
    exit('فایل یافت نشد.');
}

// تعیین MIME
$info = @getimagesize($path);
$allowed = ['image/jpeg', 'image/png'];
if ($info === false || !in_array($info['mime'], $allowed, true)) {
    http_response_code(403);
    exit('نوع فایل مجاز نیست.');
}

header('Content-Type: ' . $info['mime']);
header('Content-Length: ' . filesize($path));
header('Cache-Control: private, max-age=3600');
readfile($path);
exit;