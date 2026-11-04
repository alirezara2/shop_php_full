<?php
require_once dirname(__DIR__) . '/config/config.php';
requireLogin();

$user = currentUser();
$viewId = (int)($_GET['id'] ?? 0);

// ============================================
// نمایش جزئیات یک سفارش
// ============================================
if ($viewId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? LIMIT 1");
    $stmt->execute([$viewId, $user['id']]);
    $order = $stmt->fetch();

    if (!$order) {
        setFlash('error', 'سفارش یافت نشد.');
        redirect(BASE_URL . '/user/orders.php');
    }

    $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmt->execute([$viewId]);
    $items = $stmt->fetchAll();

    $pageTitle = 'جزئیات سفارش';
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
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
                    <h1 class="page-title" style="margin:0;">سفارش <?= e(generateOrderNumber($viewId)) ?></h1>
                    <a href="<?= BASE_URL ?>/user/orders.php" class="btn btn-outline btn-sm">← بازگشت</a>
                </div>

                <?php renderFlashes(); ?>

                <!-- وضعیت -->
                <div style="background:var(--bg);padding:16px;border-radius:var(--radius-sm);margin-bottom:16px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
                        <div>
                            <div class="text-muted" style="font-size:0.85rem;">وضعیت سفارش</div>
                            <span class="status-badge" style="background:<?= e(statusColor($order['status'])) ?>;font-size:0.95rem;padding:6px 14px;">
                                <?= e(statusLabel($order['status'])) ?>
                            </span>
                        </div>
                        <div style="text-align:left;">
                            <div class="text-muted" style="font-size:0.85rem;">تاریخ ثبت</div>
                            <div><?= e(date('Y/m/d H:i', strtotime($order['created_at']))) ?></div>
                        </div>
                    </div>
                </div>

                <!-- هشدار فیش -->
                <?php if ($order['status'] === 'pending'): ?>
                    <div class="flash flash-info">
                        <?php if ($order['receipt_image']): ?>
                            ⏳ فیش شما آپلود شده و در انتظار تایید ادمین است.
                        <?php else: ?>
                            ⚠️ هنوز فیش پرداخت آپلود نکرده‌اید.
                        <?php endif; ?>
                        <a href="<?= BASE_URL ?>/user/upload_receipt.php?order=<?= (int)$order['id'] ?>" style="font-weight:600;">
                            <?= $order['receipt_image'] ? 'تغییر فیش' : 'آپلود فیش' ?>
                        </a>
                    </div>
                <?php endif; ?>

                <!-- اقلام -->
                <h3 style="font-size:1rem;margin-bottom:12px;">اقلام سفارش</h3>
                <div style="overflow-x:auto;margin-bottom:16px;">
                    <table class="data-table">
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
                                    <td><?= e($item['product_title']) ?></td>
                                    <td><?= (int)$item['quantity'] ?></td>
                                    <td><?= formatPrice($item['unit_price']) ?></td>
                                    <td><?= formatPrice($item['total_price']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" style="text-align:left;font-weight:600;">جمع کل:</td>
                                <td><strong><?= formatPrice($order['total_amount']) ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- آدرس -->
                <h3 style="font-size:1rem;margin-bottom:12px;">آدرس ارسال</h3>
                <div style="background:var(--bg);padding:16px;border-radius:var(--radius-sm);line-height:1.9;margin-bottom:16px;">
                    <strong><?= e($order['shipping_name'] ?? '') ?></strong><br>
                    <?= e($order['shipping_city'] ?? '') ?>، <?= e($order['shipping_address'] ?? '') ?><br>
                    کد پستی: <?= e($order['shipping_postal_code'] ?? '—') ?><br>
                    تماس: <span dir="ltr"><?= e($order['shipping_phone'] ?? '—') ?></span>
                </div>

                <!-- یادداشت ادمین (اگر وجود دارد) -->
                <?php if (!empty($order['admin_note'])): ?>
                    <h3 style="font-size:1rem;margin-bottom:12px;">یادداشت فروشنده</h3>
                    <div style="background:#eaf2f8;padding:16px;border-radius:var(--radius-sm);color:#21618c;line-height:1.9;">
                        <?= nl2br(e($order['admin_note'])) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php
    require_once BASE_PATH . '/includes/footer.php';
    exit;
}

// ============================================
// لیست همه سفارش‌ها
// ============================================
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$user['id']]);
$orders = $stmt->fetchAll();

$pageTitle = 'سفارش‌های من';
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
            <h1 class="page-title">سفارش‌های من</h1>

            <?php renderFlashes(); ?>

            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <div class="icon">📦</div>
                    <h3>هنوز سفارشی ثبت نکرده‌اید</h3>
                    <p>از فروشگاه ما دیدن کنید و اولین سفارش خود را ثبت کنید.</p>
                    <a href="<?= BASE_URL ?>/index.php?page=shop" class="btn btn-primary mt-2">مشاهده محصولات</a>
                </div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>شماره سفارش</th>
                                <th>مبلغ</th>
                                <th>وضعیت</th>
                                <th>تاریخ</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $o): ?>
                                <tr>
                                    <td><strong><?= e(generateOrderNumber((int)$o['id'])) ?></strong></td>
                                    <td><?= formatPrice($o['total_amount']) ?></td>
                                    <td>
                                        <span class="status-badge" style="background:<?= e(statusColor($o['status'])) ?>;">
                                            <?= e(statusLabel($o['status'])) ?>
                                        </span>
                                    </td>
                                    <td><?= e(date('Y/m/d H:i', strtotime($o['created_at']))) ?></td>
                                    <td>
                                        <a href="<?= BASE_URL ?>/user/orders.php?id=<?= (int)$o['id'] ?>"
                                           class="btn btn-outline btn-sm">جزئیات</a>
                                        <?php if ($o['status'] === 'pending' && !$o['receipt_image']): ?>
                                            <a href="<?= BASE_URL ?>/user/upload_receipt.php?order=<?= (int)$o['id'] ?>"
                                               class="btn btn-primary btn-sm">آپلود فیش</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>