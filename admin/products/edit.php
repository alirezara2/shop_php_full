<?php
require_once dirname(__DIR__) . '/includes/admin_auth.php';

$pageTitle = 'ویرایش محصول';
$productId = (int)($_GET['id'] ?? 0);

// گرفتن محصول
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    setFlash('error', 'محصول یافت نشد.');
    redirect(BASE_URL . '/admin/products/list.php');
}

$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();

$errors = [];
$data = $product;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $data['title'] = trim($_POST['title'] ?? '');
    $data['category_id'] = (int)($_POST['category_id'] ?? 0);
    $data['short_description'] = trim($_POST['short_description'] ?? '');
    $data['description'] = trim($_POST['description'] ?? '');
    $data['price'] = (int)($_POST['price'] ?? 0);
    $data['discount_price'] = $_POST['discount_price'] !== '' ? (int)$_POST['discount_price'] : null;
    $data['stock'] = (int)($_POST['stock'] ?? 0);
    $data['is_new'] = isset($_POST['is_new']) ? 1 : 0;
    $data['is_active'] = isset($_POST['is_active']) ? 1 : 0;

    if ($data['title'] === '') {
        $errors[] = 'نام محصول الزامی است.';
    }
    if ($data['price'] <= 0) {
        $errors[] = 'قیمت محصول باید بزرگ‌تر از صفر باشد.';
    }
    if ($data['discount_price'] !== null && $data['discount_price'] >= $data['price']) {
        $errors[] = 'قیمت تخفیف باید کمتر از قیمت اصلی باشد.';
    }

    $newImage = null;
    if (!empty($_FILES['image']['name'])) {
        $upload = uploadImage($_FILES['image'], 'products');
        if (!$upload['ok']) {
            $errors[] = $upload['msg'];
        } else {
            $newImage = $upload['filename'];
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE products SET
                    category_id = ?, title = ?, short_description = ?, description = ?,
                    price = ?, discount_price = ?, stock = ?, is_new = ?, is_active = ?
                    " . ($newImage ? ", image = ?" : "") . "
                WHERE id = ?
            ");

            $params = [
                $data['category_id'] ?: null,
                $data['title'],
                $data['short_description'] ?: null,
                $data['description'] ?: null,
                $data['price'],
                $data['discount_price'],
                $data['stock'],
                $data['is_new'],
                $data['is_active'],
            ];
            if ($newImage) {
                $params[] = $newImage;
            }
            $params[] = $productId;

            $stmt->execute($params);

            if ($newImage && $product['image']) {
                deleteImage('products', $product['image']);
            }

            setFlash('success', 'محصول با موفقیت ویرایش شد.');
            redirect(BASE_URL . '/admin/products/list.php');
        } catch (PDOException $e) {
            $errors[] = 'خطا در ذخیره: ' . $e->getMessage();
            if ($newImage) deleteImage('products', $newImage);
        }
    }
}

require_once dirname(__DIR__) . '/includes/admin_header.php';
?>

<h1 class="admin-page-title">ویرایش محصول #<?= (int)$productId ?></h1>

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
        <div class="admin-card-header">
            <h2>اطلاعات اصلی</h2>
        </div>

        <div class="form-group">
            <label for="title">نام محصول <span class="required">*</span></label>
            <input type="text" id="title" name="title" class="form-control"
                   value="<?= e($data['title']) ?>" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="category_id">دسته‌بندی</label>
                <select id="category_id" name="category_id" class="form-control">
                    <option value="0">— بدون دسته —</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= $data['category_id'] == $c['id'] ? 'selected' : '' ?>>
                            <?= e($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="stock">موجودی</label>
                <input type="number" id="stock" name="stock" class="form-control"
                       value="<?= (int)$data['stock'] ?>" min="0">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="price">قیمت اصلی (تومان) <span class="required">*</span></label>
                <input type="number" id="price" name="price" class="form-control"
                       value="<?= (int)$data['price'] ?>" min="1" required>
            </div>

            <div class="form-group">
                <label for="discount_price">قیمت تخفیف‌خورده (اختیاری)</label>
                <input type="number" id="discount_price" name="discount_price" class="form-control"
                       value="<?= $data['discount_price'] !== null ? (int)$data['discount_price'] : '' ?>" min="0">
            </div>
        </div>

        <div class="form-group">
            <label for="short_description">توضیحات کوتاه</label>
            <textarea id="short_description" name="short_description" class="form-control" rows="2"
                      maxlength="500"><?= e($data['short_description']) ?></textarea>
        </div>

        <div class="form-group">
            <label for="description">توضیحات کامل</label>
            <textarea id="description" name="description" class="form-control" rows="8"><?= e($data['description']) ?></textarea>
        </div>

        <div class="form-group">
            <label for="image">تغییر عکس محصول</label>
            <?php if ($product['image']): ?>
                <div style="margin-bottom:8px;">
                    <img src="<?= e(imageUrl('products', $product['image'])) ?>"
                         class="img-preview" alt="عکس فعلی">
                    <div class="form-hint">عکس فعلی — با آپلود عکس جدید، جایگزین می‌شود.</div>
                </div>
            <?php endif; ?>
            <input type="file" id="image" name="image" class="form-control"
                   accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                   data-preview="imagePreview">
            <img id="imagePreview" class="img-preview" style="display:none;" alt="پیش‌نمایش">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_new" value="1" <?= $data['is_new'] ? 'checked' : '' ?>>
                    محصول جدید
                </label>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_active" value="1" <?= $data['is_active'] ? 'checked' : '' ?>>
                    فعال
                </label>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:8px;">
        <button type="submit" class="btn-admin btn-admin-primary">ذخیره تغییرات</button>
        <a href="<?= BASE_URL ?>/admin/products/list.php" class="btn-admin btn-admin-secondary">انصراف</a>
    </div>
</form>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>