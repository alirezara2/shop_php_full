<?php
require_once dirname(__DIR__) . '/includes/admin_auth.php';

$pageTitle = 'مشاهده پیام';
$id = (int)($_GET['id'] ?? 0);

// ----- گرفتن پیام -----
$stmt = $pdo->prepare("SELECT * FROM contact_messages WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$message = $stmt->fetch();

if (!$message) {
    setFlash('error', 'پیام یافت نشد.');
    redirect(BASE_URL . '/admin/messages/list.php');
}

// ----- علامت‌گذاری به‌عنوان خوانده‌شده -----
if (!$message['is_read']) {
    $upd = $pdo->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?");
    $upd->execute([$id]);
    $message['is_read'] = 1;
}

// ----- حذف پیام -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    if (($_POST['action'] ?? '') === 'delete') {
        try {
            // حذف عکس
            if ($message['image']) {
                deleteImage('contact', $message['image']);
            }
            $del = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
            $del->execute([$id]);
            setFlash('success', 'پیام با موفقیت حذف شد.');
            redirect(BASE_URL . '/admin/messages/list.php');
        } catch (PDOException $e) {
            setFlash('error', 'خطا در حذف پیام: ' . $e->getMessage());
        }
    }
}

require_once dirname(__DIR__) . '/includes/admin_header.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <h1 class="admin-page-title" style="margin:0;">مشاهده پیام #<?= (int)$id ?></h1>
    <a href="<?= BASE_URL ?>/admin/messages/list.php" class="btn-admin btn-admin-secondary">
        ← بازگشت به لیست
    </a>
</div>

<?php renderFlashes(); ?>

<div class="admin-card">
    <div class="admin-card-header">
        <h2>اطلاعات پیام</h2>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:20px;">
        <div>
            <div class="text-muted" style="font-size:0.85rem;">ایمیل فرستنده</div>
            <div><a href="mailto:<?= e($message['email']) ?>"><?= e($message['email']) ?></a></div>
        </div>
        <div>
            <div class="text-muted" style="font-size:0.85rem;">موضوع</div>
            <div><?= e($message['subject']) ?></div>
        </div>
        <div>
            <div class="text-muted" style="font-size:0.85rem;">تاریخ</div>
            <div><?= e(date('Y/m/d H:i', strtotime($message['created_at']))) ?></div>
        </div>
        <div>
            <div class="text-muted" style="font-size:0.85rem;">وضعیت</div>
            <div><span class="badge-soft badge-soft-success">خوانده شده</span></div>
        </div>
    </div>

    <div style="background:var(--admin-bg);padding:16px;border-radius:8px;line-height:1.9;">
        <?= nl2br(e($message['body'])) ?>
    </div>

    <?php if ($message['image']): ?>
        <div style="margin-top:20px;">
            <div class="text-muted" style="font-size:0.85rem;margin-bottom:8px;">عکس پیوست:</div>
            <a href="<?= BASE_URL ?>/uploads/contact/<?= e(urlencode($message['image'])) ?>" target="_blank">
                <img src="<?= BASE_URL ?>/uploads/contact/<?= e(urlencode($message['image'])) ?>"
                     class="receipt-image" alt="عکس پیوست">
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- پاسخ از طریق ایمیل -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2>پاسخ</h2>
    </div>
    <p class="text-muted" style="margin-bottom:12px;">
        می‌توانید از طریق ایمیل به کاربر پاسخ دهید:
    </p>
    <a href="mailto:<?= e($message['email']) ?>?subject=پاسخ: <?= e(urlencode($message['subject'])) ?>"
       class="btn-admin btn-admin-primary">
        ✉️ ارسال ایمیل پاسخ
    </a>
</div>

<!-- حذف -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2>حذف پیام</h2>
    </div>
    <form method="post">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="delete">
        <button type="submit" class="btn-admin btn-admin-danger"
                data-confirm="این پیام حذف شود؟ این عمل قابل بازگشت نیست.">
            🗑️ حذف پیام
        </button>
    </form>
</div>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>