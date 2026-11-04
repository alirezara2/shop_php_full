<?php
require_once dirname(__DIR__) . '/includes/admin_auth.php';

$pageTitle = 'تنظیمات سایت';

// ----- فیلدهای مجاز تنظیمات -----
$allowedKeys = [
    'site_name',
    'site_description',
    'site_logo',
    'footer_text',
    'contact_phone',
    'contact_email',
    'contact_address',
];

// ----- ذخیره تنظیمات -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $errors = [];

    // متن‌ها
    foreach (['site_name', 'site_description', 'footer_text', 'contact_phone', 'contact_email', 'contact_address'] as $key) {
        $value = trim($_POST[$key] ?? '');
        if ($key === 'site_name' && $value === '') {
            $errors[] = 'نام سایت الزامی است.';
        }
        if ($key === 'contact_email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'ایمیل تماس معتبر نیست.';
        }
    }

    // لوگو
    $newLogo = null;
    if (!empty($_FILES['site_logo']['name'])) {
        $upload = uploadImage($_FILES['site_logo'], 'site');
        if (!$upload['ok']) {
            $errors[] = 'لوگو: ' . $upload['msg'];
        } else {
            $newLogo = $upload['filename'];
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO settings (setting_key, setting_value)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");

            foreach (['site_name', 'site_description', 'footer_text', 'contact_phone', 'contact_email', 'contact_address'] as $key) {
                $stmt->execute([$key, trim($_POST[$key] ?? '')]);
            }

            if ($newLogo) {
                // لوگوی قبلی را حذف کن
                $oldLogo = getSetting('site_logo');
                $stmt->execute(['site_logo', $newLogo]);
                if ($oldLogo) {
                    deleteImage('site', $oldLogo);
                }
            }

            $pdo->commit();
            setFlash('success', 'تنظیمات با موفقیت ذخیره شد.');
            redirect(BASE_URL . '/admin/settings/index.php');
        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($newLogo) deleteImage('site', $newLogo);
            $errors[] = 'خطا در ذخیره: ' . $e->getMessage();
        }
    }

    if ($errors) {
        foreach ($errors as $err) {
            setFlash('error', $err);
        }
    }
}

// ----- گرفتن مقادیر فعلی -----
$settings = [];
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$get = fn($k, $d = '') => $settings[$k] ?? $d;

require_once dirname(__DIR__) . '/includes/admin_header.php';
?>

<h1 class="admin-page-title">تنظیمات سایت</h1>

<?php renderFlashes(); ?>

<form method="post" enctype="multipart/form-data" class="admin-form">
    <?= csrfField() ?>

    <!-- اطلاعات اصلی -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2>اطلاعات اصلی</h2>
        </div>

        <div class="form-group">
            <label for="site_name">نام سایت <span class="required">*</span></label>
            <input type="text" id="site_name" name="site_name" class="form-control"
                   value="<?= e($get('site_name', 'کوهنورد شاپ')) ?>" required>
        </div>

        <div class="form-group">
            <label for="site_description">توضیح کوتاه سایت</label>
            <input type="text" id="site_description" name="site_description" class="form-control"
                   value="<?= e($get('site_description')) ?>">
            <div class="form-hint">در فوتر و متا تگ نمایش داده می‌شود.</div>
        </div>

        <div class="form-group">
            <label for="site_logo">لوگو</label>
            <?php if ($get('site_logo')): ?>
                <div style="margin-bottom:8px;">
                    <img src="<?= e(imageUrl('site', $get('site_logo'))) ?>"
                         style="max-height:80px;background:#f4f6f9;padding:8px;border-radius:8px;" alt="لوگو">
                    <div class="form-hint">لوگوی فعلی</div>
                </div>
            <?php endif; ?>
            <input type="file" id="site_logo" name="site_logo" class="form-control"
                   accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                   data-preview="logoPreview">
            <div class="form-hint">پیشنهاد: PNG با پس‌زمینه شفاف، ارتفاع ۶۰ پیکسل.</div>
            <img id="logoPreview" class="img-preview" style="display:none;background:#f4f6f9;padding:8px;" alt="پیش‌نمایش">
        </div>
    </div>

    <!-- تماس -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2>اطلاعات تماس</h2>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="contact_phone">تلفن تماس</label>
                <input type="text" id="contact_phone" name="contact_phone" class="form-control"
                       value="<?= e($get('contact_phone')) ?>">
            </div>

            <div class="form-group">
                <label for="contact_email">ایمیل تماس</label>
                <input type="email" id="contact_email" name="contact_email" class="form-control"
                       value="<?= e($get('contact_email')) ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="contact_address">آدرس</label>
            <textarea id="contact_address" name="contact_address" class="form-control" rows="2"><?= e($get('contact_address')) ?></textarea>
        </div>
    </div>

    <!-- فوتر -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2>فوتر</h2>
        </div>

        <div class="form-group">
            <label for="footer_text">متن فوتر</label>
            <textarea id="footer_text" name="footer_text" class="form-control" rows="2"><?= e($get('footer_text', 'تمام حقوق محفوظ است.')) ?></textarea>
        </div>
    </div>

    <div style="display:flex;gap:8px;">
        <button type="submit" class="btn-admin btn-admin-primary">ذخیره تنظیمات</button>
        <a href="<?= BASE_URL ?>/admin/index.php" class="btn-admin btn-admin-secondary">بازگشت</a>
    </div>
</form>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>