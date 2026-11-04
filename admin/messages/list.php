<?php
require_once dirname(__DIR__) . '/includes/admin_auth.php';

$pageTitle = 'پیام‌های تماس با ما';
require_once dirname(__DIR__) . '/includes/admin_header.php';

// ----- فیلتر -----
$statusFilter = $_GET['status'] ?? 'all'; // unread | read | all

$where = [];
$params = [];

if ($statusFilter === 'unread') {
    $where[] = "is_read = 0";
} elseif ($statusFilter === 'read') {
    $where[] = "is_read = 1";
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ----- صفحه‌بندی -----
$perPage = 20;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM contact_messages $whereSql");
$countStmt->execute($params);
$totalItems = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalItems / $perPage));

$sql = "SELECT * FROM contact_messages $whereSql ORDER BY is_read ASC, id DESC LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$messages = $stmt->fetchAll();

// شمارش خوانده‌نشده‌ها برای بج
$unreadCount = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0")->fetchColumn();
?>

<h1 class="admin-page-title">پیام‌های تماس با ما</h1>

<?php renderFlashes(); ?>

<div class="admin-card">
    <div class="admin-card-header">
        <h2>
            لیست پیام‌ها (<?= number_format($totalItems) ?> مورد)
            <?php if ($unreadCount > 0): ?>
                <span class="badge-soft badge-soft-danger"><?= (int)$unreadCount ?> خوانده‌نشده</span>
            <?php endif; ?>
        </h2>
    </div>

    <form method="get" class="filter-bar">
        <div class="form-group">
            <label>وضعیت</label>
            <select name="status" class="form-control">
                <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>همه</option>
                <option value="unread" <?= $statusFilter === 'unread' ? 'selected' : '' ?>>خوانده‌نشده</option>
                <option value="read" <?= $statusFilter === 'read' ? 'selected' : '' ?>>خوانده‌شده</option>
            </select>
        </div>
        <div class="form-group" style="flex:0 0 auto;">
            <button type="submit" class="btn-admin btn-admin-primary">فیلتر</button>
        </div>
    </form>

    <?php if (empty($messages)): ?>
        <div class="admin-empty">
            <div class="icon">✉️</div>
            <h3>پیامی یافت نشد</h3>
        </div>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>فرستنده</th>
                        <th>موضوع</th>
                        <th>وضعیت</th>
                        <th>تاریخ</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages as $m): ?>
                        <tr style="<?= !$m['is_read'] ? 'background:#fef9e7;font-weight:500;' : '' ?>">
                            <td><?= (int)$m['id'] ?></td>
                            <td><?= e($m['email']) ?></td>
                            <td><?= e(mb_substr($m['subject'], 0, 60)) ?><?= mb_strlen($m['subject']) > 60 ? '…' : '' ?></td>
                            <td>
                                <?php if ($m['is_read']): ?>
                                    <span class="badge-soft badge-soft-success">خوانده شده</span>
                                <?php else: ?>
                                    <span class="badge-soft badge-soft-danger">جدید</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e(date('Y/m/d H:i', strtotime($m['created_at']))) ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>/admin/messages/view.php?id=<?= (int)$m['id'] ?>"
                                   class="btn-admin btn-admin-primary btn-admin-sm">
                                    مشاهده
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

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