<?php
if (!defined('BASE_PATH')) { die('دسترسی مستقیم مجاز نیست.'); }

function cartInit(): void {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
}

function cartAdd(int $productId, int $quantity = 1): void {
    cartInit();
    $quantity = max(1, (int)$quantity);
    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId] += $quantity;
    } else {
        $_SESSION['cart'][$productId] = $quantity;
    }
}

function cartUpdate(int $productId, int $quantity): void {
    cartInit();
    $quantity = (int)$quantity;
    if ($quantity <= 0) {
        cartRemove($productId);
    } else {
        $_SESSION['cart'][$productId] = $quantity;
    }
}

function cartRemove(int $productId): void {
    cartInit();
    unset($_SESSION['cart'][$productId]);
}

function cartClear(): void {
    $_SESSION['cart'] = [];
}

function cartCount(): int {
    cartInit();
    $count = 0;
    foreach ($_SESSION['cart'] as $qty) {
        $count += (int)$qty;
    }
    return $count;
}

function cartItems(): array {
    global $pdo;
    cartInit();
    if (empty($_SESSION['cart'])) return [];

    $ids = array_map('intval', array_keys($_SESSION['cart']));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders) AND is_active = 1");
    $stmt->execute($ids);
    $products = $stmt->fetchAll();

    $items = [];
    foreach ($products as $p) {
        $qty = (int)($_SESSION['cart'][$p['id']] ?? 0);
        if ($qty <= 0) continue;
        $unit = finalPrice($p);
        $items[] = [
            'product'    => $p,
            'quantity'   => $qty,
            'unit_price' => $unit,
            'total'      => $unit * $qty,
        ];
    }
    return $items;
}

function cartTotal(): int {
    $total = 0;
    foreach (cartItems() as $item) {
        $total += $item['total'];
    }
    return $total;
}

function cartValidateStock(): array {
    $errors = [];
    foreach (cartItems() as $item) {
        if ($item['product']['stock'] < $item['quantity']) {
            $errors[] = 'موجودی «' . $item['product']['title'] . '» کافی نیست.';
        }
    }
    return $errors;
}