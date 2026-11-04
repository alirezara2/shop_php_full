<?php
/**
 * تنظیمات کلی سایت
 * این فایل در index.php اول از همه include می‌شود.
 */

// جلوگیری از دسترسی مستقیم
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// ----- تنظیمات دیتابیس -----
define('DB_HOST', 'localhost');
define('DB_NAME', 'shopCO');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ----- تنظیمات مسیرها -----
define('BASE_URL', 'http://localhost/kohnevard-shop');
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('UPLOAD_URL', BASE_URL . '/uploads');

// ----- تنظیمات آپلود -----
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png']);
define('ALLOWED_MIME_TYPES', ['image/jpeg', 'image/png']);

// ----- منطقه زمانی و کدگذاری -----
date_default_timezone_set('Asia/Tehran');
mb_internal_encoding('UTF-8');

// ----- شروع Session -----
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        // اگر HTTPS داری، این را true کن:
        // 'secure' => true,
    ]);
    session_start();
}

// ----- نمایش خطاها (برای توسعه) -----
// ⚠️ در محیط نهایی این دو خط را حذف/غیرفعال کن
error_reporting(E_ALL);
ini_set('display_errors', '1');

// ----- بارگذاری فایل‌های پیکربندی دیگر -----
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/database.php';
require_once BASE_PATH . '/includes/functions.php';
require_once BASE_PATH . '/includes/csrf.php';
require_once BASE_PATH . '/includes/flash.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/cart.php';