<?php
require_once dirname(__DIR__) . '/includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/admin/categories/list.php');
}

requireCsrf();

$categoryId = (int)($_POST['id'] ?? 0);
if ($categoryId <= 0) {
    setFlash('error', 'شناسه دسته نامعتبر است.');
    redirect(BASE_URL . '/admin/categories/list.php');
}

try {
    // چک محصولات وابسته
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
    $stmt->execute([$categoryId]);
    $productCount = (int)$stmt->fetchColumn();

    if ($productCount > 0) {
        setFlash('error', "این دسته دارای $productCount محصول است. ابتدا محصولات را جابه‌جا یا حذف کنید.");
        redirect(BASE_URL . '/admin/categories/list.php');
    }

    $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ? LIMIT 1");
    $stmt->execute([$categoryId]);
    $name = $stmt->fetchColumn();

    if (!$name) {
        setFlash('error', 'دسته‌بندی یافت نشد.');
        redirect(BASE_URL . '/admin/categories/list.php');
    }

    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$categoryId]);

    setFlash('success', 'دسته «' . $name . '» حذف شد.');
} catch (PDOException $e) {
    setFlash('error', 'خطا در حذف: ' . $e->getMessage());
}

redirect(BASE_URL . '/admin/categories/list.php');