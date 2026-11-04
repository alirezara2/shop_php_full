<?php
require_once dirname(__DIR__) . '/config/config.php';

// اگر از قبل لاگین است، برود داشبورد
if (isAdminLoggedIn()) {
    redirect(BASE_URL . '/admin/index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'نام کاربری و رمز عبور را وارد کنید.';
    } else {
        $result = attemptAdminLogin($username, $password);
        if ($result['ok']) {
            setFlash('success', 'خوش آمدید ' . $result['admin']['username']);
            redirect(BASE_URL . '/admin/index.php');
        } else {
            $error = $result['msg'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به پنل مدیریت</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet">
</head>
<body class="admin-body">

<div class="admin-login-page">
    <div class="admin-login-card">
        <h1>پنل مدیریت</h1>
        <p class="subtitle">لطفاً وارد حساب خود شوید</p>

        <?php if ($error): ?>
            <div class="flash flash-error"><?= e($error) ?></div>
        <?php endif; ?>

        <?php renderFlashes(); ?>

        <form method="post" class="admin-form" autocomplete="off">
            <?= csrfField() ?>

            <div class="form-group">
                <label for="username">نام کاربری <span class="required">*</span></label>
                <input type="text" id="username" name="username" class="form-control"
                       value="<?= e($_POST['username'] ?? '') ?>" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">رمز عبور <span class="required">*</span></label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>

            <button type="submit" class="btn-admin btn-admin-primary btn-admin-block">
                ورود به پنل
            </button>
        </form>
    </div>
</div>

</body>
</html>