<?php
require_once dirname(__DIR__) . '/includes/admin_auth.php';

$pageTitle = 'جزئیات سفارش';
$orderId = (int)($_GET['id'] ?? 0);

// ----- گرفتن سفارش -----
$stmt = $pdo->prepare("
    SELECT o.*, u.phone AS user_phone, u.first_name AS user_first_name, u.last_name AS user_last_name
    FROM orders o
    JOIN users u ON u.id = o.user_id
    WHERE o.id = ? LIMIT 1
");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    setFlash('error', 'سفارش یافت نشد.');
    redirect(BASE_URL . '/admin/orders/list.php');
}

// ----- گرفتن اقلام سفارش -----
$stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt->execute([$orderId]);
$items = $stmt->fetchAll();

// ----- پردازش POST برای تغییر وضعیت یا یادداشت -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $newStatus = $_POST['status'] ?? '';
        $allowed = [ORDER_PENDING, ORDER_PAID, ORDER_PREPARING, ORDER_SHIPPING, ORDER_CANCELED];

        if (!in_array($newStatus, $allowed, true)) {
            setFlash('error', 'وضعیت نامعتبر است.');
        } else {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
                $stmt->execute([$newStatus, $orderId]);

                // اگر از pending به paid رفت، موجودی محصولات کم شود
                if ($order['status'] === ORDER_PENDING && in_array($newStatus, [ORDER_PAID, ORDER_PREPARING, ORDER_SHIPPING], true)) {
                    foreach ($items as $item) {
                        $upd = $pdo->prepare("UPDATE products SET stock = GREATEST(stock - ?, 0) WHERE id = ?");
                        $upd->execute([$item['quantity'], $item['product_id']]);
                    }
                }

                // اگر به canceled رفت، موجودی برگردد (فقط اگر قبلاً کم شده بود)
                if ($newStatus === ORDER_CANCELED && in_array($order['status'], [ORDER_PAID, ORDER_PREPARING, ORDER_SHIPPING], true)) {
                    foreach ($items as $item) {
                        $upd = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
                        $upd->execute([$item['quantity'], $item['product_id']]);
                    }
                }

                $pdo->commit();
                setFlash('success', 'وضعیت سفارش به «' . statusLabel($newStatus) . '» تغییر کرد.');
                redirect(BASE_URL . '/admin/orders/view.php?id=' . $orderId);
            } catch (PDOException $e) {
                $pdo->rollBack();
                setFlash('error', 'خطا در تغییر وضعیت: ' . $e->getMessage());
            }
        }
    }

    if ($action === 'update_note') {
        $note = trim($_POST['admin_note'] ?? '');
        try {
            $stmt = $pdo->prepare("UPDATE orders SET admin_note = ? WHERE id = ?");
            $stmt->execute([$note ?: null, $orderId]);
            setFlash('success', 'یادداشت ذخیره شد.');
            redirect(BASE_URL . '/admin/orders/view.php?id=' . $orderId);
        } catch (PDOException $e) {
            setFlash('error', 'خطا در ذخیره یادداشت.');
        }
    }
}

require_once dirname(__DIR__) . '/includes/admin_header.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <h1 class="admin-page-title" style="margin:0;">
        سفارش <?= e(generateOrderNumber((int)$order['id'])) ?>
    </h1>
    <a href="<?= BASE_URL ?>/admin/orders/list.php" class="btn-admin btn-admin-secondary">
        ← بازگشت به لیست
    </a>
</div>

<?php renderFlashes(); ?>

<!-- وضعیت فعلی -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2>وضعیت سفارش</h2>
        <span class="badge-status badge-<?= e($order['status']) ?>" style="font-size:0.95rem;padding:8px 16px;">
            <?= e(statusLabel($order['status'])) ?>
        </span>
    </div>

    <form method="post" class="admin-form">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="update_status">

        <div class="form-row">
            <div class="form-group">
                <label for="status">تغییر وضعیت به:</label>
                <select id="status" name="status" class="form-control">
                    <?php foreach ($GLOBALS['ORDER_STATUS_LABELS'] as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= $order['status'] === $key ? 'selected' : '' ?>>
                            <?= e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="display:flex;align-items:flex-end;">
                <button type="submit" class="btn-admin btn-admin-primary"
                        data-confirm="وضعیت سفارش تغییر کند؟">
                    بروزرسانی وضعیت
                </button>
            </div>
        </div>
    </form>
</div>

<!-- اطلاعات مشتری -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2>اطلاعات مشتری</h2>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;">
        <div>
            <div class="text-muted" style="font-size:0.85rem;">نام و نام خانوادگی</div>
            <div><?= e(trim(($order['user_first_name'] ?? '') . ' ' . ($order['user_last_name'] ?? '')) ?: '—') ?></div>
        </div>
        <div>
            <div class="text-muted" style="font-size:0.85rem;">شماره تماس</div>
            <div><?= e($order['user_phone']) ?></div>
        </div>
        <div>
            <div class="text-muted" style="font-size:0.85rem;">تاریخ ثبت</div>
            <div><?= e(date('Y/m/d H:i', strtotime($order['created_at']))) ?></div>
        </div>
        <div>
            <div class="text-muted" style="font-size:0.85rem;">مبلغ کل</div>
            <div><strong><?= formatPrice($order['total_amount']) ?></strong></div>
        </div>
    </div>
</div>

<!-- آدرس ارسال -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2>آدرس ارسال</h2>
    </div>
    <div style="line-height:2;">
        <strong><?= e($order['shipping_name'] ?? '—') ?></strong><br>
        <?= e($order['shipping_city'] ?? '') ?>
        <?= e($order['shipping_address'] ?? '') ?><br>
        کد پستی: <?= e($order['shipping_postal_code'] ?? '—') ?><br>
        تماس: <?= e($order['shipping_phone'] ?? '—') ?>
    </div>
</div>

<!-- فیش بانکی -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2>فیش بانکی</h2>
    </div>
    <?php if ($order['receipt_image']): ?>
        <a href="<?= BASE_URL ?>/admin/orders/view_receipt.php?file=<?= e(urlencode($order['receipt_image'])) ?>"
           target="_blank" class="btn-admin btn-admin-primary btn-admin-sm">
            🔍 مشاهده فیش در تب جدید
        </a>
        <div style="margin-top:12px;">
            <img src="<?= BASE_URL ?>/admin/orders/view_receipt.php?file=<?= e(urlencode($order['receipt_image'])) ?>"
                 class="receipt-image" alt="فیش بانکی">
        </div>
    <?php else: ?>
        <p class="text-muted">هنوز فیشی آپلود نشده است.</p>
    <?php endif; ?>
</div>

<!-- اقلام سفارش -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2>اقلام سفارش</h2>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>محصول</th>
                    <th>تعداد</th>
                    <th>قیمت واحد</th>
                    <th>جمع</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= e($item['product_title']) ?></td>
                        <td><?= (int)$item['quantity'] ?></td>
                        <td><?= formatPrice($item['unit_price']) ?></td>
                        <td><?= formatPrice($item['total_price']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align:left;font-weight:600;">جمع کل:</td>
                    <td><strong><?= formatPrice($order['total_amount']) ?></strong></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- یادداشت ادمین -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2>یادداشت داخلی</h2>
    </div>
    <form method="post" class="admin-form">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="update_note">
        <div class="form-group">
            <textarea name="admin_note" class="form-control" rows="3"
                      placeholder="یادداشت داخلی (فقط ادمین می‌بیند)"><?= e($order['admin_note'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn-admin btn-admin-secondary">ذخیره یادداشت</button>
    </form>
</div>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>