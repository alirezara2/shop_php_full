<?php
require_once dirname(__DIR__) . '/config/config.php';

// ----- پردازش درخواست AJAX -----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    requireCsrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));

        $stmt = $pdo->prepare("SELECT id, stock, is_active FROM products WHERE id = ? LIMIT 1");
        $stmt->execute([$productId]);
        $p = $stmt->fetch();

        if (!$p || !$p['is_active']) {
            jsonResponse(['ok' => false, 'msg' => 'محصول یافت نشد.'], 404);
        }
        if ($p['stock'] < $quantity) {
            jsonResponse(['ok' => false, 'msg' => 'موجودی کافی نیست.']);
        }

        cartAdd($productId, $quantity);
        jsonResponse(['ok' => true, 'count' => cartCount()]);
    }

    if ($action === 'update') {
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 0);
        cartUpdate($productId, $quantity);
        jsonResponse(['ok' => true, 'count' => cartCount()]);
    }

    if ($action === 'add_multi') {
        $idsJson = $_POST['product_ids'] ?? '[]';
        $ids = json_decode($idsJson, true);
        if (!is_array($ids) || empty($ids)) {
            jsonResponse(['ok' => false, 'msg' => 'محصولی انتخاب نشده است.']);
        }

        $added = 0;
        foreach ($ids as $pid) {
            $pid = (int)$pid;
            if ($pid <= 0) continue;

            $stmt = $pdo->prepare("SELECT stock, is_active FROM products WHERE id = ? LIMIT 1");
            $stmt->execute([$pid]);
            $p = $stmt->fetch();
            if ($p && $p['is_active'] && $p['stock'] > 0) {
                cartAdd($pid, 1);
                $added++;
            }
        }
        jsonResponse(['ok' => true, 'added' => $added, 'count' => cartCount()]);
    }

    jsonResponse(['ok' => false, 'msg' => 'عملیات نامعتبر.'], 400);
}

// ----- پردازش POST عادی (فرم‌های صفحه) -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));

        $stmt = $pdo->prepare("SELECT stock, is_active FROM products WHERE id = ? LIMIT 1");
        $stmt->execute([$productId]);
        $p = $stmt->fetch();

        if (!$p || !$p['is_active']) {
            setFlash('error', 'محصول یافت نشد.');
        } elseif ($p['stock'] < $quantity) {
            setFlash('error', 'موجودی کافی نیست.');
        } else {
            cartAdd($productId, $quantity);
            setFlash('success', 'محصول به سبد خرید اضافه شد.');
        }
        redirect($_SERVER['HTTP_REFERER'] ?? BASE_URL . '/user/cart.php');
    }

    if ($action === 'add_multi') {
        $ids = $_POST['product_ids'] ?? [];
        if (is_array($ids) && !empty($ids)) {
            $added = 0;
            foreach ($ids as $pid) {
                $pid = (int)$pid;
                if ($pid <= 0) continue;
                $stmt = $pdo->prepare("SELECT stock, is_active FROM products WHERE id = ? LIMIT 1");
                $stmt->execute([$pid]);
                $p = $stmt->fetch();
                if ($p && $p['is_active'] && $p['stock'] > 0) {
                    cartAdd($pid, 1);
                    $added++;
                }
            }
            setFlash('success', $added . ' محصول به سبد خرید اضافه شد.');
        } else {
            setFlash('error', 'محصولی انتخاب نشده بود.');
        }
        redirect($_SERVER['HTTP_REFERER'] ?? BASE_URL . '/user/cart.php');
    }

    if ($action === 'update') {
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 0);
        cartUpdate($productId, $quantity);
        redirect(BASE_URL . '/user/cart.php');
    }

    if ($action === 'remove') {
        $productId = (int)($_POST['product_id'] ?? 0);
        cartRemove($productId);
        setFlash('success', 'محصول از سبد خرید حذف شد.');
        redirect(BASE_URL . '/user/cart.php');
    }

    if ($action === 'clear') {
        cartClear();
        setFlash('success', 'سبد خرید خالی شد.');
        redirect(BASE_URL . '/user/cart.php');
    }
}

// ----- نمایش سبد -----
requireLogin();

$user = currentUser();
$items = cartItems();
$total = cartTotal();

