<?php
require_once dirname(__DIR__) . '/config/config.php';

if (isLoggedIn()) {
    redirect(BASE_URL . '/user/dashboard.php');
}

$errors = [];
$oldPhone = '';
$redirectAfterLogin = $_SESSION['redirect_after_login'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';
    $oldPhone = $phone;

    $result = attemptUserLogin($phone, $password);

    if ($result['ok']) {
        setFlash('success', 'خوش آمدید!');
        unset($_SESSION['redirect_after_login']);

        if ($redirectAfterLogin) {
            redirect($redirectAfterLogin);
        }
        redirect(BASE_URL . '/user/dashboard.php');
    } else {
        $errors[] = $result['msg'];
    }
}

$pageTitle = 'ورود';
require_once BASE_PATH . '/includes/header.php';
?>

<div class="auth-page">
    <div class="auth-card">
        <h2>ورود</h2>
        <p class="subtitle">به حساب کاربری خود وارد شوید</p>

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
            </div>

            <div class="form-group">
                <label>رمز عبور <span class="required">*</span></label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block">ورود</button>
        </form>

        <div class="auth-footer">
            حساب ندارید؟ 
            <a href="<?= BASE_URL ?>/user/register.php">ثبت‌نام کنید</a>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>