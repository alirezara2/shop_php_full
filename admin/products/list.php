<?php
require_once dirname(__DIR__) . '/includes/admin_auth.php';

// ----- پردازش حذف (قبل از هر خروجی HTML) -----
if (isset($_GET['delete']) && isset($_GET['token'])) {
    if (hash_equals(csrfToken(), $_GET['token'])) {
        $delId = (int)$_GET['delete'];
        try {
            $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
            $stmt->execute([$delId]);
            $img = $stmt->fetchColumn();

            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$delId]);

            if ($img) deleteImage('products', $img);
            setFlash('success', 'محصول با موفقیت حذف شد.');
        } catch (PDOException $e) {
            setFlash('error', 'خطا در حذف محصول. ممکن است سفارش مرتبط داشته باشد.');
        }
    } else {
        setFlash('error', 'توکن امنیتی نامعتبر است.');
    }
    redirect(BASE_URL . '/admin/products/list.php');
}

// ----- حالا خروجی -----
$pageTitle = 'مدیریت محصولات';
require_once dirname(__DIR__) . '/includes/admin_header.php';

// ----- فیلترها -----
$search = trim($_GET['search'] ?? '');
$categoryFilter = (int)($_GET['category'] ?? 0);
$statusFilter = $_GET['status'] ?? '';

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(p.title LIKE ? OR p.short_description LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
if ($categoryFilter > 0) {
    $where[] = "p.category_id = ?";
    $params[] = $categoryFilter;
}
if ($statusFilter === 'active') {
    $where[] = "p.is_active = 1";
} elseif ($statusFilter === 'inactive') {
    $where[] = "p.is_active = 0";
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ----- صفحه‌بندی -----
$perPage = 15;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p $whereSql");
$countStmt->execute($params);
$totalItems = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalItems / $perPage));

$sql = "
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    $whereSql
    ORDER BY p.id DESC
    LIMIT $perPage OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
?>

<h1 class="admin-page-title">مدیریت محصولات</h1>

<?php renderFlashes(); ?>

<div class="admin-card">
    <div class="admin-card-header">
        <h2>لیست محصولات (<?= number_format($totalItems) ?> مورد)</h2>
        <a href="<?= BASE_URL ?>/admin/products/create.php" class="btn-admin btn-admin-primary">
            ➕ افزودن محصول جدید
        </a>
    </div>

    <form method="get" class="filter-bar">
        <div class="form-group">
            <label>جستجو</label>
            <input type="text" name="search" class="form-control"
                   value="<?= e($search) ?>" placeholder="نام یا توضیح محصول...">
        </div>

        <div class="form-group">
            <label>دسته‌بندی</label>
            <select name="category" class="form-control">
                <option value="0">همه دسته‌ها</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= $categoryFilter == $c['id'] ? 'selected' : '' ?>>
                        <?= e($c['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>وضعیت</label>
            <select name="status" class="form-control">
                <option value="">همه</option>
                <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>فعال</option>
                <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>غیرفعال</option>
            </select>
        </div>

        <div class="form-group" style="flex:0 0 auto;">
            <button type="submit" class="btn-admin btn-admin-primary">اعمال فیلتر</button>
            <a href="<?= BASE_URL ?>/admin/products/list.php" class="btn-admin btn-admin-secondary">پاک کردن</a>
        </div>
    </form>

    <?php if (empty($products)): ?>
        <div class="admin-empty">
            <div class="icon">📦</div>
            <h3>محصولی یافت نشد</h3>
            <p>می‌توانید با دکمه بالا محصول جدید اضافه کنید.</p>
        </div>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>عکس</th>
                        <th>نام</th>
                        <th>دسته</th>
                        <th>قیمت</th>
                        <th>تخفیف</th>
                        <th>موجودی</th>
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td><?= (int)$p['id'] ?></td>
                            <td>
                                <?php if ($p['image']): ?>
                                    <img class="thumb" src="<?= e(imageUrl('products', $p['image'])) ?>" alt="">
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= e($p['title']) ?>
                                <?php if ($p['is_new']): ?>
                                    <span class="badge-soft badge-soft-info">جدید</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($p['category_name'] ?? '—') ?></td>
                            <td><?= formatPrice($p['price']) ?></td>
                            <td>
                                <?php if ($p['discount_price']): ?>
                                    <?= formatPrice($p['discount_price']) ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($p['stock'] > 0): ?>
                                    <?= (int)$p['stock'] ?>
                                <?php else: ?>
                                    <span class="badge-soft badge-soft-danger">ناموجود</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($p['is_active']): ?>
                                    <span class="badge-soft badge-soft-success">فعال</span>
                                <?php else: ?>
                                    <span class="badge-soft badge-soft-danger">غیرفعال</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="<?= BASE_URL ?>/admin/products/edit.php?id=<?= (int)$p['id'] ?>"
                                       class="btn-admin btn-admin-primary btn-admin-sm">ویرایش</a>
                                    <a href="<?= BASE_URL ?>/admin/products/list.php?delete=<?= (int)$p['id'] ?>&token=<?= e(csrfToken()) ?>"
                                       class="btn-admin btn-admin-danger btn-admin-sm"
                                       data-confirm="محصول «<?= e($p['title']) ?>» حذف شود؟">حذف</a>
                                </div>
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