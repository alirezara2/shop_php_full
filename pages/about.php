<?php
if (!defined('BASE_PATH')) { die('دسترسی مستقیم مجاز نیست.'); }

// محتوای درباره ما از تنظیمات
$aboutText = getSetting('about_text', '');
$aboutImage = getSetting('about_image', '');

require_once BASE_PATH . '/includes/header.php';
?>

<section class="container">
    <div class="about-page">
        <?php if ($aboutImage): ?>
            <img src="<?= e(imageUrl('site', $aboutImage)) ?>" alt="درباره ما" class="about-hero">
        <?php else: ?>
            <div style="height:280px;background:linear-gradient(135deg,#2c5e3f,#4a8b64);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.5rem;">
                ⛰️ کوهنورد شاپ
            </div>
        <?php endif; ?>

        <div class="about-content">
            <h1>درباره ما</h1>

            <?php if ($aboutText): ?>
                <?= nl2br(e($aboutText)) ?>
            <?php else: ?>
                <p>
                    به <strong>کوهنورد شاپ</strong> خوش آمدید. ما مجموعه‌ای هستیم که با عشق به کوه و طبیعت،
                    تجهیزات تخصصی کوهنوردی را برای شما فراهم می‌کنیم.
                </p>
                <p>
                    از سال‌ها پیش با هدف ارائه بهترین کیفیت و قیمت منصفانه، در کنار کوهنوردان،
                    صخره‌نوردان و طبیعت‌گردان هستیم. تمام محصولات ما از برندهای معتبر و با
                    گارانتی اصالت عرضه می‌شوند.
                </p>
                <p>
                    تیم ما با تجربه‌ی سال‌ها صعود و کوهنوردی، آماده‌ی راهنمایی شما در انتخاب
                    تجهیزات مناسب است. سفرهای شما، با ما ایمن‌تر و لذت‌بخش‌تر خواهد بود.
                </p>
                <p style="margin-top:24px;color:var(--primary);font-weight:600;">
                    کوهنورد شاپ — همراه شما در هر صعود ⛰️
                </p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>