<?php
require_once dirname(__DIR__) . '/includes/admin_auth.php';

$pageTitle = 'افزودن اسلاید جدید';

$errors = [];
$data = [
    'title' => '',
    'link' => '',
    'sort_order' => 0,
    'is_active' => 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $data['title'] = trim($_POST['title'] ?? '');
    $data['link'] = trim($_POST['link'] ?? '');
    $data['sort_order'] = (int)($_POST['sort_order'] ?? 0);
    $data['is_active'] = isset($_POST['is_active']) ? 1 : 0;

    $imageName = null;
    if (empty($_FILES['image']['name'])) {
        $errors[] = 'عکس اسلاید الزامی است.';
    } else {
        $upload = uploadImage($_FILES['image'], 'slider');
        if (!$upload['ok']) {
            $errors[] = $upload['msg'];
        } else {
            $imageName = $upload['filename'];
        }
    }

    if ($data['link'] !== '' && !filter_var($data['link'], FILTER_VALIDATE_URL) && strpos($data['link'], '/') !== 0) {
        $errors[] = 'لینک معتبر نیست (آدرس کامل یا مسیر نسبی مثل /shop.php وارد کنید).';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO slides (image, title, link, sort_order, is_active)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $imageName,
                $data['title'] ?: null,
                $data['link'] ?: null,
                $data['sort_order'],
                $data['is_active'],
            ]);
            setFlash('success', 'اسلاید با موفقیت اضافه شد.');
            redirect(BASE_URL . '/admin/slides/list.php');
        } catch (PDOException $e) {
            if ($imageName) deleteImage('slider', $imageName);
            $errors[] = 'خطا در ذخیره: ' . $e->getMessage();
        }
    }
}

require_once dirname(__DIR__) . '/includes/admin_header.php';
?>

<h1 class="admin-page-title">افزودن اسلاید جدید</h1>

<?php renderFlashes(); ?>

<?php if ($errors): ?>
    <div class="flash flash-error">
        <?php foreach ($errors as $err): ?>
            <div>• <?= e($err) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="admin-form">
    <?= csrfField() ?>

    <div class="admin-card">
        <div class="form-group">
            <label for="image">عکس اسلاید <span class="required">*</span></label>
            <input type="file" id="image" name="image" class="form-control"
                   accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                   data-preview="imagePreview" required>
            <div class="form-hint">پیشنهاد: ۱۹۲۰×۶۰۰ پیکسل، فقط jpg/png، حداکثر ۲ مگابایت.</div>
            <img id="imagePreview" class="img-preview" style="display:none;" alt="پیش‌نمایش">
        </div>

        <div class="form-group">
            <label for="title">عنوان (اختیاری)</label>
            <input type="text" id="title" name="title" class="form-control"
                   value="<?= e($data['title']) ?>">
        </div>

        <div class="form-group">
            <label for="link">لینک (اختیاری)</label>
            <input type="text" id="link" name="link" class="form-control"
                   value="<?= e($data['link']) ?>" placeholder="https://... یا /shop.php">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="sort_order">ترتیب نمایش</label>
                <input type="number" id="sort_order" name="sort_order" class="form-control"
                       value="<?= (int)$data['sort_order'] ?>">
                <div class="form-hint">عدد کوچک‌تر اول نمایش داده می‌شود.</div>
            </div>

            <div class="form-group" style="display:flex;align-items:center;padding-top:28px;">
                <label>
                    <input type="checkbox" name="is_active" value="1" <?= $data['is_active'] ? 'checked' : '' ?>>
                    فعال
                </label>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:8px;">
        <button type="submit" class="btn-admin btn-admin-primary">ذخیره اسلاید</button>
        <a href="<?= BASE_URL ?>/admin/slides/list.php" class="btn-admin btn-admin-secondary">انصراف</a>
    </div>
</form>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>