$pageTitle = 'سبد خرید';
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
                <li><a href="<?= BASE_URL ?>/user/orders.php">📦 سفارش‌های من</a></li>
                <li><a href="<?= BASE_URL ?>/user/cart.php" class="active">🛒 سبد خرید</a></li>
                <li><a href="<?= BASE_URL ?>/user/profile.php">👤 اطلاعات من</a></li>
                <li><a href="<?= BASE_URL ?>/user/logout.php" data-confirm="از حساب خود خارج می‌شوید؟">🚪 خروج</a></li>
            </ul>
        </aside>

        <div class="dashboard-content">
            <h1 class="page-title">سبد خرید</h1>

            <?php renderFlashes(); ?>

            <?php if (!isProfileComplete($user)): ?>
                <div class="flash flash-warning">
                    ⚠️ برای مشاهده سبد خرید و ادامه خرید، ابتدا باید اطلاعات پروفایل خود را کامل کنید.
                    <a href="<?= BASE_URL ?>/user/profile.php" style="font-weight:600;">تکمیل اطلاعات</a>
                </div>

                <?php if (!empty($items)): ?>
                    <p class="text-muted"><?= count($items) ?> محصول در سبد شما وجود دارد، اما تا تکمیل اطلاعات قابل مشاهده نیست.</p>
                <?php endif; ?>

            <?php elseif (empty($items)): ?>
                <div class="empty-state">
                    <div class="icon">🛒</div>
                    <h3>سبد خرید شما خالی است</h3>
                    <a href="<?= BASE_URL ?>/index.php?page=shop" class="btn btn-primary mt-2">مشاهده محصولات</a>
                </div>
            <?php else: ?>
                <form method="post" style="margin-bottom:16px;">
                    <?= csrfField() ?>
                    <div style="overflow-x:auto;">
                        <table class="cart-table">
                            <thead>
                                <tr>
                                    <th>محصول</th>
                                    <th>قیمت واحد</th>
                                    <th>تعداد</th>
                                    <th>جمع</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <?php $p = $item['product']; ?>
                                    <tr>
                                        <td data-label="محصول">
                                            <div class="cart-product">
                                                <img src="<?= e($p['image'] ? imageUrl('products', $p['image']) : BASE_URL . '/assets/images/placeholder.png') ?>"
                                                     alt="<?= e($p['title']) ?>">
                                                <a href="<?= BASE_URL ?>/index.php?page=product&id=<?= (int)$p['id'] ?>">
                                                    <?= e($p['title']) ?>
                                                </a>
                                            </div>
                                        </td>
                                        <td data-label="قیمت واحد"><?= formatPrice($item['unit_price']) ?></td>
                                        <td data-label="تعداد">
                                            <div class="quantity-selector" style="margin:0;">
                                                <button type="button" class="qty-minus">−</button>
                                                <input type="number" class="cart-qty-input"
                                                       data-product-id="<?= (int)$p['id'] ?>"
                                                       name="qty_<?= (int)$p['id'] ?>"
                                                       value="<?= (int)$item['quantity'] ?>"
                                                       min="1" max="<?= (int)$p['stock'] ?>">
                                                <button type="button" class="qty-plus">+</button>
                                            </div>
                                        </td>
                                        <td data-label="جمع"><strong><?= formatPrice($item['total']) ?></strong></td>
                                        <td data-label="">
                                            <button type="submit" name="action" value="remove_single_<?= (int)$p['id'] ?>"
                                                    style="display:none;"></button>
                                            <a href="#" class="cart-remove" 
                                               onclick="event.preventDefault(); document.getElementById('remove_<?= (int)$p['id'] ?>').submit();">🗑️</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </form>

                <!-- فرم‌های حذف جداگانه برای هر محصول -->
                <?php foreach ($items as $item): ?>
                    <form id="remove_<?= (int)$item['product']['id'] ?>" method="post" style="display:none;">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="product_id" value="<?= (int)$item['product']['id'] ?>">
                    </form>
                <?php endforeach; ?>

                <div class="cart-summary">
                    <div class="cart-total">
                        مبلغ کل: <span class="amount"><?= formatPrice($total) ?></span>
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <form method="post" onsubmit="return confirm('سبد خرید خالی شود؟');" style="display:inline;">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="clear">
                            <button type="submit" class="btn btn-outline btn-sm">خالی کردن سبد</button>
                        </form>
                        <a href="<?= BASE_URL ?>/user/checkout.php" class="btn btn-primary">ادامه خرید ←</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>