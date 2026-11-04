<?php
require_once dirname(__DIR__) . '/config/config.php';

$username = 'admin';
$password = 'admin';

$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("DELETE FROM admins WHERE username = ?");
    $stmt->execute([$username]);

    $stmt = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
    $stmt->execute([$username, $hash]);

    echo "✅ ادمین ساخته شد<br>";
    echo "نام کاربری: <strong>{$username}</strong><br>";
    echo "رمز عبور: <strong>{$password}</strong><br>";
    echo "هش: <code>{$hash}</code><br><br>";
    echo "⚠️ این فایل را حذف کن!";
} catch (PDOException $e) {
    echo "❌ خطا: " . $e->getMessage();
}