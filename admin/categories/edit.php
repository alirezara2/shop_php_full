<?php
require_once dirname(__DIR__) . '/includes/admin_auth.php';

$pageTitle = 'ویرایش دسته‌بندی';
$categoryId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ? LIMIT 1");
$stmt->execute([$categoryId]);
$category = $stmt->fetch();

if (!$category) {
    setFlash('error', 'دسته‌بندی یافت نشد.');
    redirect(BASE_URL . '/admin/categories/list.php');
}

$errors = [];
$data = $category;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $data['name'] = trim($_POST['name'] ?? '');
    $data['slug'] = trim($_POST['slug'] ?? '');

    if ($data['name'] === '') {
        $errors[] = 'نام دسته الزامی است.';
    }

    $data['slug'] = $data['slug'] !== '' ? strtolower(preg_replace('/[^a-z0-9\-]+/', '-', $data['slug'])) : '';
    $data['slug'] = trim($data['slug'], '-');
    if ($data['slug'] === '') {
        $data['slug'] = 'category-' . time();
    }

    // چک تکرار slug (به‌جز خودش)
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ? AND id != ? LIMIT 1");
    $stmt->execute([$data['slug'], $categoryId]);
    if ($stmt->fetch()) {
        $errors[] = 'این اسلاگ قبلاً استفاده شده.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ? WHERE id = ?");
            $stmt->execute([$data['name'], $data['slug'], $categoryId]);
            setFlash('success', 'دسته‌بندی با موفقیت ویرایش شد.');
            redirect(BASE_URL . '/admin/categories/list.php');
        } catch (PDOException $e) {
            $errors[] = 'خطا در ذخیره: ' . $e->getMessage();
        }
    }
}

require_once dirname(__DIR__) . '/includes/admin_header.php';
?>

<h1 class="admin-page-title">ویرایش دسته‌بندی #<?= (int)$categoryId ?></h1>

<?php renderFlashes(); ?>

<?php if ($errors): ?>
    <div class="flash flash-error">
        <?php foreach ($errors as $err): ?>
            <div>• <?= e($err) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="post" class="admin-form">
    <?= csrfField() ?>

    <div class="admin-card">
        <div class="form-group">
            <label for="name">نام دسته <span class="required">*</span></label>
            <input type="text" id="name" name="name" class="form-control"
                   value="<?= e($data['name']) ?>" required>
        </div>

        <div class="form-group">
            <label for="slug">اسلاگ</label>
            <input type="text" id="slug" name="slug" class="form-control"
                   value="<?= e($data['slug']) ?>">
            <div class="form-hint">فقط حروف انگلیسی، اعداد و خط تیره.</div>
        </div>
    </div>

    <div style="display:flex;gap:8px;">
        <button type="submit" class="btn-admin btn-admin-primary">ذخیره تغییرات</button>
        <a href="<?= BASE_URL ?>/admin/categories/list.php" class="btn-admin btn-admin-secondary">انصراف</a>
    </div>
</form>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>