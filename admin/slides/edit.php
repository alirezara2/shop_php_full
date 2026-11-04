<?php
require_once dirname(__DIR__) . '/includes/admin_auth.php';

$pageTitle = 'ویرایش اسلاید';
$slideId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM slides WHERE id = ? LIMIT 1");
$stmt->execute([$slideId]);
$slide = $stmt->fetch();

if (!$slide) {
    setFlash('error', 'اسلاید یافت نشد.');
    redirect(BASE_URL . '/admin/slides/list.php');
}

$errors = [];
$data = $slide;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $data['title'] = trim($_POST['title'] ?? '');
    $data['link'] = trim($_POST['link'] ?? '');
    $data['sort_order'] = (int)($_POST['sort_order'] ?? 0);
    $data['is_active'] = isset($_POST['is_active']) ? 1 : 0;

    $newImage = null;
    if (!empty($_FILES['image']['name'])) {
        $upload = uploadImage($_FILES['image'], 'slider');
        if (!$upload['ok']) {
            $errors[] = $upload['msg'];
        } else {
            $newImage = $upload['filename'];
        }
    }

    if ($data['link'] !== '' && !filter_var($data['link'], FILTER_VALIDATE_URL) && strpos($data['link'], '/') !== 0) {
        $errors[] = 'لینک معتبر نیست.';
    }

    if (empty($errors)) {
        try {
            $sql = "UPDATE slides SET title = ?, link = ?, sort_order = ?, is_active = ?";
            $params = [
                $data['title'] ?: null,
                $data['link'] ?: null,
                $data['sort_order'],
                $data['is_active'],
            ];
            if ($newImage) {
                $sql .= ", image = ?";
                $params[] = $newImage;
            }
            $sql .= " WHERE id = ?";
            $params[] = $slideId;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            if ($newImage && $slide['image']) {
                deleteImage('slider', $slide['image']);
            }

            setFlash('success', 'اسلاید با موفقیت ویرایش شد.');
            redirect(BASE_URL . '/admin/slides/list.php');
        } catch (PDOException $e) {
            if ($newImage) deleteImage('slider', $newImage);
            $errors[] = 'خطا در ذخیره: ' . $e->getMessage();
        }
    }
}

require_once dirname(__DIR__) . '/includes/admin_header.php';
?>

<h1 class="admin-page-title">ویرایش اسلاید #<?= (int)$slideId ?></h1>

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
            <label for="image">عکس اسلاید (برای تغییر انتخاب کنید)</label>
            <?php if ($slide['image']): ?>
                <div style="margin-bottom:8px;">
                    <img src="<?= e(imageUrl('slider', $slide['image'])) ?>"
                         style="max-width:400px;border-radius:8px;border:1px solid var(--admin-border);" alt="">
                    <div class="form-hint">عکس فعلی — با آپلود عکس جدید جایگزین می‌شود.</div>
                </div>
            <?php endif; ?>
            <input type="file" id="image" name="image" class="form-control"
                   accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                   data-preview="imagePreview">
            <img id="imagePreview" class="img-preview" style="display:none;" alt="پیش‌نمایش">
        </div>

        <div class="form-group">
            <label for="title">عنوان (اختیاری)</label>
            <input type="text" id="title" name="title" class="form-control"
                   value="<?= e($data['title'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="link">لینک (اختیاری)</label>
            <input type="text" id="link" name="link" class="form-control"
                   value="<?= e($data['link'] ?? '') ?>">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="sort_order">ترتیب نمایش</label>
                <input type="number" id="sort_order" name="sort_order" class="form-control"
                       value="<?= (int)$data['sort_order'] ?>">
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
        <button type="submit" class="btn-admin btn-admin-primary">ذخیره تغییرات</button>
        <a href="<?= BASE_URL ?>/admin/slides/list.php" class="btn-admin btn-admin-secondary">انصراف</a>
    </div>
</form>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>