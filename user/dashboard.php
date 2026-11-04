<?php
require_once dirname(__DIR__) . '/config/config.php';
requireLogin();

$user = currentUser();

// ----- آمار کاربر -----
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
$stmt->execute([$user['id']]);
$totalOrders = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status IN ('pending','paid','preparing','shipping')");
$stmt->execute([$user['id']]);
$activeOrders = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE user_id = ? AND status IN ('paid','preparing','shipping')");
$stmt->execute([$user['id']]);
$totalSpent = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 5");
$stmt->execute([$user['id']]);
$recentOrders = $stmt->fetchAll();

$pageTitle = 'داشبورد';
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
                <li><a href="<?= BASE_URL ?>/user/dashboard.php" class="active">🏠 داشبورد</a></li>
                <li><a href="<?= BASE_URL ?>/user/orders.php">📦 سفارش‌های من</a></li>
                <li><a href="<?= BASE_URL ?>/user/cart.php">🛒 سبد خرید</a></li>
                <li><a href="<?= BASE_URL ?>/user/profile.php">👤 اطلاعات من</a></li>
                <li><a href="<?= BASE_URL ?>/user/logout.php" data-confirm="از حساب خود خارج می‌شوید؟">🚪 خروج</a></li>
            </ul>
        </aside>

        <!-- محتوا -->
        <div class="dashboard-content">
            <h1 class="page-title">داشبورد</h1>

            <?php renderFlashes(); ?>

            <?php if (!$user['is_profile_complete']): ?>
                <div class="flash flash-warning">
                    ⚠️ لطفاً برای امکان خرید، ابتدا اطلاعات پروفایل خود را تکمیل کنید.
                    <a href="<?= BASE_URL ?>/user/profile.php" style="font-weight:600;">تکمیل پروفایل</a>
                </div>
            <?php endif; ?>

            <!-- کارت‌های آماری -->
            <div class="stats-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:24px;">
                <div style="background:var(--bg);padding:20px;border-radius:var(--radius-sm);text-align:center;">
                    <div style="font-size:1.8rem;font-weight:700;color:var(--primary);"><?= number_format($totalOrders) ?></div>
                    <div style="color:var(--text-muted);font-size:0.9rem;">کل سفارش‌ها</div>
                </div>
                <div style="background:var(--bg);padding:20px;border-radius:var(--radius-sm);text-align:center;">
                    <div style="font-size:1.8rem;font-weight:700;color:var(--accent);"><?= number_format($activeOrders) ?></div>
                    <div style="color:var(--text-muted);font-size:0.9rem;">سفارش‌های فعال</div>
                </div>
                <div style="background:var(--bg);padding:20px;border-radius:var(--radius-sm);text-align:center;">
                    <div style="font-size:1.4rem;font-weight:700;color:var(--success);"><?= formatPrice($totalSpent) ?></div>
                    <div style="color:var(--text-muted);font-size:0.9rem;">مجموع خرید</div>
                </div>
            </div>

            <!-- آخرین سفارش‌ها -->
            <h2 style="font-size:1.2rem;margin-bottom:16px;">آخرین سفارش‌ها</h2>

            <?php if (empty($recentOrders)): ?>
                <div class="empty-state">
                    <div class="icon">📦</div>
                    <h3>هنوز سفارشی ثبت نکرده‌اید</h3>
                    <a href="<?= BASE_URL ?>/index.php?page=shop" class="btn btn-primary mt-2">مشاهده محصولات</a>
                </div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>شماره</th>
                                <th>مبلغ</th>
                                <th>وضعیت</th>
                                <th>تاریخ</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $o): ?>
                                <tr>
                                    <td><?= e(generateOrderNumber((int)$o['id'])) ?></td>
                                    <td><?= formatPrice($o['total_amount']) ?></td>
                                    <td>
                                        <span class="status-badge" style="background:<?= e(statusColor($o['status'])) ?>;">
                                            <?= e(statusLabel($o['status'])) ?>
                                        </span>
                                    </td>
                                    <td><?= e(date('Y/m/d', strtotime($o['created_at']))) ?></td>
                                    <td>
                                        <a href="<?= BASE_URL ?>/user/orders.php?id=<?= (int)$o['id'] ?>"
                                           class="btn btn-outline btn-sm">جزئیات</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <a href="<?= BASE_URL ?>/user/orders.php" class="btn btn-outline">مشاهده همه سفارش‌ها</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>