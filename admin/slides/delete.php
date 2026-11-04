<?php
require_once dirname(__DIR__) . '/includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/admin/slides/list.php');
}

requireCsrf();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    setFlash('error', 'شناسه اسلاید نامعتبر است.');
    redirect(BASE_URL . '/admin/slides/list.php');
}

try {
    $stmt = $pdo->prepare("SELECT image FROM slides WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $img = $stmt->fetchColumn();

    if ($img) {
        $stmt = $pdo->prepare("DELETE FROM slides WHERE id = ?");
        $stmt->execute([$id]);
        deleteImage('slider', $img);
        setFlash('success', 'اسلاید حذف شد.');
    } else {
        setFlash('error', 'اسلاید یافت نشد.');
    }
} catch (PDOException $e) {
    setFlash('error', 'خطا در حذف: ' . $e->getMessage());
}

redirect(BASE_URL . '/admin/slides/list.php');