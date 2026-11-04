<?php
if (!defined('BASE_PATH')) { die('دسترسی مستقیم مجاز نیست.'); }

// ----- اسلایدها -----
$slides = $pdo->query("
    SELECT * FROM slides 
    WHERE is_active = 1 
    ORDER BY sort_order ASC, id DESC
")->fetchAll();

// ----- محصولات تخفیف‌خورده -----
$discountedProducts = $pdo->query("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.is_active = 1 
      AND p.discount_price IS NOT NULL 
      AND p.discount_price > 0 
      AND p.discount_price < p.price
    ORDER BY p.id DESC
    LIMIT 8
")->fetchAll();

// ----- محصولات جدید: ردیف اول -----
$newProductsRow1 = $pdo->query("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.is_active = 1 AND p.is_new = 1
    ORDER BY RAND()
    LIMIT 8
")->fetchAll();

// اگر کمتر از ۸ تا بود، از بقیه پر کن
if (count($newProductsRow1) < 8) {
    $existingIds = array_map('intval', array_column($newProductsRow1, 'id'));
    $excludeSql = !empty($existingIds) ? 'AND p.id NOT IN (' . implode(',', $existingIds) . ')' : '';
    $needed = 8 - count($newProductsRow1);

    $fill = $pdo->query("
        SELECT p.*, c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE p.is_active = 1 $excludeSql
        ORDER BY RAND()
        LIMIT $needed
    ")->fetchAll();

    $newProductsRow1 = array_merge($newProductsRow1, $fill);
}

// ----- محصولات جدید: ردیف دوم -----
$row1Ids = array_map('intval', array_column($newProductsRow1, 'id'));
$excludeRow1 = !empty($row1Ids) ? 'AND p.id NOT IN (' . implode(',', $row1Ids) . ')' : '';

$newProductsRow2 = $pdo->query("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.is_active = 1 $excludeRow1
    ORDER BY RAND()
    LIMIT 8
")->fetchAll();

/**
 * رندر کارت محصول
 */
function renderProductCard(array $p): void {
    $img = $p['image']
        ? imageUrl('products', $p['image'])
        : BASE_URL . '/assets/images/placeholder.png';
    ?>
    <div class="product-card">
        <a href="<?= BASE_URL ?>/index.php?page=product&id=<?= (int)$p['id'] ?>">
            <div class="product-image">
                <?php if (hasDiscount($p)): ?>
                    <span class="discount-badge"><?= discountPercent($p) ?>% تخفیف</span>
                <?php endif; ?>
                <?php if ($p['is_new']): ?>
                    <span class="new-badge">جدید</span>
                <?php endif; ?>
                <img src="<?= e($img) ?>" alt="<?= e($p['title']) ?>" loading="lazy">
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
    <?php
}

require_once BASE_PATH . '/includes/header.php';
?>

<!-- اسلایدر -->
<?php if (!empty($slides)): ?>
<section class="container">
    <div class="hero-slider">
        <div class="slider-track">
            <?php foreach ($slides as $s): ?>
                <div class="slide">
                    <?php if ($s['link']): ?>
                        <a href="<?= e($s['link']) ?>">
                            <img src="<?= e(imageUrl('slider', $s['image'])) ?>" alt="<?= e($s['title'] ?? '') ?>">
                        </a>
                    <?php else: ?>
                        <img src="<?= e(imageUrl('slider', $s['image'])) ?>" alt="<?= e($s['title'] ?? '') ?>">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (count($slides) > 1): ?>
            <button class="slider-btn slider-prev" aria-label="قبلی">‹</button>
            <button class="slider-btn slider-next" aria-label="بعدی">›</button>
            <div class="slider-dots"></div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<!-- محصولات تخفیف‌خورده -->
<?php if (!empty($discountedProducts)): ?>
<section class="container">
    <div class="section">
        <div class="section-header">
            <h2 class="section-title">🔥 محصولات تخفیف‌خورده</h2>
            <a href="<?= BASE_URL ?>/index.php?page=shop" class="btn btn-outline btn-sm">مشاهده همه</a>
        </div>
        <div class="products-grid">
            <?php foreach ($discountedProducts as $p) renderProductCard($p); ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- محصولات جدید — ردیف اول -->
<?php if (!empty($newProductsRow1)): ?>
<section class="container">
    <div class="section">
        <div class="section-header">
            <h2 class="section-title">✨ محصولات جدید</h2>
            <a href="<?= BASE_URL ?>/index.php?page=shop" class="btn btn-outline btn-sm">مشاهده همه</a>
        </div>
        <div class="products-grid">
            <?php foreach ($newProductsRow1 as $p) renderProductCard($p); ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- محصولات جدید — ردیف دوم -->
<?php if (!empty($newProductsRow2)): ?>
<section class="container">
    <div class="section">
        <div class="products-grid">
            <?php foreach ($newProductsRow2 as $p) renderProductCard($p); ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>