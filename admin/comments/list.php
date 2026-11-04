<?php
require_once dirname(__DIR__) . '/includes/admin_auth.php';

$pageTitle = 'مدیریت نظرات';
require_once dirname(__DIR__) . '/includes/admin_header.php';

// ----- فیلتر -----
$statusFilter = $_GET['status'] ?? 'pending'; // pending | approved | all

$where = [];
$params = [];

if ($statusFilter === 'pending') {
    $where[] = "c.is_approved = 0";
} elseif ($statusFilter === 'approved') {
    $where[] = "c.is_approved = 1";
}
// all → هیچ شرطی

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ----- صفحه‌بندی -----
$perPage = 20;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM comments c $whereSql");
$countStmt->execute($params);
$totalItems = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalItems / $perPage));

$sql = "
    SELECT c.*, p.title AS product_title
    FROM comments c
    JOIN products p ON p.id = c.product_id
    $whereSql
    ORDER BY c.is_approved ASC, c.id DESC
    LIMIT $perPage OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$comments = $stmt->fetchAll();
?>

<h1 class="admin-page-title">مدیریت نظرات</h1>

<?php renderFlashes(); ?>

<div class="admin-card">
    <div class="admin-card-header">
        <h2>لیست نظرات (<?= number_format($totalItems) ?> مورد)</h2>
    </div>

    <!-- فیلتر -->
    <form method="get" class="filter-bar">
        <div class="form-group">
            <label>وضعیت</label>
            <select name="status" class="form-control">
                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>در انتظار تایید</option>
                <option value="approved" <?= $statusFilter === 'approved' ? 'selected' : '' ?>>تایید شده</option>
                <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>همه</option>
            </select>
        </div>
        <div class="form-group" style="flex:0 0 auto;">
            <button type="submit" class="btn-admin btn-admin-primary">فیلتر</button>
        </div>
    </form>

    <?php if (empty($comments)): ?>
        <div class="admin-empty">
            <div class="icon">💬</div>
            <h3>نظری یافت نشد</h3>
        </div>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>محصول</th>
                        <th>نویسنده</th>
                        <th>متن</th>
                        <th>وضعیت</th>
                        <th>تاریخ</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($comments as $c): ?>
                        <tr>
                            <td><?= (int)$c['id'] ?></td>
                            <td><?= e($c['product_title']) ?></td>
                            <td>
                                <?= e($c['name']) ?>
                                <?php if (!empty($c['email'])): ?>
                                    <div class="text-muted" style="font-size:0.8rem;"><?= e($c['email']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="max-width:320px;">
                                <?= nl2br(e(mb_substr($c['body'], 0, 200))) ?><?= mb_strlen($c['body']) > 200 ? '…' : '' ?>
                            </td>
                            <td>
                                <?php if ($c['is_approved']): ?>
                                    <span class="badge-soft badge-soft-success">تایید شده</span>
                                <?php else: ?>
                                    <span class="badge-soft badge-soft-warning">در انتظار</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e(date('Y/m/d H:i', strtotime($c['created_at']))) ?></td>
                            <td>
                                <div class="btn-group">
                                    <?php if (!$c['is_approved']): ?>
                                        <form method="post" action="<?= BASE_URL ?>/admin/comments/approve.php" style="display:inline;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                            <button type="submit" class="btn-admin btn-admin-success btn-admin-sm"
                                                    data-confirm="این نظر تایید شود؟">
                                                ✅ تایید
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <form method="post" action="<?= BASE_URL ?>/admin/comments/delete.php" style="display:inline;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                        <button type="submit" class="btn-admin btn-admin-danger btn-admin-sm"
                                                data-confirm="این نظر حذف شود؟">
                                            🗑️ حذف
                                        </button>
                                    </form>
                                </div>
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