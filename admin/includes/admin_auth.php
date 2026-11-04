<?php
/**
 * چک احراز هویت ادمین
 * این فایل باید در بالای همه فایل‌های پنل ادمین (به‌جز login.php) include شود.
 */

// مسیر پایه را اگر ست نشده، ست کن
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 2));
}

require_once BASE_PATH . '/config/config.php';

// اگر ادمین لاگین نیست، به صفحه لاگین برود
if (!isAdminLoggedIn()) {
    setFlash('warning', 'برای دسترسی به پنل ادمین ابتدا وارد شوید.');
    redirect(BASE_URL . '/admin/login.php');
}

// گرفتن اطلاعات ادمین جاری (اختیاری، برای نمایش در هدر)
$currentAdmin = currentAdmin();

// اگر ادمین لاگین است اما در دیتابیس نیست (حذف شده)، خروج
if (!$currentAdmin) {
    logoutAdmin();
    setFlash('error', 'حساب کاربری شما یافت نشد. لطفاً دوباره وارد شوید.');
    redirect(BASE_URL . '/admin/login.php');
}