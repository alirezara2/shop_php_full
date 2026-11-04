<?php
if (!defined('BASE_PATH')) { die('دسترسی مستقیم مجاز نیست.'); }

$footerText = getSetting('footer_text', 'تمام حقوق محفوظ است.');
$contactPhone = getSetting('contact_phone', '');
$contactEmail = getSetting('contact_email', '');
$contactAddress = getSetting('contact_address', '');
$siteName = getSetting('site_name', 'کوهنورد شاپ');
$siteDescription = getSetting('site_description', '');
?>

<!-- فوتر -->
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">

            <!-- درباره -->
            <div class="footer-col">
                <h3>⛰️ <?= e($siteName) ?></h3>
                <?php if ($siteDescription): ?>
                    <p><?= e($siteDescription) ?></p>
                <?php endif; ?>
                <p style="margin-top:12px;">
                    همراه شما در هر صعود، با بهترین تجهیزات کوهنوردی.
                </p>
            </div>

            <!-- لینک‌های سریع -->
            <div class="footer-col">
                <h3>لینک‌های سریع</h3>
                <p><a href="<?= BASE_URL ?>/index.php">صفحه اصلی</a></p>
                <p><a href="<?= BASE_URL ?>/index.php?page=shop">فروشگاه</a></p>
                <p><a href="<?= BASE_URL ?>/index.php?page=about">درباره ما</a></p>
                <p><a href="<?= BASE_URL ?>/index.php?page=contact">ارتباط با ما</a></p>
            </div>

            <!-- حساب کاربری -->
            <div class="footer-col">
                <h3>حساب کاربری</h3>
                <?php if (isLoggedIn()): ?>
                    <p><a href="<?= BASE_URL ?>/user/dashboard.php">داشبورد</a></p>
                    <p><a href="<?= BASE_URL ?>/user/orders.php">سفارش‌های من</a></p>
                    <p><a href="<?= BASE_URL ?>/user/profile.php">اطلاعات من</a></p>
                    <p><a href="<?= BASE_URL ?>/user/logout.php">خروج</a></p>
                <?php else: ?>
                    <p><a href="<?= BASE_URL ?>/user/login.php">ورود</a></p>
                    <p><a href="<?= BASE_URL ?>/user/register.php">ثبت‌نام</a></p>
                <?php endif; ?>
            </div>

            <!-- تماس -->
            <div class="footer-col">
                <h3>تماس با ما</h3>
                <?php if ($contactPhone): ?>
                    <p>📞 <span dir="ltr"><?= e($contactPhone) ?></span></p>
                <?php endif; ?>
                <?php if ($contactEmail): ?>
                    <p>✉️ <?= e($contactEmail) ?></p>
                <?php endif; ?>
                <?php if ($contactAddress): ?>
                    <p>📍 <?= e($contactAddress) ?></p>
                <?php endif; ?>
            </div>

        </div>

        <div class="footer-bottom">
            <p>© <?= date('Y') ?> <?= e($siteName) ?> — <?= e($footerText) ?></p>
        </div>
    </div>
</footer>

<!-- اسکریپت‌ها -->
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script src="<?= BASE_URL ?>/assets/js/cart.js"></script>

</body>
</html>