<?php
if (!defined('BASE_PATH')) { die('دسترسی مستقیم مجاز نیست.'); }

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function normalizePhone(string $phone): string {
    $phone = trim($phone);
    $phone = preg_replace('/[^\d]/', '', $phone);
    if (strpos($phone, '98') === 0 && strlen($phone) === 12) {
        $phone = '0' . substr($phone, 2);
    }
    if (strpos($phone, '9') === 0 && strlen($phone) === 10) {
        $phone = '0' . $phone;
    }
    return $phone;
}

function isValidPhone(string $phone): bool {
    return (bool) preg_match('/^09\d{9}$/', $phone);
}

function isValidPostalCode(string $code): bool {
    return (bool) preg_match('/^\d{10}$/', $code);
}

function formatPrice($amount): string {
    return number_format((int)$amount) . ' تومان';
}

function finalPrice(array $product): int {
    if (!empty($product['discount_price']) && $product['discount_price'] > 0) {
        return (int)$product['discount_price'];
    }
    return (int)$product['price'];
}

function hasDiscount(array $product): bool {
    return !empty($product['discount_price']) && $product['discount_price'] > 0 && $product['discount_price'] < $product['price'];
}

function discountPercent(array $product): int {
    if (!hasDiscount($product) || $product['price'] <= 0) return 0;
    return (int) round((($product['price'] - $product['discount_price']) / $product['price']) * 100);
}

function old($key, $default = '') {
    return e($_SESSION['old'][$key] ?? $default);
}

function setOld(array $data): void {
    $_SESSION['old'] = $data;
}

function clearOld(): void {
    unset($_SESSION['old']);
}

function validateExtension(string $filename): bool {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, ALLOWED_EXTENSIONS, true);
}

function validateImageFile(array $file): array {
    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'msg' => 'خطا در آپلود فایل.'];
    }
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['ok' => false, 'msg' => 'حجم فایل بیشتر از حد مجاز (۲ مگابایت) است.'];
    }
    if (!validateExtension($file['name'])) {
        return ['ok' => false, 'msg' => 'فقط فایل jpg و png مجاز است.'];
    }
    $info = @getimagesize($file['tmp_name']);
    if ($info === false || !in_array($info['mime'], ALLOWED_MIME_TYPES, true)) {
        return ['ok' => false, 'msg' => 'فایل تصویری معتبر نیست.'];
    }
    return ['ok' => true];
}

function uploadImage(array $file, string $subfolder): array {
    $check = validateImageFile($file);
    if (!$check['ok']) return $check;

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $ext = $ext === 'jpeg' ? 'jpg' : $ext;
    $name = uniqid('img_', true) . '.' . $ext;

    $dir = UPLOAD_PATH . '/' . $subfolder;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $path = $dir . '/' . $name;

    if (!move_uploaded_file($file['tmp_name'], $path)) {
        return ['ok' => false, 'msg' => 'ذخیره فایل ناموفق بود.'];
    }
    return ['ok' => true, 'filename' => $name];
}

function deleteImage(string $subfolder, string $filename): void {
    if (!$filename) return;
    $path = UPLOAD_PATH . '/' . $subfolder . '/' . basename($filename);
    if (is_file($path)) {
        @unlink($path);
    }
}

function imageUrl(?string $subfolder, ?string $filename, string $fallback = ''): string {
    if (!$filename) return $fallback;
    return UPLOAD_URL . '/' . $subfolder . '/' . rawurlencode($filename);
}

function statusLabel(string $status): string {
    return $GLOBALS['ORDER_STATUS_LABELS'][$status] ?? $status;
}

function statusColor(string $status): string {
    return $GLOBALS['ORDER_STATUS_COLORS'][$status] ?? '#888';
}

function generateOrderNumber(int $id): string {
    return 'KOH-' . str_pad((string)$id, 6, '0', STR_PAD_LEFT);
}

function isAjax(): bool {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}