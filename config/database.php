<?php
/**
 * اتصال به دیتابیس با PDO
 * این فایل یک متغیر $pdo در اختیار بقیه فایل‌ها قرار می‌دهد.
 */

if (!defined('DB_HOST')) {
    die('دسترسی مستقیم به این فایل مجاز نیست.');
}

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    // ⚠️ در محیط نهایی پیام دقیق خطا را به کاربر نشان نده
    // error_log($e->getMessage());
    die('خطا در اتصال به دیتابیس. لطفاً بعداً تلاش کنید.');
}

/**
 * تابع کمکی برای گرفتن تنظیمات از جدول settings
 * فقط یک بار از دیتابیس می‌خواند و کش می‌کند.
 */
function getSetting(string $key, $default = null)
{
    global $pdo;
    static $cache = null;

    if ($cache === null) {
        $cache = [];
        try {
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
            while ($row = $stmt->fetch()) {
                $cache[$row['setting_key']] = $row['setting_value'];
            }
        } catch (PDOException $e) {
            // جدول ممکن است هنوز ساخته نشده باشد
        }
    }

    return $cache[$key] ?? $default;
}