# 🏔️ کوهنورد شاپ — فروشگاه آنلاین تجهیزات کوهنوردی

فروشگاه آنلاین کامل با PHP خام (بدون فریمورک) و MySQL.

---

## 📋 پیش‌نیازها

- **PHP 8.0** یا بالاتر
- **MySQL 5.7** یا بالاتر (یا MariaDB 10.3+)
- **Apache** با ماژول‌های `mod_rewrite` و `mod_headers` فعال
- افزونه‌های PHP: `pdo_mysql`، `mbstring`، `gd`، `json`، `fileinfo`
- (اختیاری) **Composer** برای خروجی xlsx

---

## 🚀 مراحل نصب

### ۱. کپی فایل‌ها

کل پوشه پروژه را در مسیر وب‌سرور خود کپی کن:

- **XAMPP:** `C:\xampp\htdocs\kohnevard-shop\`
- **Laragon:** `C:\laragon\www\kohnevard-shop\`
- **هاست:** `public_html/kohnevard-shop/`

### ۲. ساخت دیتابیس

در phpMyAdmin یک دیتابیس جدید بساز:

- نام: `kohnevard_shop`
- Collation: `utf8mb4_unicode_ci`

سپس فایل `database/schema.sql` را در تب **Import** آپلود یا در تب **SQL** کپی و اجرا کن.

### ۳. تنظیم اتصال دیتابیس

فایل `config/config.php` را باز کن و اطلاعات دیتابیس خود را وارد کن:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'kohnevard_shop');
define('DB_USER', 'root');
define('DB_PASS', '');