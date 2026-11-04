<?php
require_once dirname(__DIR__) . '/includes/admin_auth.php';

// ----- پردازش حذف (قبل از هر خروجی HTML) -----
if (isset($_GET['delete'], $_GET['token'])) {
    if (hash_equals(csrfToken(), $_GET['token'])) {
        $delId = (int)$_GET['delete'];
        try {
            $stmt = $pdo->prepare("SELECT image FROM slides WHERE id = ? LIMIT 1");
            $stmt->execute([$delId]);
            $img = $stmt->fetchColumn();

            if ($img) {
                $stmt = $pdo->prepare("DELETE FROM slides WHERE id = ?");
                $stmt->execute([$delId]);
                deleteImage('slider', $img);
                setFlash('success', 'اسلاید حذف شد.');
            } else {
                setFlash('error', 'اسلاید یافت نشد.');
            }
        } catch (PDOException $e) {
            setFlash('error', 'خطا در حذف: ' . $e->getMessage());
        }
    } else {
        setFlash('error', 'توکن امنیتی نامعتبر است.');
    }
    redirect(BASE_URL . '/admin/slides/list.php');
}

// ----- حالا خروجی -----
$pageTitle = 'مدیریت اسلایدر';
require_once dirname(__DIR__) . '/includes/admin_header.php';

$slides = $pdo->query("SELECT * FROM slides ORDER BY sort_order ASC, id DESC")->fetchAll();
?>

<h1 class="admin-page-title">مدیریت اسلایدر</h1>

<?php renderFlashes(); ?>

<div class="admin-card">
    <div class="admin-card-header">
        <h2>لیست اسلایدها (<?= number_format(count($slides)) ?> مورد)</h2>
        <a href="<?= BASE_URL ?>/admin/slides/create.php" class="btn-admin btn-admin-primary">
            ➕ افزودن اسلاید جدید
        </a>
    </div>

    <?php if (empty($slides)): ?>
        <div class="admin-empty">
            <div class="icon">🖼️</div>
            <h3>هنوز اسلایدی اضافه نشده</h3>
            <p>اولین اسلاید را برای صفحه اصلی بسازید.</p>
        </div>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>عکس</th>
                        <th>عنوان</th>
                        <th>لینک</th>
                        <th>ترتیب</th>
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($slides as $s): ?>
                        <tr>
                            <td><?= (int)$s['id'] ?></td>
                            <td>
                                <?php if ($s['image']): ?>
                                    <img class="thumb" style="width:80px;height:48px;"
                                         src="<?= e(imageUrl('slider', $s['image'])) ?>" alt="">
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($s['title'] ?? '—') ?></td>
                            <td>
                                <?php if ($s['link']): ?>
                                    <a href="<?= e($s['link']) ?>" target="_blank"><?= e(mb_substr($s['link'], 0, 30)) ?></a>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?= (int)$s['sort_order'] ?></td>
                            <td>
                                <?php if ($s['is_active']): ?>
                                    <span class="badge-soft badge-soft-success">فعال</span>
                                <?php else: ?>
                                    <span class="badge-soft badge-soft-danger">غیرفعال</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="<?= BASE_URL ?>/admin/slides/edit.php?id=<?= (int)$s['id'] ?>"
                                       class="btn-admin btn-admin-primary btn-admin-sm">ویرایش</a>
                                    <a href="<?= BASE_URL ?>/admin/slides/list.php?delete=<?= (int)$s['id'] ?>&token=<?= e(csrfToken()) ?>"
                                       class="btn-admin btn-admin-danger btn-admin-sm"
                                       data-confirm="این اسلاید حذف شود؟">حذف</a>
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