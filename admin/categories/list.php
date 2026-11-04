<?php
require_once dirname(__DIR__) . '/includes/admin_auth.php';

// ----- پردازش حذف (قبل از هر خروجی HTML) -----
if (isset($_GET['delete'], $_GET['token'])) {
    if (hash_equals(csrfToken(), $_GET['token'])) {
        $delId = (int)$_GET['delete'];
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
            $stmt->execute([$delId]);
            $productCount = (int)$stmt->fetchColumn();

            if ($productCount > 0) {
                setFlash('error', "این دسته دارای $productCount محصول است. ابتدا محصولات را جابه‌جا یا حذف کنید.");
            } else {
                $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                $stmt->execute([$delId]);
                setFlash('success', 'دسته‌بندی با موفقیت حذف شد.');
            }
        } catch (PDOException $e) {
            setFlash('error', 'خطا در حذف دسته: ' . $e->getMessage());
        }
    } else {
        setFlash('error', 'توکن امنیتی نامعتبر است.');
    }
    redirect(BASE_URL . '/admin/categories/list.php');
}

// ----- حالا خروجی -----
$pageTitle = 'مدیریت دسته‌بندی‌ها';
require_once dirname(__DIR__) . '/includes/admin_header.php';

$categories = $pdo->query("
    SELECT c.*, 
           (SELECT COUNT(*) FROM products WHERE category_id = c.id) AS product_count
    FROM categories c
    ORDER BY c.id DESC
")->fetchAll();
?>

<h1 class="admin-page-title">مدیریت دسته‌بندی‌ها</h1>

<?php renderFlashes(); ?>

<div class="admin-card">
    <div class="admin-card-header">
        <h2>لیست دسته‌بندی‌ها (<?= number_format(count($categories)) ?> مورد)</h2>
        <a href="<?= BASE_URL ?>/admin/categories/create.php" class="btn-admin btn-admin-primary">
            ➕ افزودن دسته جدید
        </a>
    </div>

    <?php if (empty($categories)): ?>
        <div class="admin-empty">
            <div class="icon">🗂️</div>
            <h3>هنوز دسته‌بندی‌ای ساخته نشده</h3>
            <p>با دکمه بالا اولین دسته را اضافه کنید.</p>
        </div>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>نام</th>
                        <th>اسلاگ</th>
                        <th>تعداد محصول</th>
                        <th>تاریخ</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $c): ?>
                        <tr>
                            <td><?= (int)$c['id'] ?></td>
                            <td><?= e($c['name']) ?></td>
                            <td><code><?= e($c['slug']) ?></code></td>
                            <td>
                                <?php if ($c['product_count'] > 0): ?>
                                    <span class="badge-soft badge-soft-info"><?= (int)$c['product_count'] ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e(date('Y/m/d', strtotime($c['created_at']))) ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="<?= BASE_URL ?>/admin/categories/edit.php?id=<?= (int)$c['id'] ?>"
                                       class="btn-admin btn-admin-primary btn-admin-sm">ویرایش</a>
                                    <a href="<?= BASE_URL ?>/admin/categories/list.php?delete=<?= (int)$c['id'] ?>&token=<?= e(csrfToken()) ?>"
                                       class="btn-admin btn-admin-danger btn-admin-sm"
                                       data-confirm="دسته «<?= e($c['name']) ?>» حذف شود؟">حذف</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>