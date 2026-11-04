<?php
require_once dirname(__DIR__) . '/includes/admin_auth.php';

$pageTitle = 'افزودن دسته‌بندی جدید';

$errors = [];
$data = ['name' => '', 'slug' => ''];

/**
 * ساخت اسلاگ تمیز از نام (فقط حروف انگلیسی، اعداد و خط تیره)
 */
function makeSlug(string $text): string {
    $text = trim($text);
    // اگر انگلیسی است
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\-]+/', '-', $text);
    $text = trim($text, '-');
    return $text !== '' ? $text : 'category-' . time();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $data['name'] = trim($_POST['name'] ?? '');
    $data['slug'] = trim($_POST['slug'] ?? '');

    if ($data['name'] === '') {
        $errors[] = 'نام دسته الزامی است.';
    }

    if ($data['slug'] === '') {
        $data['slug'] = makeSlug($data['name']);
    } else {
        $data['slug'] = makeSlug($data['slug']);
    }

    // چک تکراری بودن slug
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ? LIMIT 1");
    $stmt->execute([$data['slug']]);
    if ($stmt->fetch()) {
        $errors[] = 'این اسلاگ قبلاً استفاده شده. اسلاگ دیگری وارد کنید.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
            $stmt->execute([$data['name'], $data['slug']]);
            setFlash('success', 'دسته‌بندی با موفقیت اضافه شد.');
            redirect(BASE_URL . '/admin/categories/list.php');
        } catch (PDOException $e) {
            $errors[] = 'خطا در ذخیره: ' . $e->getMessage();
        }
    }
}

require_once dirname(__DIR__) . '/includes/admin_header.php';
?>

<h1 class="admin-page-title">افزودن دسته‌بندی جدید</h1>

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
                   value="<?= e($data['name']) ?>" required autofocus>
        </div>

        <div class="form-group">
            <label for="slug">اسلاگ (اختیاری)</label>
            <input type="text" id="slug" name="slug" class="form-control"
                   value="<?= e($data['slug']) ?>" placeholder="مثلاً: shoes">
            <div class="form-hint">فقط حروف انگلیسی، اعداد و خط تیره. اگر خالی بگذاری، خودکار ساخته می‌شود.</div>
        </div>
    </div>

    <div style="display:flex;gap:8px;">
        <button type="submit" class="btn-admin btn-admin-primary">ذخیره</button>
        <a href="<?= BASE_URL ?>/admin/categories/list.php" class="btn-admin btn-admin-secondary">انصراف</a>
    </div>
</form>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>