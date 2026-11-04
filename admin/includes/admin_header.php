<?php
/**
 * هدر مشترک پنل ادمین
 */

if (!isset($currentAdmin)) {
    die('دسترسی غیرمجاز.');
}

$pageTitle = $pageTitle ?? 'پنل مدیریت';
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));

function isMenuActive(string $dir, string $file = null): string {
    global $currentDir, $currentPage;
    if ($file !== null) {
        return ($currentDir === $dir && $currentPage === $file) ? 'active' : '';
    }
    return ($currentDir === $dir) ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | پنل مدیریت</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet">
</head>
<body class="admin-body">

<div class="admin-layout">

    <aside class="admin-sidebar" id="adminSidebar">
        <div class="admin-brand">
            <span>⛰️</span>
            <span>پنل مدیریت</span>
        </div>

        <ul class="admin-menu">
            <li>
                <a href="<?= BASE_URL ?>/admin/index.php" class="<?= ($currentPage === 'index.php' && $currentDir === 'admin') ? 'active' : '' ?>">
                    <span class="menu-icon">🏠</span>
                    <span>داشبورد</span>
                </a>
            </li>

            <li class="menu-divider"></li>

            <li>
                <a href="<?= BASE_URL ?>/admin/products/list.php" class="<?= isMenuActive('products') ?>">
                    <span class="menu-icon">📦</span>
                    <span>محصولات</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/categories/list.php" class="<?= isMenuActive('categories') ?>">
                    <span class="menu-icon">🗂️</span>
                    <span>دسته‌بندی‌ها</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/slides/list.php" class="<?= isMenuActive('slides') ?>">
                    <span class="menu-icon">🖼️</span>
                    <span>اسلایدر</span>
                </a>
            </li>

            <li class="menu-divider"></li>

            <li>
                <a href="<?= BASE_URL ?>/admin/orders/list.php" class="<?= isMenuActive('orders') ?>">
                    <span class="menu-icon">🛒</span>
                    <span>سفارش‌ها</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/comments/list.php" class="<?= isMenuActive('comments') ?>">
                    <span class="menu-icon">💬</span>
                    <span>نظرات</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/messages/list.php" class="<?= isMenuActive('messages') ?>">
                    <span class="menu-icon">✉️</span>
                    <span>پیام‌ها</span>
                </a>
            </li>

            <li class="menu-divider"></li>

            <li>
                <a href="<?= BASE_URL ?>/admin/settings/index.php" class="<?= isMenuActive('settings') ?>">
                    <span class="menu-icon">⚙️</span>
                    <span>تنظیمات سایت</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>" target="_blank">
                    <span class="menu-icon">🌐</span>
                    <span>مشاهده سایت</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/logout.php" data-confirm="از حساب خود خارج می‌شوید؟">
                    <span class="menu-icon">🚪</span>
                    <span>خروج</span>
                </a>
            </li>
        </ul>
    </aside>

    <div class="admin-overlay" id="adminOverlay"></div>

    <div class="admin-main">

        <header class="admin-header">
            <div style="display:flex;align-items:center;gap:12px;">
                <button class="sidebar-toggle" id="sidebarToggle" aria-label="منو">☰</button>
                <div class="page-title-bar"><?= e($pageTitle) ?></div>
            </div>

            <div class="header-actions">
                <div class="admin-user-badge">
                    <div class="avatar"><?= e(mb_substr($currentAdmin['username'], 0, 1)) ?></div>
                    <span class="name"><?= e($currentAdmin['username']) ?></span>
                </div>
            </div>
        </header>

        <main class="admin-content">
            <?php renderFlashes(); ?>