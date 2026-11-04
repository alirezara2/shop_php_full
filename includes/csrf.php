<?php
if (!defined('BASE_PATH')) { die('دسترسی مستقیم مجاز نیست.'); }

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function verifyCsrf(?string $token = null): bool {
    $token = $token ?? ($_POST['csrf_token'] ?? '');
    if (empty($_SESSION['csrf_token']) || empty($token)) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

function requireCsrf(): void {
    if (!verifyCsrf()) {
        http_response_code(403);
        die('درخواست نامعتبر است (CSRF). لطفاً صفحه را دوباره بارگذاری کنید.');
    }
}