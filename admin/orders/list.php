<?php
require_once dirname(__DIR__) . '/includes/admin_auth.php';

$pageTitle = 'مدیریت سفارش‌ها';
require_once dirname(__DIR__) . '/includes/admin_header.php';

// ----- فیلترها -----
$statusFilter = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

$where = [];
$params = [];

if ($statusFilter !== '' && in_array($statusFilter, [ORDER_PENDING, ORDER_PAID, ORDER_PREPARING, ORDER_SHIPPING, ORDER_CANCELED], true)) {
    $where[] = "o.status = ?";
    $params[] = $statusFilter;
}

if ($search !== '') {
    $where[] = "(u.phone LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR o.id = ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = (int)$search;
}

if ($dateFrom !== '') {
    $where[] = "DATE(o.created_at) >= ?";
    $params[] = $dateFrom;
}

if ($dateTo !== '') {
    $where[] = "DATE(o.created_at) <= ?";
    $params[] = $dateTo;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ----- صفحه‌بندی -----
$perPage = 20;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$countStmt = $pdo->prepare("
    SELECT COUNT(*) FROM orders o
    JOIN users u ON u.id = o.user_id
    $whereSql
");
$countStmt->execute($params);
$totalItems = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalItems / $perPage));

// ----- گرفتن سفارش‌ها -----
$sql = "
    SELECT o.*, u.phone AS user_phone, u.first_name, u.last_name
    FROM orders o
    JOIN users u ON u.id = o.user_id
    $whereSql
    ORDER BY o.id DESC
    LIMIT $perPage OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>

<h1 class="admin-page-title">مدیریت سفارش‌ها</h1>

<?php renderFlashes(); ?>

<div class="admin-card">
    <div class="admin-card-header">
        <h2>لیست سفارش‌ها (<?= number_format($totalItems) ?> مورد)</h2>
        <a href="<?= BASE_URL ?>/admin/orders/export_excel.php?<?= e(http_build_query($_GET)) ?>"
           class="btn-admin btn-admin-success">
            📊 خروجی اکسل
        </a>
    </div>

    <!-- فیلترها -->
    <form method="get" class="filter-bar">
        <div class="form-group">
            <label>جستجو</label>
            <input type="text" name="search" class="form-control"
                   value="<?= e($search) ?>" placeholder="شماره یا نام کاربر...">
        </div>

        <div class="form-group">
            <label>وضعیت</label>
            <select name="status" class="form-control">
                <option value="">همه</option>
                <?php foreach ($GLOBALS['ORDER_STATUS_LABELS'] as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= $statusFilter === $key ? 'selected' : '' ?>>
                        <?= e($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>از تاریخ</label>
            <input type="date" name="date_from" class="form-control" value="<?= e($dateFrom) ?>">
        </div>

        <div class="form-group">
            <label>تا تاریخ</label>
            <input type="date" name="date_to" class="form-control" value="<?= e($dateTo) ?>">
        </div>

        <div class="form-group" style="flex:0 0 auto;">
            <button type="submit" class="btn-admin btn-admin-primary">اعمال</button>
            <a href="<?= BASE_URL ?>/admin/orders/list.php" class="btn-admin btn-admin-secondary">پاک کردن</a>
        </div>
    </form>

    <?php if (empty($orders)): ?>
        <div class="admin-empty">
            <div class="icon">🛒</div>
            <h3>سفارشی یافت نشد</h3>
        </div>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>شماره سفارش</th>
                        <th>کاربر</th>
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
                            <td>
                                <?= e(trim(($o['first_name'] ?? '') . ' ' . ($o['last_name'] ?? '')) ?: '—') ?>
                                <div class="text-muted" style="font-size:0.8rem;"><?= e($o['user_phone']) ?></div>
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

        <!-- صفحه‌بندی -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php
                $queryParams = $_GET;
                for ($i = 1; $i <= $totalPages; $i++):
                    $queryParams['page'] = $i;
                    $link = '?' . http_build_query($queryParams);
                ?>
                    <?php if ($i === $page): ?>
                        <span class="active"><?= $i ?></span>
                    <?php else: ?>
                        <a href="<?= e($link) ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>