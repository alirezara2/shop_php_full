<?php
if (!defined('BASE_PATH')) { die('دسترسی مستقیم مجاز نیست.'); }

$siteName = getSetting('site_name', 'کوهنورد شاپ');
$siteLogo = getSetting('site_logo', '');
$currentPage = $_GET['page'] ?? 'home';
$cartCount = cartCount();
$loggedIn = isLoggedIn();
$user = $loggedIn ? currentUser() : null;
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e(getSetting('site_description', 'فروشگاه تخصصی تجهیزات کوهنوردی')) ?>">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' | ' : '' ?><?= e($siteName) ?></title>

    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet">

    <script>
        window.BASE_URL = <?= json_encode(BASE_URL) ?>;
    </script>
</head>
<body>

<header class="site-header">
    <div class="container">
        <div class="header-inner">

            <a href="<?= BASE_URL ?>/index.php" class="logo">
                <?php if ($siteLogo): ?>
                    <img src="<?= e(imageUrl('site', $siteLogo)) ?>" alt="<?= e($siteName) ?>">
                <?php else: ?>
                    <span>⛰️</span>
                    <span><?= e($siteName) ?></span>
                <?php endif; ?>
            </a>

            <input type="checkbox" id="menuToggleCheckbox" class="menu-toggle-checkbox" hidden>

            <nav class="main-nav" id="mainNav">
                <a href="<?= BASE_URL ?>/index.php" class="<?= $currentPage === 'home' ? 'active' : '' ?>">خانه</a>
                <a href="<?= BASE_URL ?>/index.php?page=shop" class="<?= $currentPage === 'shop' ? 'active' : '' ?>">فروشگاه</a>
                <a href="<?= BASE_URL ?>/index.php?page=about" class="<?= $currentPage === 'about' ? 'active' : '' ?>">درباره ما</a>
                <a href="<?= BASE_URL ?>/index.php?page=contact" class="<?= $currentPage === 'contact' ? 'active' : '' ?>">ارتباط با ما</a>

                <?php if ($loggedIn): ?>
                    <a href="<?= BASE_URL ?>/user/dashboard.php">داشبورد</a>
                <?php endif; ?>
            </nav>

            <div class="header-actions">
                <?php if ($loggedIn): ?>
                    <a href="<?= BASE_URL ?>/user/cart.php" class="cart-link">
                        🛒
                        <span class="cart-badge" style="<?= $cartCount > 0 ? '' : 'display:none;' ?>">
                            <?= (int)$cartCount ?>
                        </span>
                    </a>
                    <a href="<?= BASE_URL ?>/user/dashboard.php" class="btn-login">
                        <?= e(mb_substr(trim(($user['first_name'] ?? '') ?: 'حساب من'), 0, 12)) ?>
                    </a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/user/login.php" class="btn-login">ورود</a>
                <?php endif; ?>

                <label for="menuToggleCheckbox" class="menu-toggle" aria-label="باز کردن منو">
                    <span></span>
                    <span></span>
                    <span></span>
                </label>
            </div>

        </div>
    </div>
</header>

<label for="menuToggleCheckbox" class="overlay" id="overlay"></label>

<div class="container">
    <?php renderFlashes(); ?>
</div>