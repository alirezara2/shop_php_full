<?php
require_once dirname(__DIR__) . '/config/config.php';
requireLogin();

$user = currentUser();

// ----- چک پروفایل کامل -----
if (!isProfileComplete($user)) {
    setFlash('warning', 'برای ادامه خرید ابتدا اطلاعات خود را کامل کنید.');
    redirect(BASE_URL . '/user/profile.php');
}

$items = cartItems();
if (empty($items)) {
    setFlash('error', 'سبد خرید شما خالی است.');
    redirect(BASE_URL . '/user/cart.php');
}

// ----- چک موجودی -----
$stockErrors = cartValidateStock();
if (!empty($stockErrors)) {
    foreach ($stockErrors as $err) setFlash('error', $err);
    redirect(BASE_URL . '/user/cart.php');
}

$total = cartTotal();
$errors = [];

// ----- ثبت سفارش -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    try {
        $pdo->beginTransaction();

        // آدرس ارسال از پروفایل کاربر
        $shippingName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        $shippingAddress = implode('، ', array_filter([
            $user['city'] ?? '',
            $user['district'] ?? '',
            $user['street'] ?? '',
            $user['floor'] ?? '',
            $user['address'] ?? '',
        ]));
        $shippingPostal = $user['postal_code'] ?? '';
        $shippingCity = $user['city'] ?? '';
        $shippingPhone = $user['phone'] ?? '';

        // درج سفارش
        $stmt = $pdo->prepare("
            INSERT INTO orders
            (user_id, total_amount, status, shipping_address, shipping_postal_code,
             shipping_city, shipping_phone, shipping_name)
            VALUES (?, ?, 'pending', ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user['id'],
            $total,
            $shippingAddress,
            $shippingPostal,
            $shippingCity,
            $shippingPhone,
            $shippingName,
        ]);
        $orderId = (int)$pdo->lastInsertId();

        // درج اقلام
        $itemStmt = $pdo->prepare("
            INSERT INTO order_items
            (order_id, product_id, product_title, quantity, unit_price, total_price)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        foreach ($items as $item) {
            $itemStmt->execute([
                $orderId,
                $item['product']['id'],
                $item['product']['title'],
                $item['quantity'],
                $item['unit_price'],
                $item['total'],
            ]);
        }

        $pdo->commit();

        // خالی کردن سبد
        cartClear();

        setFlash('success', 'سفارش شما با شماره ' . generateOrderNumber($orderId) . ' ثبت شد. لطفاً فیش پرداخت را آپلود کنید.');
        redirect(BASE_URL . '/user/upload_receipt.php?order=' . $orderId);

    } catch (PDOException $e) {
        $pdo->rollBack();
        $errors[] = 'خطا در ثبت سفارش. لطفاً بعداً تلاش کنید.';
        // برای دیباگ: $errors[] = $e->getMessage();
    }
}

$pageTitle = 'ثبت سفارش';
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
                <li><a href="<?= BASE_URL ?>/user/orders.php">📦 سفارش‌های من</a></li>
                <li><a href="<?= BASE_URL ?>/user/cart.php">🛒 سبد خرید</a></li>
                <li><a href="<?= BASE_URL ?>/user/profile.php">👤 اطلاعات من</a></li>
                <li><a href="<?= BASE_URL ?>/user/logout.php" data-confirm="از حساب خود خارج می‌شوید؟">🚪 خروج</a></li>
            </ul>
        </aside>

        <div class="dashboard-content">
            <h1 class="page-title">تأیید و ثبت سفارش</h1>

            <?php renderFlashes(); ?>

            <?php if ($errors): ?>
                <div class="flash flash-error">
                    <?php foreach ($errors as $err): ?>
                        <div>• <?= e($err) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- آدرس ارسال -->
            <div class="admin-card" style="background:var(--bg);padding:16px;border-radius:var(--radius-sm);margin-bottom:16px;">
                <h3 style="margin-bottom:8px;font-size:1rem;">📍 آدرس ارسال</h3>
                <p style="line-height:1.9;">
                    <strong><?= e(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ?></strong><br>
                    <?= e($user['city'] ?? '') ?>، 
                    <?= e($user['district'] ?? '') ?>، 
                    <?= e($user['street'] ?? '') ?>، 
                    <?= e($user['floor'] ?? '') ?><br>
                    <?= e($user['address'] ?? '') ?><br>
                    کد پستی: <?= e($user['postal_code']) ?> | تماس: <span dir="ltr"><?= e($user['phone']) ?></span>
                </p>
                <a href="<?= BASE_URL ?>/user/profile.php" class="btn btn-outline btn-sm" style="margin-top:8px;">ویرایش آدرس</a>
            </div>

            <!-- خلاصه اقلام -->
            <h3 style="margin-bottom:12px;font-size:1rem;">🛍️ اقلام سفارش</h3>
            <div style="overflow-x:auto;margin-bottom:16px;">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>محصول</th>
                            <th>تعداد</th>
                            <th>قیمت واحد</th>
                            <th>جمع</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?= e($item['product']['title']) ?></td>
                                <td><?= (int)$item['quantity'] ?></td>
                                <td><?= formatPrice($item['unit_price']) ?></td>
                                <td><?= formatPrice($item['total']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- جمع کل -->
            <div class="cart-summary">
                <div class="cart-total">
                    مبلغ کل قابل پرداخت: <span class="amount"><?= formatPrice($total) ?></span>
                </div>
            </div>

            <!-- دکمه ثبت نهایی -->
            <form method="post" style="margin-top:24px;">
                <?= csrfField() ?>
                <div style="background:#fff7e6;padding:16px;border-radius:var(--radius-sm);border:1px solid #f0d090;margin-bottom:16px;">
                    <p style="font-size:0.95rem;color:#7a5a00;">
                        ℹ️ بعد از ثبت سفارش، شما به صفحه آپلود فیش بانکی هدایت می‌شوید.
                        پس از واریز مبلغ به حساب فروشگاه، فیش را آپلود کنید تا ادمین آن را تایید کند.
                    </p>
                </div>
                <button type="submit" class="btn btn-primary" style="padding:14px 32px;font-size:1rem;">
                    ✅ ثبت نهایی سفارش
                </button>
                <a href="<?= BASE_URL ?>/user/cart.php" class="btn btn-outline">بازگشت به سبد</a>
            </form>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>