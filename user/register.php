<?php
require_once dirname(__DIR__) . '/config/config.php';

if (isLoggedIn()) {
    redirect(BASE_URL . '/user/dashboard.php');
}

$errors = [];
$oldPhone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    $oldPhone = $phone;

    $normalizedPhone = normalizePhone($phone);

    if (!isValidPhone($normalizedPhone)) {
        $errors[] = 'شماره تماس باید با ۰۹ شروع شده و ۱۱ رقم باشد.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'رمز عبور باید حداقل ۶ کاراکتر باشد.';
    }
    if ($password !== $passwordConfirm) {
        $errors[] = 'رمز عبور و تکرار آن یکسان نیستند.';
    }

    if (empty($errors)) {
        $result = registerUser($normalizedPhone, $password);
        if ($result['ok']) {
            // بعد از ثبت‌نام، خودکار لاگین کن
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$result['user_id']]);
            $user = $stmt->fetch();
            loginUser($user);

            setFlash('success', 'ثبت‌نام با موفقیت انجام شد. لطفاً اطلاعات خود را تکمیل کنید.');
            redirect(BASE_URL . '/user/profile.php');
        } else {
            $errors[] = $result['msg'];
        }
    }
}

$pageTitle = 'ثبت‌نام';
require_once BASE_PATH . '/includes/header.php';
?>

<div class="auth-page">
    <div class="auth-card">
        <h2>ثبت‌نام</h2>
        <p class="subtitle">با شماره تماس خود ثبت‌نام کنید</p>

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

            <div class="form-group">
                <label>شماره تماس <span class="required">*</span></label>
                <input type="tel" name="phone" class="form-control" 
                       value="<?= e($oldPhone) ?>" 
                       placeholder="09xxxxxxxxx" required autofocus dir="ltr">
                <div class="form-hint">مثال: ۰۹۱۲۳۴۵۶۷۸۹</div>
            </div>

            <div class="form-group">
                <label>رمز عبور <span class="required">*</span></label>
                <input type="password" name="password" class="form-control" required>
                <div class="form-hint">حداقل ۶ کاراکتر</div>
            </div>

            <div class="form-group">
                <label>تکرار رمز عبور <span class="required">*</span></label>
                <input type="password" name="password_confirm" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block">ثبت‌نام</button>
        </form>

        <div class="auth-footer">
            قبلاً ثبت‌نام کرده‌اید؟ 
            <a href="<?= BASE_URL ?>/user/login.php">وارد شوید</a>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>