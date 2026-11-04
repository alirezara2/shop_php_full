<?php
if (!defined('BASE_PATH')) { die('دسترسی مستقیم مجاز نیست.'); }

$errors = [];
$data = ['email' => '', 'subject' => '', 'body' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $data['email'] = trim($_POST['email'] ?? '');
    $data['subject'] = trim($_POST['subject'] ?? '');
    $data['body'] = trim($_POST['body'] ?? '');

    // اعتبارسنجی
    if ($data['email'] === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'ایمیل معتبر وارد کنید.';
    }
    if ($data['subject'] === '') {
        $errors[] = 'موضوع پیام الزامی است.';
    }
    if ($data['body'] === '') {
        $errors[] = 'متن پیام الزامی است.';
    }
    if (mb_strlen($data['subject']) > 200) {
        $errors[] = 'موضوع پیام طولانی است (حداکثر ۲۰۰ کاراکتر).';
    }

    // آپلود عکس (اختیاری)
    $imageName = null;
    if (!empty($_FILES['image']['name'])) {
        $upload = uploadImage($_FILES['image'], 'contact');
        if (!$upload['ok']) {
            $errors[] = $upload['msg'];
        } else {
            $imageName = $upload['filename'];
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO contact_messages (email, subject, body, image, is_read)
                VALUES (?, ?, ?, ?, 0)
            ");
            $stmt->execute([$data['email'], $data['subject'], $data['body'], $imageName]);

            setFlash('success', 'پیام شما با موفقیت ارسال شد. به‌زودی پاسخ داده می‌شود.');
            redirect(BASE_URL . '/index.php?page=contact');
        } catch (PDOException $e) {
            if ($imageName) deleteImage('contact', $imageName);
            $errors[] = 'خطا در ارسال پیام. لطفاً بعداً تلاش کنید.';
        }
    }
}

$contactPhone = getSetting('contact_phone', '');
$contactEmail = getSetting('contact_email', '');
$contactAddress = getSetting('contact_address', '');

require_once BASE_PATH . '/includes/header.php';
?>

<section class="container">
    <div class="section">
        <div class="section-header">
            <h1 class="section-title">📞 ارتباط با ما</h1>
        </div>

        <?php renderFlashes(); ?>

        <?php if ($errors): ?>
            <div class="flash flash-error">
                <?php foreach ($errors as $err): ?>
                    <div>• <?= e($err) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="contact-grid">
            <!-- فرم -->
            <div class="contact-form-card">
                <h3 style="margin-bottom:20px;">فرم تماس</h3>

                <form method="post" enctype="multipart/form-data">
                    <?= csrfField() ?>

                    <div class="form-group">
                        <label>ایمیل <span class="required">*</span></label>
                        <input type="email" name="email" class="form-control"
                               value="<?= e($data['email']) ?>" required>
                        <div class="form-hint">پاسخ ما به این ایمیل ارسال می‌شود.</div>
                    </div>

                    <div class="form-group">
                        <label>موضوع <span class="required">*</span></label>
                        <input type="text" name="subject" class="form-control"
                               value="<?= e($data['subject']) ?>" maxlength="200" required>
                    </div>

                    <div class="form-group">
                        <label>توضیحات <span class="required">*</span></label>
                        <textarea name="body" class="form-control" rows="6" required><?= e($data['body']) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>عکس پیوست (اختیاری)</label>
                        <input type="file" name="image" class="form-control"
                               accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                               data-preview="contactImagePreview">
                        <div class="form-hint">فقط jpg یا png، حداکثر ۲ مگابایت.</div>
                        <img id="contactImagePreview" class="img-preview" style="display:none;margin-top:8px;max-width:200px;border-radius:8px;" alt="پیش‌نمایش">
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">ارسال پیام</button>
                </form>
            </div>

            <!-- اطلاعات تماس -->
            <div class="contact-info-card">
                <h3>راه‌های ارتباطی</h3>

                <?php if ($contactPhone): ?>
                    <div class="contact-info-item">
                        <span class="icon">📞</span>
                        <div>
                            <div style="font-weight:600;">تلفن تماس</div>
                            <div dir="ltr" style="text-align:right;"><?= e($contactPhone) ?></div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($contactEmail): ?>
                    <div class="contact-info-item">
                        <span class="icon">✉️</span>
                        <div>
                            <div style="font-weight:600;">ایمیل</div>
                            <div><?= e($contactEmail) ?></div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($contactAddress): ?>
                    <div class="contact-info-item">
                        <span class="icon">📍</span>
                        <div>
                            <div style="font-weight:600;">آدرس</div>
                            <div><?= e($contactAddress) ?></div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="contact-info-item">
                    <span class="icon">⏰</span>
                    <div>
                        <div style="font-weight:600;">ساعات پاسخگویی</div>
                        <div>شنبه تا پنجشنبه، ۹ صبح تا ۶ عصر</div>
                    </div>
                </div>

                <div style="margin-top:24px;padding-top:16px;border-top:1px solid var(--border);font-size:0.9rem;color:var(--text-muted);line-height:1.9;">
                    در صورت داشتن هرگونه سوال یا انتقاد، از طریق فرم روبرو با ما در ارتباط باشید.
                    تیم پشتیبانی ما در سریع‌ترین زمان ممکن پاسخگوی شما خواهد بود.
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>