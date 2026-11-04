<?php
if (!defined('BASE_PATH')) { die('دسترسی مستقیم مجاز نیست.'); }

$productId = (int)($_GET['id'] ?? 0);

// ----- گرفتن محصول -----
$stmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.id = ? AND p.is_active = 1
    LIMIT 1
");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    require_once BASE_PATH . '/includes/header.php';
    echo '<div class="container"><div class="empty-state"><h3>محصول یافت نشد</h3><p><a href="' . BASE_URL . '/index.php?page=shop">بازگشت به فروشگاه</a></p></div></div>';
    require_once BASE_PATH . '/includes/footer.php';
    exit;
}

// ----- ثبت نظر -----
$commentErrors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_comment') {
    requireCsrf();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $body = trim($_POST['body'] ?? '');

    if ($name === '') $commentErrors[] = 'نام الزامی است.';
    if ($body === '') $commentErrors[] = 'متن نظر الزامی است.';
    if (mb_strlen($body) > 1000) $commentErrors[] = 'متن نظر طولانی است (حداکثر ۱۰۰۰ کاراکتر).';
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $commentErrors[] = 'ایمیل معتبر نیست.';

    if (empty($commentErrors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO comments (product_id, user_id, name, email, body, is_approved)
                VALUES (?, ?, ?, ?, ?, 0)
            ");
            $stmt->execute([
                $productId,
                isLoggedIn() ? $_SESSION['user_id'] : null,
                $name,
                $email ?: null,
                $body,
            ]);
            setFlash('success', 'نظر شما ثبت شد و پس از تایید نمایش داده می‌شود.');
            redirect(BASE_URL . '/index.php?page=product&id=' . $productId);
        } catch (PDOException $e) {
            $commentErrors[] = 'خطا در ثبت نظر. لطفاً بعداً تلاش کنید.';
        }
    }
}

