<?php
require_once dirname(__DIR__) . '/config/config.php';
requireLogin();

$user = currentUser();

$errors = [];
$data = $user;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $data['first_name'] = trim($_POST['first_name'] ?? '');
    $data['last_name'] = trim($_POST['last_name'] ?? '');
    $data['postal_code'] = trim($_POST['postal_code'] ?? '');
    $data['city'] = trim($_POST['city'] ?? '');
    $data['district'] = trim($_POST['district'] ?? '');
    $data['street'] = trim($_POST['street'] ?? '');
    $data['floor'] = trim($_POST['floor'] ?? '');
    $data['address'] = trim($_POST['address'] ?? '');

    // اعتبارسنجی
    if ($data['first_name'] === '') $errors[] = 'نام الزامی است.';
    if ($data['last_name'] === '') $errors[] = 'نام خانوادگی الزامی است.';
    if (!isValidPostalCode($data['postal_code'])) $errors[] = 'کد پستی باید ۱۰ رقم باشد.';
    if ($data['city'] === '') $errors[] = 'شهر الزامی است.';
    if ($data['street'] === '') $errors[] = 'خیابان الزامی است.';
    if ($data['address'] === '') $errors[] = 'آدرس کامل الزامی است.';

    if (empty($errors)) {
        try {
            $isComplete = 1;
            $stmt = $pdo->prepare("
                UPDATE users SET
                    first_name = ?, last_name = ?, address = ?, postal_code = ?,
                    city = ?, district = ?, street = ?, floor = ?, is_profile_complete = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $data['first_name'],
                $data['last_name'],
                $data['address'],
                $data['postal_code'],
                $data['city'],
                $data['district'] ?: null,
                $data['street'],
                $data['floor'] ?: null,
                $isComplete,
                $user['id'],
            ]);

            // آپدیت session (کاربر جدید را دوباره بخوان)
            $user = currentUser();
            setFlash('success', 'اطلاعات با موفقیت ذخیره شد.');
            redirect(BASE_URL . '/user/profile.php');
        } catch (PDOException $e) {
            $errors[] = 'خطا در ذخیره اطلاعات.';
        }
    }
}

$pageTitle = 'اطلاعات من';
require_once BASE_PATH . '/includes/header.php';
?>

<div class="container">
    <div class="dashboard">
        <!-- سایدبار -->
        <aside class="dashboard-sidebar">
            <div class="user-info">
                <div class="name">
                    <?= e(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'کاربر') ?>
                </div>
                <div class="phone" dir="ltr"><?= e($user['phone']) ?></div>
            </div>

            <ul class="dashboard-menu">
                <li><a href="<?= BASE_URL ?>/user/dashboard.php">🏠 داشبورد</a></li>
                <li><a href="<?= BASE_URL ?>/user/orders.php">📦 سفارش‌های من</a></li>
                <li><a href="<?= BASE_URL ?>/user/cart.php">🛒 سبد خرید</a></li>
                <li><a href="<?= BASE_URL ?>/user/profile.php" class="active">👤 اطلاعات من</a></li>
                <li><a href="<?= BASE_URL ?>/user/logout.php" data-confirm="از حساب خود خارج می‌شوید؟">🚪 خروج</a></li>
            </ul>
        </aside>

        <!-- محتوا -->
        <div class="dashboard-content">
            <h1 class="page-title">اطلاعات من</h1>

            <?php renderFlashes(); ?>

            <?php if ($errors): ?>
                <div class="flash flash-error">
                    <?php foreach ($errors as $err): ?>
                        <div>• <?= e($err) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <?= csrfField() ?>

                <div class="form-row">
                    <div class="form-group">
                        <label>نام <span class="required">*</span></label>
                        <input type="text" name="first_name" class="form-control"
                               value="<?= e($data['first_name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>نام خانوادگی <span class="required">*</span></label>
                        <input type="text" name="last_name" class="form-control"
                               value="<?= e($data['last_name'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>شماره تماس (غیرقابل تغییر)</label>
                    <input type="text" class="form-control" value="<?= e($user['phone']) ?>" disabled dir="ltr">
                </div>

                <h3 style="margin:24px 0 12px;font-size:1.1rem;color:var(--primary);">آدرس ارسال</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label>شهر <span class="required">*</span></label>
                        <input type="text" name="city" class="form-control"
                               value="<?= e($data['city'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>شهرک / محله</label>
                        <input type="text" name="district" class="form-control"
                               value="<?= e($data['district'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>خیابان <span class="required">*</span></label>
                        <input type="text" name="street" class="form-control"
                               value="<?= e($data['street'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>طبقه / واحد</label>
                        <input type="text" name="floor" class="form-control"
                               value="<?= e($data['floor'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>کد پستی <span class="required">*</span></label>
                        <input type="text" name="postal_code" class="form-control"
                               value="<?= e($data['postal_code'] ?? '') ?>" maxlength="10" dir="ltr" required>
                        <div class="form-hint">۱۰ رقم، بدون خط تیره</div>
                    </div>
                </div>

                <div class="form-group">
                    <label>آدرس کامل <span class="required">*</span></label>
                    <textarea name="address" class="form-control" rows="3" required><?= e($data['address'] ?? '') ?></textarea>
                    <div class="form-hint">آدرس دقیق پستی خود را وارد کنید.</div>
                </div>

                <button type="submit" class="btn btn-primary">ذخیره اطلاعات</button>
            </form>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>