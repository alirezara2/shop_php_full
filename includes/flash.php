<?php
if (!defined('BASE_PATH')) { die('دسترسی مستقیم مجاز نیست.'); }

function setFlash(string $type, string $message): void {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function getFlashes(): array {
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

function renderFlashes(): void {
    $flashes = getFlashes();
    if (empty($flashes)) return;

    $classes = [
        'success' => 'flash-success',
        'error'   => 'flash-error',
        'warning' => 'flash-warning',
        'info'    => 'flash-info',
    ];

    foreach ($flashes as $f) {
        $cls = $classes[$f['type']] ?? 'flash-info';
        echo '<div class="flash ' . $cls . '">' . e($f['message']) . '</div>';
    }
}