// ----- نظرات تایید شده -----
$stmt = $pdo->prepare("
    SELECT * FROM comments 
    WHERE product_id = ? AND is_approved = 1 
    ORDER BY id DESC
");
$stmt->execute([$productId]);
$comments = $stmt->fetchAll();

// ----- محصولات دیگر (هم‌دسته یا آخرین محصولات) -----
if ($product['category_id']) {
    $stmt = $pdo->prepare("
        SELECT * FROM products 
        WHERE is_active = 1 AND id != ? AND category_id = ?
        ORDER BY RAND() LIMIT 4
    ");
    $stmt->execute([$productId, $product['category_id']]);
    $related = $stmt->fetchAll();
} else {
    $related = [];
}

if (empty($related)) {
    $stmt = $pdo->prepare("
        SELECT * FROM products 
        WHERE is_active = 1 AND id != ?
        ORDER BY id DESC LIMIT 4
    ");
    $stmt->execute([$productId]);
    $related = $stmt->fetchAll();
}

$pageTitle = $product['title'];
require_once BASE_PATH . '/includes/header.php';
?>

<section class="container">
    <!-- Breadcrumb -->
    <div style="margin:16px 0;font-size:0.9rem;color:var(--text-muted);">
        <a href="<?= BASE_URL ?>">خانه</a> › 
        <a href="<?= BASE_URL ?>/index.php?page=shop">فروشگاه</a>
        <?php if ($product['category_name']): ?>
            › <a href="<?= BASE_URL ?>/index.php?page=shop&category=<?= (int)$product['category_id'] ?>">
                <?= e($product['category_name']) ?>
            </a>
        <?php endif; ?>
        › <span><?= e($product['title']) ?></span>
    </div>

    <!-- جزئیات محصول -->
    <div class="product-detail">
        <div class="product-gallery">
            <img src="<?= e($product['image'] ? imageUrl('products', $product['image']) : BASE_URL . '/assets/images/placeholder.png') ?>"
                 alt="<?= e($product['title']) ?>">
        </div>

        <div class="product-info">
            <h1><?= e($product['title']) ?></h1>

            <?php if ($product['short_description']): ?>
                <p class="short-desc"><?= e($product['short_description']) ?></p>
            <?php endif; ?>

            <div class="price-block">
                <?php if (hasDiscount($product)): ?>
                    <span class="price-current"><?= formatPrice(finalPrice($product)) ?></span>
                    <span class="price-old"><?= formatPrice($product['price']) ?></span>
                    <span class="discount-badge" style="position:static;"><?= discountPercent($product) ?>% تخفیف</span>
                <?php else: ?>
                    <span class="price-current"><?= formatPrice($product['price']) ?></span>
                <?php endif; ?>
            </div>

            <?php if ($product['stock'] > 0): ?>
                <span class="stock-status in-stock">✅ موجود در انبار (<?= (int)$product['stock'] ?> عدد)</span>
            <?php else: ?>
                <span class="stock-status out-stock">❌ ناموجود</span>
            <?php endif; ?>

            <?php if ($product['stock'] > 0): ?>
                <form method="post" action="<?= BASE_URL ?>/user/cart.php">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">

                    <div class="quantity-selector">
                        <button type="button" class="qty-minus">−</button>
                        <input type="number" name="quantity" value="1" min="1" max="<?= (int)$product['stock'] ?>">
                        <button type="button" class="qty-plus">+</button>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" style="max-width:300px;">
                        🛒 افزودن به سبد خرید
                    </button>
                </form>
            <?php else: ?>
                <button class="btn btn-block" disabled style="background:#eee;color:#999;">
                    محصول فعلاً ناموجود است
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- تب‌ها: توضیحات و نظرات -->
    <div class="tabs">
        <div class="tabs-nav">
            <button class="active">توضیحات کامل</button>
            <button>نظرات (<?= count($comments) ?>)</button>
        </div>

        <div class="tab-content active">
            <div class="description-content">
                <?= $product['description'] ? nl2br(e($product['description'])) : '<p class="text-muted">توضیحی ثبت نشده است.</p>' ?>
            </div>
        </div>

        <div class="tab-content">
            <!-- لیست نظرات -->
            <?php if (empty($comments)): ?>
                <div class="empty-state">
                    <div class="icon">💬</div>
                    <h3>هنوز نظری ثبت نشده</h3>
                    <p>اولین نفری باشید که نظر می‌دهد!</p>
                </div>
            <?php else: ?>
                <div class="comments-list">
                    <?php foreach ($comments as $c): ?>
                        <div class="comment-item">
                            <div class="comment-header">
                                <span class="comment-author"><?= e($c['name']) ?></span>
                                <span class="comment-date"><?= e(date('Y/m/d', strtotime($c['created_at']))) ?></span>
                            </div>
                            <div class="comment-body"><?= nl2br(e($c['body'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- فرم ثبت نظر -->
            <div class="comment-form">
                <h3>ثبت نظر جدید</h3>

                <?php if ($commentErrors): ?>
                    <div class="flash flash-error">
                        <?php foreach ($commentErrors as $err): ?>
                            <div>• <?= e($err) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="add_comment">

                    <div class="form-row">
                        <div class="form-group">
                            <label>نام <span class="required">*</span></label>
                            <input type="text" name="name" class="form-control" 
                                   value="<?= isLoggedIn() ? e(trim((currentUser()['first_name'] ?? '') . ' ' . (currentUser()['last_name'] ?? ''))) : e($_POST['name'] ?? '') ?>"
                                   required>
                        </div>
                        <div class="form-group">
                            <label>ایمیل (اختیاری)</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?= e($_POST['email'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>متن نظر <span class="required">*</span></label>
                        <textarea name="body" class="form-control" rows="4" maxlength="1000" required><?= e($_POST['body'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">ثبت نظر</button>
                    <p class="form-hint mt-1">نظر شما پس از تایید ادمین نمایش داده می‌شود.</p>
                </form>
            </div>
        </div>
    </div>

    <!-- محصولات دیگر -->
    <?php if (!empty($related)): ?>
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">محصولات دیگر</h2>
            </div>

            <form method="post" action="<?= BASE_URL ?>/user/cart.php">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="add_multi">

                <div class="products-grid" style="margin-bottom:16px;">
                    <?php foreach ($related as $r): ?>
                        <div class="product-card" style="position:relative;">
                            <?php if ($r['stock'] > 0): ?>
                                <label style="position:absolute;top:8px;left:8px;background:#fff;padding:4px 8px;border-radius:6px;z-index:5;cursor:pointer;display:flex;align-items:center;gap:4px;font-size:0.85rem;">
                                    <input type="checkbox" name="product_ids[]" value="<?= (int)$r['id'] ?>">
                                    انتخاب
                                </label>
                            <?php endif; ?>

                            <a href="<?= BASE_URL ?>/index.php?page=product&id=<?= (int)$r['id'] ?>">
                                <div class="product-image">
                                    <img src="<?= e($r['image'] ? imageUrl('products', $r['image']) : BASE_URL . '/assets/images/placeholder.png') ?>"
                                         alt="<?= e($r['title']) ?>">
                                </div>
                            </a>

                            <div class="product-body">
                                <a href="<?= BASE_URL ?>/index.php?page=product&id=<?= (int)$r['id'] ?>">
                                    <h3 class="product-title"><?= e($r['title']) ?></h3>
                                </a>
                                <div class="product-price">
                                    <span class="price-current"><?= formatPrice(finalPrice($r)) ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button type="submit" class="btn btn-primary">➕ افزودن موارد انتخاب‌شده به سبد</button>
            </form>
        </div>
    <?php endif; ?>
</section>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>