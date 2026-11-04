<?php
if (!defined('BASE_PATH')) { die('دسترسی مستقیم مجاز نیست.'); }

// ----- دسته‌بندی‌ها برای فیلتر -----
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

$categoryFilter = (int)($_GET['category'] ?? 0);
$sortFilter = $_GET['sort'] ?? 'newest';
$search = trim($_GET['search'] ?? '');

// ----- شرط‌ها -----
$where = ["p.is_active = 1"];
$params = [];

if ($categoryFilter > 0) {
    $where[] = "p.category_id = ?";
    $params[] = $categoryFilter;
}

if ($search !== '') {
    $where[] = "(p.title LIKE ? OR p.short_description LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

// ----- مرتب‌سازی -----
$orderBy = match ($sortFilter) {
    'cheap'     => 'p.price ASC',
    'expensive' => 'p.price DESC',
    'discount'  => '(p.price - COALESCE(p.discount_price, p.price)) DESC',
    default     => 'p.id DESC',
};

// ----- صفحه‌بندی -----
$perPage = 12;
$page = max(1, (int)($_GET['p'] ?? 1));
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
    ORDER BY $orderBy
    LIMIT $perPage OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

require_once BASE_PATH . '/includes/header.php';
?>

<section class="container">
    <div class="section">
        <div class="section-header">
            <h1 class="section-title">🛍️ فروشگاه</h1>
        </div>

        <?php renderFlashes(); ?>

        <!-- فیلترها -->
        <form method="get" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;margin-bottom:24px;background:#fff;padding:16px;border-radius:var(--radius);box-shadow:var(--shadow);">
            <input type="hidden" name="page" value="shop">

            <div class="form-group" style="flex:1;min-width:200px;margin:0;">
                <label style="font-size:0.85rem;font-weight:500;margin-bottom:6px;display:block;">جستجو</label>
                <input type="text" name="search" class="form-control"
                       value="<?= e($search) ?>" placeholder="جستجوی محصول...">
            </div>

            <div class="form-group" style="flex:0 0 auto;margin:0;">
                <label style="font-size:0.85rem;font-weight:500;margin-bottom:6px;display:block;">دسته</label>
                <select name="category" class="form-control">
                    <option value="0">همه دسته‌ها</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= $categoryFilter == $c['id'] ? 'selected' : '' ?>>
                            <?= e($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="flex:0 0 auto;margin:0;">
                <label style="font-size:0.85rem;font-weight:500;margin-bottom:6px;display:block;">مرتب‌سازی</label>
                <select name="sort" class="form-control">
                    <option value="newest" <?= $sortFilter === 'newest' ? 'selected' : '' ?>>جدیدترین</option>
                    <option value="cheap" <?= $sortFilter === 'cheap' ? 'selected' : '' ?>>ارزان‌ترین</option>
                    <option value="expensive" <?= $sortFilter === 'expensive' ? 'selected' : '' ?>>گران‌ترین</option>
                    <option value="discount" <?= $sortFilter === 'discount' ? 'selected' : '' ?>>بیشترین تخفیف</option>
                </select>
            </div>

            <div class="form-group" style="flex:0 0 auto;margin:0;">
                <button type="submit" class="btn btn-primary">اعمال فیلتر</button>
            </div>

            <?php if ($search !== '' || $categoryFilter > 0 || $sortFilter !== 'newest'): ?>
                <div class="form-group" style="flex:0 0 auto;margin:0;">
                    <a href="<?= BASE_URL ?>/index.php?page=shop" class="btn btn-outline">پاک کردن</a>
                </div>
            <?php endif; ?>
        </form>

        <!-- نتایج -->
        <?php if (empty($products)): ?>
            <div class="empty-state">
                <div class="icon">🔍</div>
                <h3>محصولی یافت نشد</h3>
                <p>فیلترها را تغییر دهید یا عبارت جستجو را پاک کنید.</p>
                <a href="<?= BASE_URL ?>/index.php?page=shop" class="btn btn-primary mt-2">مشاهده همه محصولات</a>
            </div>
        <?php else: ?>
            <div style="margin-bottom:12px;color:var(--text-muted);font-size:0.9rem;">
                <?= number_format($totalItems) ?> محصول یافت شد
            </div>

            <div class="products-grid">
                <?php foreach ($products as $p): ?>
                    <div class="product-card">
                        <a href="<?= BASE_URL ?>/index.php?page=product&id=<?= (int)$p['id'] ?>">
                            <div class="product-image">
                                <?php if (hasDiscount($p)): ?>
                                    <span class="discount-badge"><?= discountPercent($p) ?>% تخفیف</span>
                                <?php endif; ?>
                                <?php if ($p['is_new']): ?>
                                    <span class="new-badge">جدید</span>
                                <?php endif; ?>
                                <img src="<?= e($p['image'] ? imageUrl('products', $p['image']) : BASE_URL . '/assets/images/placeholder.png') ?>"
                                     alt="<?= e($p['title']) ?>" loading="lazy">
                            </div>
                        </a>
                        <div class="product-body">
                            <a href="<?= BASE_URL ?>/index.php?page=product&id=<?= (int)$p['id'] ?>">
                                <h3 class="product-title"><?= e($p['title']) ?></h3>
                            </a>
                            <?php if ($p['short_description']): ?>
                                <p class="product-desc"><?= e($p['short_description']) ?></p>
                            <?php endif; ?>
                            <div class="product-price">
                                <?php if (hasDiscount($p)): ?>
                                    <span class="price-current"><?= formatPrice(finalPrice($p)) ?></span>
                                    <span class="price-old"><?= formatPrice($p['price']) ?></span>
                                <?php else: ?>
                                    <span class="price-current"><?= formatPrice($p['price']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="product-actions">
                            <?php if ($p['stock'] > 0): ?>
                                <a href="<?= BASE_URL ?>/index.php?page=product&id=<?= (int)$p['id'] ?>"
                                   class="btn btn-outline btn-sm">مشاهده</a>
                                <form method="post" action="<?= BASE_URL ?>/user/cart.php" style="flex:1;">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="btn btn-primary btn-sm btn-block">افزودن</button>
                                </form>
                            <?php else: ?>
                                <span class="btn btn-sm btn-block" style="background:#eee;color:#999;cursor:not-allowed;">
                                    ناموجود
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- صفحه‌بندی -->
            <?php if ($totalPages > 1): ?>
                <div style="display:flex;gap:6px;justify-content:center;margin-top:30px;flex-wrap:wrap;">
                    <?php
                    $queryParams = $_GET;
                    for ($i = 1; $i <= $totalPages; $i++):
                        $queryParams['p'] = $i;
                        $link = '?' . http_build_query($queryParams);
                    ?>
                        <?php if ($i === $page): ?>
                            <span class="btn btn-primary" style="min-width:40px;"><?= $i ?></span>
                        <?php else: ?>
                            <a href="<?= e($link) ?>" class="btn btn-outline" style="min-width:40px;"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>