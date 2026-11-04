<?php
require_once dirname(__DIR__) . '/includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/admin/comments/list.php');
}

requireCsrf();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    setFlash('error', 'شناسه نظر نامعتبر است.');
    redirect(BASE_URL . '/admin/comments/list.php');
}

try {
    $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        setFlash('success', 'نظر با موفقیت حذف شد.');
    } else {
        setFlash('error', 'نظر یافت نشد.');
    }
} catch (PDOException $e) {
    setFlash('error', 'خطا در حذف نظر: ' . $e->getMessage());
}

$ref = $_SERVER['HTTP_REFERER'] ?? '';
if ($ref && strpos($ref, BASE_URL) === 0) {
    redirect($ref);
}
redirect(BASE_URL . '/admin/comments/list.php');