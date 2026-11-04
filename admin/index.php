<?php
require_once __DIR__ . '/includes/admin_auth.php';

$pageTitle = 'داشبورد';
require_once __DIR__ . '/includes/admin_header.php';

// ----- آمار کلی -----
try {
    // تعداد کل محصولات
    $totalProducts = (int) $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

    // تعداد کل کاربران
    $totalUsers = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

    // تعداد کل سفارش‌ها
    $totalOrders = (int) $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();

    // سفارش‌های در انتظار تایید فیش
    $pendingOrders = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();

    // سفارش‌های در انتظار ارسال (پرداخت شده یا در حال آماده‌سازی)
    $toShipOrders = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('paid','preparing')")->fetchColumn();

    // نظرات در انتظار تایید
    $pendingComments = (int) $pdo->query("SELECT COUNT(*) FROM comments WHERE is_approved = 0")->fetchColumn();

    // پیام‌های خوانده‌نشده
    $unreadMessages = (int) $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0")->fetchColumn();

    // مجموع درآمد سفارش‌های موفق
    $totalRevenue = (int) $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status IN ('paid','preparing','shipping')")->fetchColumn();

    // آخرین سفارش‌ها (۵ مورد)
    $recentOrders = $pdo->query("
        SELECT o.*, u.phone AS user_phone, u.first_name, u.last_name
        FROM orders o
        JOIN users u ON u.id = o.user_id
        ORDER BY o.id DESC
        LIMIT 5
    ")->fetchAll();

    // آخرین محصولات (۵ مورد)
    $recentProducts = $pdo->query("
        SELECT * FROM products ORDER BY id DESC LIMIT 5
    ")->fetchAll();

} catch (PDOException $e) {
    echo '<div class="flash flash-error">خطا در دریافت آمار.</div>';
    $totalProducts = $totalUsers = $totalOrders = $pendingOrders = 0;
    $toShipOrders = $pendingComments = $unreadMessages = $totalRevenue = 0;
    $recentOrders = $recentProducts = [];
}
?>

<h1 class="admin-page-title">داشبورد</h1>

<!-- کارت‌های آماری -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">📦</div>
        <div class="stat-info">
            <div class="value"><?= number_format($totalProducts) ?></div>
            <div class="label">کل محصولات</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon green">🛒</div>
        <div class="stat-info">
            <div class="value"><?= number_format($totalOrders) ?></div>
            <div class="label">کل سفارش‌ها</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon orange">⏳</div>
        <div class="stat-info">
            <div class="value"><?= number_format($pendingOrders) ?></div>
            <div class="label">در انتظار تایید فیش</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon red">🚚</div>
        <div class="stat-info">
            <div class="value"><?= number_format($toShipOrders) ?></div>
            <div class="label">در انتظار ارسال</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon blue">👥</div>
        <div class="stat-info">
            <div class="value"><?= number_format($totalUsers) ?></div>
            <div class="label">کل کاربران</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon green">💰</div>
        <div class="stat-info">
            <div class="value"><?= number_format($totalRevenue) ?></div>
            <div class="label">درآمد (تومان)</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon orange">💬</div>
        <div class="stat-info">
            <div class="value"><?= number_format($pendingComments) ?></div>
            <div class="label">نظرات در انتظار</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon red">✉️</div>
        <div class="stat-info">
            <div class="value"><?= number_format($unreadMessages) ?></div>
            <div class="label">پیام خوانده‌نشده</div>
        </div>
    </div>
</div>

<!-- آخرین سفارش‌ها -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2>آخرین سفارش‌ها</h2>
        <a href="<?= BASE_URL ?>/admin/orders/list.php" class="btn-admin btn-admin-secondary btn-admin-sm">
            مشاهده همه
        </a>
    </div>

    <?php if (empty($recentOrders)): ?>
        <div class="admin-empty">
            <div class="icon">📭</div>
            <p>هنوز سفارشی ثبت نشده است.</p>
        </div>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>شماره</th>
                        <th>کاربر</th>
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
                            <td>
                                <?= e(trim(($o['first_name'] ?? '') . ' ' . ($o['last_name'] ?? '')) ?: $o['user_phone']) ?>
                            </td>
                            <td><?= formatPrice($o['total_amount']) ?></td>
                            <td>
                                <span class="badge-status badge-<?= e($o['status']) ?>">
                                    <?= e(statusLabel($o['status'])) ?>
                                </span>
                            </td>
                            <td><?= e(date('Y/m/d H:i', strtotime($o['created_at']))) ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>/admin/orders/view.php?id=<?= (int)$o['id'] ?>"
                                   class="btn-admin btn-admin-primary btn-admin-sm">
                                    مشاهده
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- آخرین محصولات -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2>آخرین محصولات</h2>
        <a href="<?= BASE_URL ?>/admin/products/list.php" class="btn-admin btn-admin-secondary btn-admin-sm">
            مشاهده همه
        </a>
    </div>

    <?php if (empty($recentProducts)): ?>
        <div class="admin-empty">
            <div class="icon">📦</div>
            <p>هنوز محصولی ثبت نشده است.</p>
        </div>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>عکس</th>
                        <th>نام</th>
                        <th>قیمت</th>
                        <th>موجودی</th>
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentProducts as $p): ?>
                        <tr>
                            <td>
                                <?php if ($p['image']): ?>
                                    <img class="thumb"
                                         src="<?= e(imageUrl('products', $p['image'])) ?>"
                                         alt="<?= e($p['title']) ?>">
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($p['title']) ?></td>
                            <td><?= formatPrice(finalPrice($p)) ?></td>
                            <td><?= (int)$p['stock'] ?></td>
                            <td>
                                <?php if ($p['is_active']): ?>
                                    <span class="badge-soft badge-soft-success">فعال</span>
                                <?php else: ?>
                                    <span class="badge-soft badge-soft-danger">غیرفعال</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= BASE_URL ?>/admin/products/edit.php?id=<?= (int)$p['id'] ?>"
                                   class="btn-admin btn-admin-primary btn-admin-sm">
                                    ویرایش
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>