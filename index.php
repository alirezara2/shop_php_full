<?php
/**
 * نقطه ورود اصلی سایت
 * همه درخواست‌های صفحات عمومی از اینجا رد می‌شوند.
 */

require_once __DIR__ . '/config/config.php';

// ----- مسیرهای مجاز -----
$allowedPages = [
    'home'    => 'pages/home.php',
    'shop'    => 'pages/shop.php',
    'product' => 'pages/product.php',
    'about'   => 'pages/about.php',
    'contact' => 'pages/contact.php',
];

// ----- صفحه پیش‌فرض -----
$page = $_GET['page'] ?? 'home';

// اگر صفحه وجود نداشت، 404 بده
if (!isset($allowedPages[$page])) {
    http_response_code(404);
    $page = 'home';
}

// ----- فایل‌های حساس که نباید از اینجا اجرا شوند -----
$blocked = ['config', 'includes', 'database', 'vendor', 'admin', 'user'];
foreach ($blocked as $b) {
    if (strpos($page, $b) !== false) {
        http_response_code(403);
        die('دسترسی غیرمجاز.');
    }
}

// ----- include کردن صفحه -----
$pageFile = __DIR__ . '/' . $allowedPages[$page];

if (!is_file($pageFile)) {
    http_response_code(404);
    die('صفحه یافت نشد.');
}

require_once $pageFile;