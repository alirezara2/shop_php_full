<?php
require_once dirname(__DIR__) . '/config/config.php';
requireLogin();

$user = currentUser();
$orderId = (int)($_GET['order'] ?? 0);

if ($orderId <= 0) {
    setFlash('error', 'شناسه سفارش نامعتبر است.');
    redirect(BASE_URL . '/user/orders.php');
}

// ----- گرفتن سفارش -----
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->execute([$orderId, $user['id']]);
$order = $stmt->fetch();

if (!$order) {
    setFlash('error', 'سفارش یافت نشد.');
    redirect(BASE_URL . '/user/orders.php');
}

// ----- اگر قبلاً فیش آپلود شده -----
if ($order['receipt_image']) {
    setFlash('info', 'فیش شما قبلاً آپلود شده و در انتظار تایید است.');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    if (empty($_FILES['receipt']['name'])) {
        $errors[] = 'لطفاً فایل فیش را انتخاب کنید.';
    } else {
        $upload = uploadImage($_FILES['receipt'], 'receipts');
        if (!$upload['ok']) {
            $errors[] = $upload['msg'];
        } else {
            try {
                // اگر فیش قبلی داشت، حذف کن
                if ($order['receipt_image']) {
                    deleteImage('receipts', $order['receipt_image']);
                }

                // ذخیره نام فایل جدید + تغییر وضعیت به pending (برای تایید مجدد)
                $stmt = $pdo->prepare("
                    UPDATE orders 
                    SET receipt_image = ?, status = 'pending'
                    WHERE id = ? AND user_id = ?
                ");
                $stmt->execute([$upload['filename'], $orderId, $user['id']]);

                setFlash('success', 'فیش با موفقیت آپلود شد. پس از تایید ادمین، وضعیت سفارش به‌روزرسانی می‌شود.');
                redirect(BASE_URL . '/user/orders.php?id=' . $orderId);
            } catch (PDOException $e) {
                deleteImage('receipts', $upload['filename']);
                $errors[] = 'خطا در ذخیره فیش.';
            }
        }
    }
}

$pageTitle = 'آپلود فیش پرداخت';
require_once BASE_PATH . '/includes/header.php';
?>

<div class="container">
    <div class="dashboard">
        <aside class="dashboard-sidebar">
            <div class="user-info">
                <div class="name"><?= e(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'کاربر') ?></div>
                <div class="phone" dir="ltr"><?= e($user['phone']) ?></div>
            </div>
            <ul class="dashboard-menu">
                <li><a href="<?= BASE_URL ?>/user/dashboard.php">🏠 داشبورد</a></li>
                <li><a href="<?= BASE_URL ?>/user/orders.php" class="active">📦 سفارش‌های من</a></li>
                <li><a href="<?= BASE_URL ?>/user/cart.php">🛒 سبد خرید</a></li>
                <li><a href="<?= BASE_URL ?>/user/profile.php">👤 اطلاعات من</a></li>
                <li><a href="<?= BASE_URL ?>/user/logout.php" data-confirm="از حساب خود خارج می‌شوید؟">🚪 خروج</a></li>
            </ul>
        </aside>

        <div class="dashboard-content">
            <h1 class="page-title">آپلود فیش پرداخت</h1>

            <?php renderFlashes(); ?>

            <?php if ($errors): ?>
                <div class="flash flash-error">
                    <?php foreach ($errors as $err): ?>
                        <div>• <?= e($err) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- اطلاعات سفارش -->
            <div style="background:var(--bg);padding:16px;border-radius:var(--radius-sm);margin-bottom:20px;">
                <h3 style="margin-bottom:8px;font-size:1rem;">سفارش <?= e(generateOrderNumber($orderId)) ?></h3>
                <p>مبلغ قابل پرداخت: <strong style="color:var(--primary);font-size:1.1rem;"><?= formatPrice($order['total_amount']) ?></strong></p>
                <p style="margin-top:8px;color:var(--text-muted);font-size:0.9rem;">
                    لطفاً مبلغ فوق را به حساب فروشگاه واریز کنید و فیش را در کادر زیر آپلود نمایید.
                </p>
            </div>

            <!-- اطلاعات حساب فروشگاه -->
            <div style="background:#fff7e6;padding:16px;border-radius:var(--radius-sm);border:1px solid #f0d090;margin-bottom:20px;">
                <h3 style="margin-bottom:8px;font-size:0.95rem;color:#7a5a00;">اطلاعات حساب فروشگاه</h3>
                <p style="line-height:1.9;font-size:0.9rem;">
                    بانک: <strong>بانک ملت</strong><br>
                    شماره کارت: <strong dir="ltr">6104-3379-XXXX-XXXX</strong><br>
                    به نام: <strong>فروشگاه کوهنوردی</strong>
                </p>
                <p style="font-size:0.85rem;color:#7a5a00;margin-top:8px;">
                    ⚠️ توجه: اطلاعات حساب بالا نمونه است. لطفاً پس از راه‌اندازی واقعی، از پنل ادمین ویرایش کنید.
                </p>
            </div>

            <!-- فیش فعلی (اگر وجود دارد) -->
            <?php if ($order['receipt_image']): ?>
                <div style="margin-bottom:20px;">
                    <h3 style="margin-bottom:8px;font-size:0.95rem;">فیش آپلود شده فعلی:</h3>
                    <p style="color:var(--text-muted);font-size:0.9rem;">
                        وضعیت: <strong style="color:var(--warning);">در انتظار تایید ادمین</strong>
                    </p>
                    <p style="color:var(--text-muted);font-size:0.85rem;margin-top:8px;">
                        اگر می‌خواهید فیش جدید آپلود کنید، فایل قبلی جایگزین می‌شود.
                    </p>
                </div>
            <?php endif; ?>

            <!-- فرم آپلود -->
            <form method="post" enctype="multipart/form-data">
                <?= csrfField() ?>

                <div class="form-group">
                    <label>فایل فیش <span class="required">*</span></label>
                    <input type="file" name="receipt" class="form-control"
                           accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                           data-preview="receiptPreview" required>
                    <div class="form-hint">فقط jpg یا png، حداکثر ۲ مگابایت.</div>
                    <img id="receiptPreview" class="img-preview"
                         style="display:none;margin-top:12px;max-width:300px;border-radius:var(--radius-sm);"
                         alt="پیش‌نمایش فیش">
                </div>

                <button type="submit" class="btn btn-primary">📤 آپلود فیش</button>
                <a href="<?= BASE_URL ?>/user/orders.php" class="btn btn-outline">بعداً آپلود می‌کنم</a>
            </form>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>