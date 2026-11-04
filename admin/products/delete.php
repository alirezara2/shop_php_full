<?php
require_once dirname(__DIR__) . '/includes/admin_auth.php';

/**
 * حذف محصول
 * فقط با POST و CSRF. اگر GET بیاید، به لیست برمی‌گردد.
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/admin/products/list.php');
}

requireCsrf();

$productId = (int)($_POST['id'] ?? 0);
if ($productId <= 0) {
    setFlash('error', 'شناسه محصول نامعتبر است.');
    redirect(BASE_URL . '/admin/products/list.php');
}

try {
    // گرفتن نام عکس
    $stmt = $pdo->prepare("SELECT image, title FROM products WHERE id = ? LIMIT 1");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product) {
        setFlash('error', 'محصول یافت نشد.');
        redirect(BASE_URL . '/admin/products/list.php');
    }

    // حذف محصول
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$productId]);

    // حذف فایل عکس
    if ($product['image']) {
        deleteImage('products', $product['image']);
    }

    setFlash('success', 'محصول «' . $product['title'] . '» با موفقیت حذف شد.');
} catch (PDOException $e) {
    setFlash('error', 'خطا در حذف محصول: ' . $e->getMessage());
}

redirect(BASE_URL . '/admin/products/list.php');